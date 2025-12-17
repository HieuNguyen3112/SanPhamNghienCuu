<?php

namespace App\Http\Controllers;

use App\Models\LecturerProfile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LecturerProfileController extends Controller
{
    // GET /api/profile/me
    public function me(Request $request)
    {
        $user = $request->user();
        $lecturer = $user?->lecturer;

        if (! $lecturer) {
            return response()->json(['message' => 'lecturer not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->buildProfilePayload($user, $lecturer),
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

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'degree_id' => ['sometimes', 'integer', 'exists:degrees,id'],
            'academic_rank_id' => ['sometimes', 'integer', 'exists:academic_ranks,id'],
            'gender' => ['sometimes', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'date'],
            'place_of_birth' => ['sometimes', 'string', 'max:255'],
            'ethnicity' => ['sometimes', 'string', 'max:100'],
            'hometown' => ['sometimes', 'string', 'max:255'],
            'personal_email' => ['sometimes', 'email', 'max:255'],
            'alternate_phone' => ['sometimes', 'string', 'max:50'],
            'address' => ['sometimes', 'string', 'max:500'],
            'emergency_contact_name' => ['sometimes', 'string', 'max:255'],
            'emergency_contact_phone' => ['sometimes', 'string', 'max:50'],
            'emergency_contact_relation' => ['sometimes', 'string', 'max:100'],
            'current_position' => ['sometimes', 'string', 'max:255'],
            'current_unit' => ['sometimes', 'string', 'max:255'],
            'research_area' => ['sometimes', 'string', 'max:255'],
            'teaching_specialization' => ['sometimes', 'string', 'max:255'],
            'orcid_id' => ['sometimes', 'string', 'max:50'],
            'google_scholar_profile' => ['sometimes', 'string', 'max:500'],
            'research_gate_profile' => ['sometimes', 'string', 'max:500'],
            'scopus_id' => ['sometimes', 'string', 'max:100'],
            'publons_id' => ['sometimes', 'string', 'max:100'],
            'personal_website' => ['sometimes', 'string', 'max:500'],
            'academic_portfolio_url' => ['sometimes', 'string', 'max:500'],
        ]);

        $lecturer->fill(collect($data)->only([
            'full_name',
            'phone',
            'department_id',
            'degree_id',
            'academic_rank_id',
        ])->toArray());
        $lecturer->save();

        $profile = LecturerProfile::firstOrNew(['lecturer_id' => $lecturer->id]);
        $profile->fill(collect($data)->only([
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
        ])->toArray());
        $profile->save();

        return response()->json([
            'message' => 'profile contact updated',
            'data' => $this->buildProfilePayload($user, $lecturer),
        ], Response::HTTP_OK);
    }

    private function buildProfilePayload($user, $lecturer): array
    {
        $profile = $lecturer->profile;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
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
            'academic_titles' => [],
            'educations' => [],
            'languages' => [],
            'research_areas' => [],
            'work_histories' => [],
        ];
    }
}
