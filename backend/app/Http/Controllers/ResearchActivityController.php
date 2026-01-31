<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResearchActivities\ResearchActivityDetailRequest;
use App\Http\Requests\ResearchActivities\ResearchActivityMembersRequest;
use App\Http\Requests\ResearchActivities\SubmitResearchActivityRequest;
use App\Http\Requests\ResearchActivities\StoreResearchActivityRequest;
use App\Http\Requests\ResearchActivities\UpdateResearchActivityRequest;
use App\Support\AuditLogger;
use App\Notifications\ParticipationInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResearchActivityController extends Controller
{
    public function store(StoreResearchActivityRequest $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();

        if (! $this->activityTypeMatchesKind($data['type_id'] ?? null, $data['kind_id'])) {
            return response()->json(['message' => 'type_id does not match kind_id'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $draftStatusId = $this->getStatusId('draft');
        if (! $draftStatusId) {
            return response()->json(['message' => 'draft status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => $this->generateActivityCode(),
            'owner_lecturer_id' => $lecturer->id,
            'kind_id' => $data['kind_id'],
            'type_id' => $data['type_id'] ?? null,
            'academic_year_id' => $data['academic_year_id'],
            'status_id' => $draftStatusId,
            'title' => $data['title'],
            'abstract' => $data['abstract'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'submitted_at' => null,
            'approved_at' => null,
            'total_hours_calc' => null,
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'research activity created',
            'data' => $this->serializeActivity($activityId),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateResearchActivityRequest $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isEditableStatus($current->status_code)) {
            return response()->json(['message' => 'activity is not editable'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validated();

        if (array_key_exists('kind_id', $data) && (int) $data['kind_id'] !== (int) $current->kind_id) {
            return response()->json(['message' => 'kind_id cannot be changed'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (array_key_exists('type_id', $data) && ! $this->activityTypeMatchesKind($data['type_id'], $current->kind_id)) {
            return response()->json(['message' => 'type_id does not match kind_id'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $updates = array_intersect_key($data, array_flip([
            'type_id',
            'academic_year_id',
            'title',
            'abstract',
            'start_date',
            'end_date',
            'quantity',
            'notes',
        ]));

        if ($updates) {
            $updates['updated_at'] = now();
            DB::table('research_activities')->where('id', $activity)->update($updates);
        }

        return response()->json([
            'message' => 'research activity updated',
            'data' => $this->serializeActivity($activity),
        ], Response::HTTP_OK);
    }

    public function upsertDetail(ResearchActivityDetailRequest $request, int $activity, string $detail)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isEditableStatus($current->status_code)) {
            return response()->json(['message' => 'activity is not editable'], Response::HTTP_FORBIDDEN);
        }

        $detailMap = $this->detailKindMap();
        $expectedKind = $detailMap[$detail] ?? null;
        if (! $expectedKind) {
            return response()->json(['message' => 'invalid detail kind'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($current->kind_code !== $expectedKind) {
            return response()->json(['message' => 'detail kind does not match activity kind'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validated();
        unset($data['detail']);

        $table = $detail;
        $now = now();
        $exists = DB::table($table)->where('activity_id', $activity)->exists();
        $payload = array_merge($data, [
            'activity_id' => $activity,
            'updated_at' => $now,
        ]);

        if (! $exists) {
            $payload['created_at'] = $now;
            DB::table($table)->insert($payload);
        } else {
            DB::table($table)->where('activity_id', $activity)->update($payload);
        }

        return response()->json([
            'message' => 'activity details updated',
            'data' => $this->serializeDetail($table, $activity),
        ], Response::HTTP_OK);
    }

    public function syncMembers(ResearchActivityMembersRequest $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->isEditableStatus($current->status_code)) {
            return response()->json(['message' => 'activity is not editable'], Response::HTTP_FORBIDDEN);
        }

        $items = $request->validated()['items'] ?? [];
        $now = now();
        $ownerLecturerId = (int) $current->owner_lecturer_id;
        $activityTitle = (string) ($current->title ?? '');
        $ownerName = DB::table('lecturers')->where('id', $ownerLecturerId)->value('full_name') ?? '';
        $roleNames = DB::table('member_roles')->pluck('name', 'id')->all();

        $synced = DB::transaction(function () use ($activity, $items, $now, $ownerLecturerId, $activityTitle, $ownerName, $roleNames) {
            if (count($items) === 0) {
                DB::table('research_activity_members')
                    ->where('activity_id', $activity)
                    ->delete();
                return [];
            }

            $handled = [];
            foreach ($items as $item) {
                $notifyInvitee = false;
                $payload = [
                    'activity_id' => $activity,
                    'lecturer_id' => $item['lecturer_id'],
                    'member_role_id' => $item['member_role_id'],
                    'contribution_share' => $item['contribution_share'] ?? null,
                    'hours_assigned' => $item['hours_assigned'] ?? null,
                    'updated_at' => $now,
                ];

                $existing = DB::table('research_activity_members')
                    ->where('activity_id', $activity)
                    ->where('lecturer_id', $item['lecturer_id'])
                    ->first();

                $isOwner = (int) $item['lecturer_id'] === $ownerLecturerId;
                $statusPayload = [];

                if ($isOwner) {
                    $statusPayload = [
                        'confirmation_status' => 'accepted',
                        'responded_at' => $now,
                        'confirmation_note' => null,
                    ];
                } elseif (! $existing) {
                    $statusPayload = [
                        'confirmation_status' => 'pending',
                        'responded_at' => null,
                        'confirmation_note' => null,
                    ];
                    $notifyInvitee = true;
                } else {
                    $changed =
                        (int) $existing->member_role_id !== (int) $item['member_role_id']
                        || (string) ($existing->contribution_share ?? '') !== (string) ($item['contribution_share'] ?? '')
                        || (string) ($existing->hours_assigned ?? '') !== (string) ($item['hours_assigned'] ?? '');

                    if ($changed) {
                        $statusPayload = [
                            'confirmation_status' => 'pending',
                            'responded_at' => null,
                            'confirmation_note' => null,
                        ];
                        $notifyInvitee = true;
                    } elseif ($existing->confirmation_status === null || $existing->confirmation_status === '') {
                        $statusPayload = [
                            'confirmation_status' => 'pending',
                            'responded_at' => null,
                            'confirmation_note' => null,
                        ];
                        $notifyInvitee = true;
                    }
                }

                if (! $existing) {
                    $payload['created_at'] = $now;
                    DB::table('research_activity_members')->insert(array_merge($payload, $statusPayload));
                } else {
                    DB::table('research_activity_members')
                        ->where('activity_id', $activity)
                        ->where('lecturer_id', $item['lecturer_id'])
                        ->update(array_merge($payload, $statusPayload));
                }

                if ($notifyInvitee) {
                    $memberId = DB::table('research_activity_members')
                        ->where('activity_id', $activity)
                        ->where('lecturer_id', $item['lecturer_id'])
                        ->value('id');
                    $inviteeUserId = DB::table('lecturers')
                        ->where('id', $item['lecturer_id'])
                        ->value('user_id');
                    if ($memberId && $inviteeUserId) {
                        $invitee = User::find($inviteeUserId);
                        if ($invitee) {
                            $roleName = $roleNames[$item['member_role_id']] ?? null;
                            $invitee->notify(new ParticipationInvitationNotification([
                                'title' => 'Lời mời tham gia công trình',
                                'message' => trim('Bạn được mời tham gia công trình ' . $activityTitle . ($ownerName ? (' bởi ' . $ownerName) : '') . '.'),
                                'activity_id' => (int) $activity,
                                'invitation_id' => (int) $memberId,
                                'role_name' => $roleName,
                                'action_route' => '/declarations/participatier',
                            ]));
                        }
                    }
                }

                $handled[] = $item['lecturer_id'];
            }

            DB::table('research_activity_members')
                ->where('activity_id', $activity)
                ->whereNotIn('lecturer_id', $handled)
                ->delete();

            return DB::table('research_activity_members')
                ->where('activity_id', $activity)
                ->get()
                ->map(fn($row) => (array) $row)
                ->all();
        });

        return response()->json([
            'message' => 'members synced',
            'data' => $synced,
        ], Response::HTTP_OK);
    }

    public function submit(SubmitResearchActivityRequest $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        if ($current->status_code !== 'draft') {
            return response()->json(['message' => 'only draft activities can be submitted'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Allow submit even if detail/member rows are incomplete;
        // frontend validation should prevent incomplete submissions.

        $submittedId = $this->getStatusId('submitted');
        if (! $submittedId) {
            return response()->json(['message' => 'submitted status not configured'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now();
        DB::transaction(function () use ($activity, $submittedId, $now, $current, $user) {
            DB::table('research_activities')->where('id', $activity)->update([
                'status_id' => $submittedId,
                'submitted_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('activity_status_histories')->insert([
                'activity_id' => $activity,
                'from_status_id' => $current->status_id,
                'to_status_id' => $submittedId,
                'acted_by_user_id' => $user->id,
                'acted_at' => $now,
                'note' => 'submitted',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $academicYearCode = null;
        if ($current->academic_year_id) {
            $academicYearCode = DB::table('academic_years')
                ->where('id', $current->academic_year_id)
                ->value('code');
        }

        $totalHours = (float) DB::table('research_activity_members')
            ->where('activity_id', $activity)
            ->sum('hours_assigned');

        AuditLogger::log($request, [
            'action_group' => 'approval',
            'action_code' => 'HOURS_SUBMITTED',
            'action_label' => 'Gửi yêu cầu duyệt giờ NCKH',
            'severity' => 'important',
            'result_status' => 'success',
            'target_type' => 'research_activity',
            'target_id' => $activity,
            'target_display' => $current->title ? 'Công trình: ' . $current->title : null,
            'request_http_status' => Response::HTTP_OK,
            'changes' => [
                'academic_year_id' => $current->academic_year_id,
                'academic_year_code' => $academicYearCode,
                'total_hours' => $totalHours,
            ],
        ], $user);

        return response()->json([
            'message' => 'activity submitted',
            'data' => $this->serializeActivity($activity),
        ], Response::HTTP_OK);
    }

    public function show(Request $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        $detailKind = $this->detailTableByKind()[$current->kind_code] ?? null;
        $detail = $detailKind
            ? DB::table($detailKind)->where('activity_id', $activity)->first()
            : null;

        $members = DB::table('research_activity_members as ram')
            ->leftJoin('member_roles as mr', 'ram.member_role_id', '=', 'mr.id')
            ->where('ram.activity_id', $activity)
            ->select([
                'ram.lecturer_id',
                'ram.member_role_id',
                'ram.contribution_share',
                'ram.hours_assigned',
                'mr.code as member_role_code',
                'mr.name as member_role_name',
            ])
            ->get();

        $evidenceFiles = $this->fetchEvidenceFiles($activity);

        $activityPayload = $this->serializeActivityRow($current);
        $activityPayload['status_code'] = $current->status_code;
        $activityPayload['kind_code'] = $current->kind_code;

        return response()->json([
            'data' => [
                'activity' => $activityPayload,
                'detail_kind' => $detailKind,
                'detail' => $detail ? (array) $detail : null,
                'members' => $members,
                'evidence_files' => $evidenceFiles,
            ],
        ], Response::HTTP_OK);
    }

    public function listEvidenceFiles(Request $request, int $activity)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        $current = $this->getActivityWithMeta($activity, $lecturer->id);
        if (! $current) {
            return response()->json(['message' => 'activity not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->fetchEvidenceFiles($activity),
        ], Response::HTTP_OK);
    }

    private function serializeActivity(int $activityId): array
    {
        $row = DB::table('research_activities')->where('id', $activityId)->first();

        if (! $row) {
            return [];
        }

        return $this->serializeActivityRow($row);
    }

    private function serializeDetail(string $table, int $activityId): array
    {
        $row = DB::table($table)->where('activity_id', $activityId)->first();
        return $row ? (array) $row : [];
    }

    private function getActivityWithMeta(int $activityId, int $lecturerId): ?object
    {
        return DB::table('research_activities as ra')
            ->join('activity_statuses as ast', 'ra.status_id', '=', 'ast.id')
            ->join('activity_kinds as ak', 'ra.kind_id', '=', 'ak.id')
            ->where('ra.id', $activityId)
            ->where('ra.owner_lecturer_id', $lecturerId)
            ->select([
                'ra.*',
                'ast.code as status_code',
                'ak.code as kind_code',
            ])
            ->first();
    }

    private function getStatusId(string $code): ?int
    {
        return DB::table('activity_statuses')->where('code', $code)->value('id');
    }

    private function generateActivityCode(): string
    {
        return 'ACT-' . Str::upper(Str::uuid()->toString());
    }

    private function activityTypeMatchesKind(?int $typeId, int $kindId): bool
    {
        if (! $typeId) {
            return true;
        }

        return DB::table('activity_types')
            ->where('id', $typeId)
            ->where('kind_id', $kindId)
            ->exists();
    }

    private function isEditableStatus(?string $statusCode): bool
    {
        return in_array($statusCode, ['draft', 'rejected'], true);
    }

    private function detailKindMap(): array
    {
        return [
            'paper_details' => 'paper',
            'book_details' => 'book',
            'project_details' => 'project',
            'conference_details' => 'conference',
        ];
    }

    private function detailTableByKind(): array
    {
        return [
            'paper' => 'paper_details',
            'book' => 'book_details',
            'project' => 'project_details',
            'conference' => 'conference_details',
        ];
    }

    private function serializeActivityRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'activity_code' => $row->activity_code,
            'owner_lecturer_id' => $row->owner_lecturer_id !== null ? (int) $row->owner_lecturer_id : null,
            'kind_id' => $row->kind_id !== null ? (int) $row->kind_id : null,
            'type_id' => $row->type_id !== null ? (int) $row->type_id : null,
            'academic_year_id' => $row->academic_year_id !== null ? (int) $row->academic_year_id : null,
            'status_id' => $row->status_id !== null ? (int) $row->status_id : null,
            'title' => $row->title,
            'abstract' => $row->abstract,
            'start_date' => $row->start_date,
            'end_date' => $row->end_date,
            'quantity' => $row->quantity,
            'submitted_at' => $row->submitted_at,
            'approved_at' => $row->approved_at,
            'total_hours_calc' => $row->total_hours_calc,
            'notes' => $row->notes,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function fetchEvidenceFiles(int $activityId): array
    {
        return DB::table('evidence_files as ef')
            ->leftJoin('evidence_file_types as eft', 'ef.file_type_id', '=', 'eft.id')
            ->where('ef.activity_id', $activityId)
            ->select([
                'ef.id',
                'ef.activity_id',
                'ef.file_type_id',
                'ef.disk',
                'ef.path',
                'ef.original_name',
                'ef.mime_type',
                'ef.size_bytes',
                'ef.sha256',
                'ef.uploaded_by_user_id',
                'ef.uploaded_at',
                'ef.created_at',
                'ef.updated_at',
                'eft.name as file_type_name',
            ])
            ->orderByDesc('ef.uploaded_at')
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                $data['url'] = null;
                return $data;
            })
            ->all();
    }
}
