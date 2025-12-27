<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\LanguageSyncRequest;
use App\Http\Resources\LecturerLanguageResource;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\LecturerProfile;
use App\Models\LecturerTrainingHistory;
use App\Models\LecturerWorkHistory;
use App\Support\RoleMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LecturerProfileController extends Controller
{
    // GET /api/profile/me
    public function me(Request $request)
    {
        $user = $request->user();
        $lecturer = $this->resolveLecturerForUser($user);

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $lecturer);

        return response()->json([
            'data' => $this->buildProfilePayload($user, $lecturer),
        ], Response::HTTP_OK);
    }

    // GET /api/profile/lecturers/{lecturer}
    public function showLecturer(Request $request, Lecturer $lecturer)
    {
        $this->authorize('view', $lecturer);

        $profileUser = $lecturer->user;

        return response()->json([
            'data' => $this->buildProfilePayload($profileUser, $lecturer),
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/contact
    public function updateContact(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $lecturerFields = [
            'full_name',
            'phone',
            'department_id',
            'degree_id',
            'academic_rank_id',
        ];

        $profileFields = [
            'gender',
            'date_of_birth',
            'place_of_birth',
            'ethnicity',
            'hometown',
            'personal_email',
            'alternate_phone',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relation',
            'current_position',
            'current_unit',
            'research_area',
            'teaching_specialization',
            'orcid_id',
            'google_scholar_profile',
            'research_gate_profile',
            'scopus_id',
            'publons_id',
            'personal_website',
            'academic_portfolio_url',
        ];

        $nullableFields = array_merge(
            array_diff($lecturerFields, ['full_name']),
            $profileFields,
        );

        $normalized = [];
        $input = $request->all();
        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $normalized[$field] = null;
            }
        }

        if ($normalized) {
            $request->merge($normalized);
        }

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'academic_rank_id' => ['nullable', 'integer', 'exists:academic_ranks,id'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
            'hometown' => ['nullable', 'string', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'current_unit' => ['nullable', 'string', 'max:255'],
            'research_area' => ['nullable', 'string', 'max:255'],
            'teaching_specialization' => ['nullable', 'string', 'max:255'],
            'orcid_id' => ['nullable', 'string', 'max:50'],
            'google_scholar_profile' => ['nullable', 'string', 'max:500'],
            'research_gate_profile' => ['nullable', 'string', 'max:500'],
            'scopus_id' => ['nullable', 'string', 'max:100'],
            'publons_id' => ['nullable', 'string', 'max:100'],
            'personal_website' => ['nullable', 'string', 'max:500'],
            'academic_portfolio_url' => ['nullable', 'string', 'max:500'],
        ]);

        $lecturerData = collect($data)->only($lecturerFields)->toArray();
        // Avoid wiping non-nullable department_id when the UI sends empty values.
        if (array_key_exists('department_id', $lecturerData) && $lecturerData['department_id'] === null) {
            unset($lecturerData['department_id']);
        }

        $lecturer->fill($lecturerData);
        $lecturer->save();

        $profile = LecturerProfile::firstOrNew(['lecturer_id' => $lecturer->id]);
        $profile->fill(collect($data)->only($profileFields)->toArray());
        $profile->save();

        return response()->json([
            'message' => 'profile contact updated',
            'data' => $this->buildProfilePayload($user, $lecturer),
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/academic-titles
    public function updateAcademicTitles(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $rawItems = $request->input('items');
        if (is_array($rawItems)) {
            $items = $this->normalizeAcademicTitlesItems($rawItems);
            $request->merge(['items' => $items]);
        }

        $payload = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['array'],
            'items.*.degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'items.*.academic_rank_id' => ['nullable', 'integer', 'exists:academic_ranks,id'],
        ]);

        $items = $payload['items'] ?? [];
        $updates = [];

        if (count($items) === 0) {
            $updates['degree_id'] = null;
            $updates['academic_rank_id'] = null;
        } else {
            $item = $items[0];
            if (array_key_exists('degree_id', $item)) {
                $updates['degree_id'] = $item['degree_id'];
            }
            if (array_key_exists('academic_rank_id', $item)) {
                $updates['academic_rank_id'] = $item['academic_rank_id'];
            }
        }

        if ($updates) {
            $lecturer->fill($updates);
            $lecturer->save();
        }

        return response()->json([
            'message' => 'academic titles updated',
            'data' => $this->buildProfilePayload($user, $lecturer),
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/research-areas
    public function updateResearchAreas(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $payload = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['nullable'],
            'teaching_specialization' => ['nullable', 'string'],
        ]);

        $researchArea = $this->buildResearchAreaString($payload['items'] ?? []);
        $profile = LecturerProfile::firstOrNew(['lecturer_id' => $lecturer->id]);
        $profile->research_area = $researchArea;

        if (array_key_exists('teaching_specialization', $payload)) {
            $profile->teaching_specialization = $this->truncateString($payload['teaching_specialization'], 255);
        }

        $profile->save();

        return response()->json([
            'message' => 'research areas updated',
            'data' => $this->buildProfilePayload($user, $lecturer),
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/languages
    public function syncLanguages(LanguageSyncRequest $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $items = $request->validated()['items'] ?? [];

        $synced = DB::transaction(function () use ($lecturer, $items) {
            if (count($items) === 0) {
                $lecturer->languageProficiencies()->delete();
                return $lecturer->languageProficiencies()->get();
            }

            $existing = $lecturer->languageProficiencies()->get()->keyBy('id');
            $handledIds = [];

            foreach ($items as $index => $item) {
                $isCreate = empty($item['id']);

                if (! $isCreate && ! $existing->has($item['id'])) {
                    throw ValidationException::withMessages([
                        "items.$index.id" => ['invalid language id'],
                    ]);
                }

                $data = $this->mapLanguagePayload($item);

                if ($isCreate) {
                    $language = $lecturer->languageProficiencies()->create($data);
                } else {
                    $language = $existing[$item['id']];
                    $language->fill($data);
                    $language->save();
                }

                $handledIds[] = $language->id;
            }

            $lecturer->languageProficiencies()
                ->whereNotIn('id', $handledIds)
                ->delete();

            return $lecturer->languageProficiencies()->get();
        });

        return response()->json([
            'message' => 'languages synced',
            'data' => LecturerLanguageResource::collection($synced)->resolve(),
        ], Response::HTTP_OK);
    }

    // GET /api/profile/educations
    public function trainingHistories(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $lecturer);

        $histories = $lecturer->trainingHistories()->with('degree')->get();

        return response()->json([
            'data' => $this->serializeTrainingHistories($histories),
        ], Response::HTTP_OK);
    }

    // POST /api/profile/educations
    public function storeTrainingHistory(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $input = $this->normalizeTrainingHistoryInput($request->all());
        $data = Validator::make($input, $this->trainingHistoryRules(true))->validate();

        if (array_key_exists('is_current', $data) && $data['is_current'] === null) {
            unset($data['is_current']);
        }

        $history = $lecturer->trainingHistories()->create($data);
        $history->load('degree');

        return response()->json([
            'message' => 'education created',
            'data' => $this->serializeTrainingHistory($history),
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/educations/{id}
    public function updateTrainingHistory(Request $request, int $id)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $history = LecturerTrainingHistory::findOrFail($id);

        if ($history->lecturer_id !== $lecturer->id) {
            return response()->json(['message' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        $input = $this->normalizeTrainingHistoryInput($request->all());
        $data = Validator::make($input, $this->trainingHistoryRules(false))->validate();

        if (array_key_exists('is_current', $data) && $data['is_current'] === null) {
            unset($data['is_current']);
        }

        $history->fill($data);
        $history->save();
        $history->load('degree');

        return response()->json([
            'message' => 'education updated',
            'data' => $this->serializeTrainingHistory($history),
        ], Response::HTTP_OK);
    }

    // DELETE /api/profile/educations/{id}
    public function deleteTrainingHistory(Request $request, int $id)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $history = LecturerTrainingHistory::findOrFail($id);

        if ($history->lecturer_id !== $lecturer->id) {
            return response()->json(['message' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        $history->delete();

        return response()->json([
            'message' => 'education deleted',
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/educations (batch sync)
    public function syncTrainingHistories(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $payload = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['array'],
            'items.*.id' => ['nullable', 'integer'],
        ]);

        $items = $payload['items'] ?? [];

        $synced = DB::transaction(function () use ($lecturer, $items) {
            if (count($items) === 0) {
                $lecturer->trainingHistories()->delete();
                return $lecturer->trainingHistories()->with('degree')->get();
            }

            $existing = $lecturer->trainingHistories()->get()->keyBy('id');
            $handledIds = [];

            foreach ($items as $index => $item) {
                $item = $this->normalizeTrainingHistoryInput($item);
                $isCreate = empty($item['id']);

                if (! $isCreate && ! $existing->has($item['id'])) {
                    throw ValidationException::withMessages([
                        "items.$index.id" => ['invalid education id'],
                    ]);
                }

                $data = Validator::make($item, $this->trainingHistoryRules($isCreate))->validate();

                if (array_key_exists('is_current', $data) && $data['is_current'] === null) {
                    unset($data['is_current']);
                }

                if ($isCreate) {
                    $history = $lecturer->trainingHistories()->create($data);
                } else {
                    $history = $existing[$item['id']];
                    $history->fill($data);
                    $history->save();
                }

                $handledIds[] = $history->id;
            }

            $lecturer->trainingHistories()
                ->whereNotIn('id', $handledIds)
                ->delete();

            return $lecturer->trainingHistories()->with('degree')->get();
        });

        return response()->json([
            'message' => 'educations synced',
            'data' => $this->serializeTrainingHistories($synced),
        ], Response::HTTP_OK);
    }

    // GET /api/profile/work-histories
    public function workHistories(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $lecturer);

        return response()->json([
            'data' => $this->serializeWorkHistories($lecturer->workHistories()->get()),
        ], Response::HTTP_OK);
    }

    // POST /api/profile/work-histories
    public function storeWorkHistory(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $input = $this->normalizeWorkHistoryInput($request->all());
        $data = Validator::make($input, $this->workHistoryRules(true))->validate();

        if (array_key_exists('is_current', $data) && $data['is_current'] === null) {
            unset($data['is_current']);
        }

        $history = $lecturer->workHistories()->create($data);

        return response()->json([
            'message' => 'work history created',
            'data' => $this->serializeWorkHistory($history),
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/work-histories/{id}
    public function updateWorkHistory(Request $request, int $id)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $history = $lecturer->workHistories()->findOrFail($id);

        $input = $this->normalizeWorkHistoryInput($request->all());
        $data = Validator::make($input, $this->workHistoryRules(false))->validate();

        if (array_key_exists('is_current', $data) && $data['is_current'] === null) {
            unset($data['is_current']);
        }

        $history->fill($data);
        $history->save();

        return response()->json([
            'message' => 'work history updated',
            'data' => $this->serializeWorkHistory($history),
        ], Response::HTTP_OK);
    }

    // DELETE /api/profile/work-histories/{id}
    public function deleteWorkHistory(Request $request, int $id)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $history = $lecturer->workHistories()->findOrFail($id);
        $history->delete();

        return response()->json([
            'message' => 'work history deleted',
        ], Response::HTTP_OK);
    }

    // PUT /api/profile/work-histories (batch sync)
    public function syncWorkHistories(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $lecturer);

        $payload = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['array'],
            'items.*.id' => ['nullable', 'integer'],
        ]);

        $items = $payload['items'] ?? [];

        $synced = DB::transaction(function () use ($lecturer, $items) {
            if (count($items) === 0) {
                $lecturer->workHistories()->delete();
                return $lecturer->workHistories()->get();
            }

            $existing = $lecturer->workHistories()->get()->keyBy('id');
            $handledIds = [];

            foreach ($items as $index => $item) {
                $item = $this->normalizeWorkHistoryInput($item);
                $isCreate = empty($item['id']);

                if (! $isCreate && ! $existing->has($item['id'])) {
                    throw ValidationException::withMessages([
                        "items.$index.id" => ['invalid work history id'],
                    ]);
                }

                $data = Validator::make($item, $this->workHistoryRules($isCreate))->validate();

                if (array_key_exists('is_current', $data) && $data['is_current'] === null) {
                    unset($data['is_current']);
                }

                if ($isCreate) {
                    $history = $lecturer->workHistories()->create($data);
                } else {
                    $history = $existing[$item['id']];
                    $history->fill($data);
                    $history->save();
                }

                $handledIds[] = $history->id;
            }

            $lecturer->workHistories()
                ->whereNotIn('id', $handledIds)
                ->delete();

            return $lecturer->workHistories()->get();
        });

        return response()->json([
            'message' => 'work histories synced',
            'data' => $this->serializeWorkHistories($synced),
        ], Response::HTTP_OK);
    }

    private function buildProfilePayload($user, $lecturer): array
    {
        $profile = $lecturer->profile;
        $partyMembership = $lecturer->partyMembership;
        $trainingHistories = $lecturer->trainingHistories()->with('degree')->get();
        $workHistories = $lecturer->workHistories()->get();
        $languageProficiencies = $lecturer->languageProficiencies()->get();
        $backendRoles = $user ? $user->getRoleNames()->values()->all() : [];

        return [
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name ?? $lecturer->full_name,
                'email' => $user?->email ?? $lecturer->email,
                'roles' => RoleMapper::backendListToCanonical($backendRoles),
                'backend_roles' => $backendRoles,
            ],
            'lecturer' => [
                'id' => $lecturer->id,
                'code' => $lecturer->code,
                'full_name' => $lecturer->full_name,
                'email' => $lecturer->email,
                'phone' => $lecturer->phone,
                'department_id' => $lecturer->department_id,
                'department_name' => $lecturer->department->name ?? null,
                'degree_id' => $lecturer->degree_id,
                'degree_name' => $lecturer->degree->name ?? null,
                'academic_rank_id' => $lecturer->academic_rank_id,
                'academic_rank_name' => $lecturer->academicRank->name ?? null,
                'active' => $lecturer->active,
            ],
            'profile' => $profile ? [
                'gender' => $profile->gender,
                'date_of_birth' => $profile->date_of_birth,
                'place_of_birth' => $profile->place_of_birth,
                'ethnicity' => $profile->ethnicity,
                'hometown' => $profile->hometown,
                'personal_email' => $profile->personal_email,
                'alternate_phone' => $profile->alternate_phone,
                'address' => $profile->address,
                'emergency_contact_name' => $profile->emergency_contact_name,
                'emergency_contact_phone' => $profile->emergency_contact_phone,
                'emergency_contact_relation' => $profile->emergency_contact_relation,
                'current_position' => $profile->current_position,
                'current_unit' => $profile->current_unit,
                'research_area' => $profile->research_area,
                'teaching_specialization' => $profile->teaching_specialization,
                'orcid_id' => $profile->orcid_id,
                'google_scholar_profile' => $profile->google_scholar_profile,
                'research_gate_profile' => $profile->research_gate_profile,
                'scopus_id' => $profile->scopus_id,
                'publons_id' => $profile->publons_id,
                'personal_website' => $profile->personal_website,
                'academic_portfolio_url' => $profile->academic_portfolio_url,
            ] : null,
            // Các danh sách chi tiết khác: hiện để trống, có thể mở rộng sau
            'party_membership' => $partyMembership ? [
                'is_member' => $partyMembership->is_member,
                'membership_no' => $partyMembership->membership_no,
                'joined_at' => $partyMembership->joined_at?->toDateString(),
                'official_at' => $partyMembership->official_at?->toDateString(),
                'joining_place' => $partyMembership->joining_place,
                'current_branch' => $partyMembership->current_branch,
                'position' => $partyMembership->position,
                'status' => $partyMembership->status,
                'notes' => $partyMembership->notes,
            ] : null,
            'academic_titles' => [],
            'educations' => $this->serializeTrainingHistories($trainingHistories),
            'latest_education' => $trainingHistories->isNotEmpty()
                ? $this->serializeTrainingHistory($trainingHistories->first())
                : null,
            'languages' => LecturerLanguageResource::collection($languageProficiencies)->resolve(),
            'research_areas' => $this->serializeResearchAreas($profile?->research_area),
            'work_histories' => $this->serializeWorkHistories($workHistories),
            'latest_work_history' => $workHistories->isNotEmpty()
                ? $this->serializeWorkHistory($workHistories->first())
                : null,
        ];
    }

    private function resolveLecturerForUser($user): ?Lecturer
    {
        if (! $user) {
            return null;
        }

        $lecturer = $user->lecturer;
        if ($lecturer) {
            return $lecturer;
        }

        $department = Department::query()->orderBy('id')->first();
        if (! $department) {
            return null;
        }

        return Lecturer::create([
            'user_id' => $user->id,
            'code' => 'GV-' . $user->id,
            'full_name' => $user->name ?? $user->email ?? ('Lecturer ' . $user->id),
            'email' => $user->email,
            'phone' => null,
            'department_id' => $department->id,
            'degree_id' => null,
            'academic_rank_id' => null,
            'active' => true,
        ]);
    }

    private function normalizeAcademicTitlesItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (['degree_id', 'academic_rank_id'] as $field) {
                if (array_key_exists($field, $item) && $item[$field] === '') {
                    $item[$field] = null;
                }
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    private function buildResearchAreaString(array $items): ?string
    {
        $parts = [];

        foreach ($items as $item) {
            $value = null;

            if (is_string($item)) {
                $value = $item;
            } elseif (is_array($item)) {
                $value = $item['name'] ?? $item['value'] ?? $item['label'] ?? null;
            }

            if ($value === null) {
                continue;
            }

            $value = trim($value);
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        if (! $parts) {
            return null;
        }

        return $this->truncateString(implode(', ', $parts), 255);
    }

    private function truncateString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function normalizeTrainingHistoryInput(array $input): array
    {
        $nullableFields = [
            'degree_id',
            'degree_title',
            'major',
            'country',
            'city',
            'start_date',
            'end_date',
            'training_form',
            'funding_source',
            'certificate_no',
            'notes',
        ];

        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        if (array_key_exists('is_current', $input) && $input['is_current'] === '') {
            $input['is_current'] = null;
        }

        return $input;
    }

    private function trainingHistoryRules(bool $isCreate): array
    {
        return [
            'degree_id' => ['nullable', 'integer', 'exists:degrees,id'],
            'degree_title' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'institution' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:150'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_current' => ['nullable', 'boolean'],
            'training_form' => ['nullable', 'string', 'max:100'],
            'funding_source' => ['nullable', 'string', 'max:150'],
            'certificate_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function serializeTrainingHistory(LecturerTrainingHistory $history): array
    {
        return [
            'id' => $history->id,
            'degree_id' => $history->degree_id,
            'degree_name' => $history->degree?->name,
            'degree_title' => $history->degree_title,
            'major' => $history->major,
            'institution' => $history->institution,
            'country' => $history->country,
            'city' => $history->city,
            'start_date' => $history->start_date?->toDateString(),
            'end_date' => $history->end_date?->toDateString(),
            'is_current' => $history->is_current,
            'training_form' => $history->training_form,
            'funding_source' => $history->funding_source,
            'certificate_no' => $history->certificate_no,
            'notes' => $history->notes,
        ];
    }

    private function serializeTrainingHistories($histories): array
    {
        return $histories
            ->map(fn(LecturerTrainingHistory $history) => $this->serializeTrainingHistory($history))
            ->values()
            ->all();
    }

    private function normalizeWorkHistoryInput(array $input): array
    {
        $nullableFields = [
            'position',
            'department',
            'workplace',
            'start_date',
            'end_date',
            'employment_type',
            'reason_for_leaving',
            'notes',
        ];

        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        if (array_key_exists('is_current', $input) && $input['is_current'] === '') {
            $input['is_current'] = null;
        }

        return $input;
    }

    private function mapLanguagePayload(array $item): array
    {
        return [
            'language' => $item['language'],
            'proficiency_level' => $item['level'],
            'is_native' => $item['is_native'] ?? false,
            'certificate_name' => $item['certificate_name'] ?? null,
            'certificate_level' => $item['certificate_level'] ?? null,
            'certificate_score' => $item['certificate_score'] ?? null,
            'issued_by' => $item['certificate_issuer'] ?? null,
            'issued_at' => $item['issue_date'] ?? null,
            'expires_at' => $item['expire_date'] ?? null,
            'notes' => $item['note'] ?? null,
        ];
    }

    private function serializeResearchAreas(?string $researchArea): array
    {
        if (! $researchArea) {
            return [];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $researchArea))));

        if (! $parts) {
            return [];
        }

        $items = [];
        foreach ($parts as $index => $part) {
            $items[] = [
                'id' => $index + 1,
                'name' => $part,
                'type' => $index === 0 ? 'PRIMARY' : 'SECONDARY',
                'start_year' => null,
                'keywords' => null,
                'description' => null,
            ];
        }

        return $items;
    }

    private function workHistoryRules(bool $isCreate): array
    {
        return [
            'organization' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'workplace' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_current' => ['nullable', 'boolean'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'reason_for_leaving' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function serializeWorkHistory(LecturerWorkHistory $history): array
    {
        return [
            'id' => $history->id,
            'organization' => $history->organization,
            'position' => $history->position,
            'department' => $history->department,
            'workplace' => $history->workplace,
            'start_date' => $history->start_date?->toDateString(),
            'end_date' => $history->end_date?->toDateString(),
            'is_current' => $history->is_current,
            'employment_type' => $history->employment_type,
            'reason_for_leaving' => $history->reason_for_leaving,
            'notes' => $history->notes,
        ];
    }

    private function serializeWorkHistories($histories): array
    {
        return $histories
            ->map(fn(LecturerWorkHistory $history) => $this->serializeWorkHistory($history))
            ->values()
            ->all();
    }
}
