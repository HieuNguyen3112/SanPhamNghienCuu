<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResearchActivityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'gv@local.test')->first();
        if (! $user) {
            return;
        }

        $lecturer = Lecturer::where('user_id', $user->id)->first();
        if (! $lecturer) {
            return;
        }

        $now = now();
        $kindIds = DB::table('activity_kinds')->pluck('id', 'code')->all();
        $typeIds = DB::table('activity_types')->pluck('id', 'code')->all();
        $statusIds = DB::table('activity_statuses')
            ->whereIn('code', ['approved', 'draft', 'submitted', 'rejected'])
            ->pluck('id', 'code')
            ->all();
        $statusId = $statusIds['approved'] ?? null;
        $academicYearId = DB::table('academic_years')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->value('id');
        $memberRoleId = DB::table('member_roles')->where('code', 'principal')->value('id');
        $assistantStageId = DB::table('approval_stages')->where('code', 'assistant')->value('id');
        $managerStageId = DB::table('approval_stages')->where('code', 'manager')->value('id');

        if (! $statusId || ! $academicYearId || ! $kindIds) {
            return;
        }

        $activities = [
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_900',
                'activity_code' => 'RA-PAPER-001',
                'title' => 'Ung dung LLM trong tro giang',
                'start_date' => '2024-03-01',
                'end_date' => '2024-11-15',
                'details_table' => 'paper_details',
                'status_code' => 'approved',
                'details' => [
                    'journal_name' => 'Tap chi Khoa hoc Giao duc So',
                    'issn' => '1234-5678',
                    'doi' => '10.1000/xyz123',
                    'article_url' => 'https://example.local/paper/ai-edu',
                    'volume' => '12',
                    'issue' => '2',
                    'page_start' => 101,
                    'page_end' => 120,
                    'year' => 2024,
                ],
            ],
            [
                'kind_code' => 'book',
                'type_code' => 'textbook',
                'activity_code' => 'RA-BOOK-001',
                'title' => 'Giao trinh Lap trinh Web',
                'start_date' => '2023-01-01',
                'end_date' => '2023-12-01',
                'details_table' => 'book_details',
                'status_code' => 'approved',
                'details' => [
                    'publisher' => 'Nha xuat ban Dai hoc',
                    'approval_decision_no' => 'QD-2023-01',
                    'approval_decision_date' => '2023-02-01',
                    'isbn' => '978-604-000000-1',
                    'pages' => 320,
                    'year' => 2023,
                ],
            ],
            [
                'kind_code' => 'project',
                'type_code' => 'bo',
                'activity_code' => 'RA-PROJECT-001',
                'title' => 'He thong quan ly NCKH SPNC',
                'start_date' => '2022-01-01',
                'end_date' => '2024-12-31',
                'details_table' => 'project_details',
                'status_code' => 'approved',
                'details' => [
                    'project_code' => 'DA-2022-01',
                    'decision_no' => 'QD-2022-05',
                    'decision_date' => '2022-05-15',
                    'funding' => 1500000000,
                    'start_month' => '2022-01-01',
                    'end_month' => '2024-12-01',
                ],
            ],
            [
                'kind_code' => 'conference',
                'type_code' => 'report',
                'activity_code' => 'RA-CONF-001',
                'title' => 'Hoi thao Khoa hoc Quoc gia 2025 - Ha Noi',
                'start_date' => '2024-05-01',
                'end_date' => '2024-05-30',
                'details_table' => 'conference_details',
                'status_code' => 'approved',
                'details' => [
                    'conference_name' => 'Hoi thao Khoa hoc Quoc gia 2025',
                    'location' => 'Ha Noi',
                    'held_on' => '2024-05-30',
                ],
            ],
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_900',
                'activity_code' => 'RA-PAPER-DRAFT-001',
                'title' => 'Draft paper for declaration',
                'start_date' => '2024-06-01',
                'end_date' => '2024-12-01',
                'details_table' => 'paper_details',
                'status_code' => 'draft',
                'details' => [
                    'journal_name' => 'Draft Journal',
                    'year' => 2024,
                ],
            ],
            [
                'kind_code' => 'paper',
                'type_code' => 'hdgsnn_600',
                'activity_code' => 'RA-PAPER-SUBMITTED-001',
                'title' => 'Bai bao cho cap truong duyet',
                'start_date' => '2024-02-01',
                'end_date' => '2024-09-01',
                'details_table' => 'paper_details',
                'status_code' => 'submitted',
                'details' => [
                    'journal_name' => 'Tap chi Khoa hoc Ung dung',
                    'issn' => '9876-5432',
                    'year' => 2024,
                ],
            ],
        ];

        foreach ($activities as $activity) {
            $kindId = $kindIds[$activity['kind_code']] ?? null;
            if (! $kindId) {
                continue;
            }

            $typeId = $typeIds[$activity['type_code']] ?? null;

            $statusCode = $activity['status_code'] ?? 'approved';
            $statusId = $statusIds[$statusCode] ?? null;
            if (! $statusId) {
                continue;
            }

            DB::table('research_activities')->updateOrInsert(
                ['activity_code' => $activity['activity_code']],
                [
                    'owner_lecturer_id' => $lecturer->id,
                    'kind_id' => $kindId,
                    'type_id' => $typeId,
                    'academic_year_id' => $academicYearId,
                    'status_id' => $statusId,
                    'title' => $activity['title'],
                    'abstract' => null,
                    'start_date' => $activity['start_date'],
                    'end_date' => $activity['end_date'],
                    'quantity' => 1,
                    'submitted_at' => in_array($statusCode, ['approved', 'submitted'], true) ? $now : null,
                    'approved_at' => $statusCode === 'approved' ? $now : null,
                    'total_hours_calc' => null,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $activityId = DB::table('research_activities')
                ->where('activity_code', $activity['activity_code'])
                ->value('id');

            if (! $activityId) {
                continue;
            }

            DB::table($activity['details_table'])->updateOrInsert(
                ['activity_id' => $activityId],
                array_merge($activity['details'], [
                    'activity_id' => $activityId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );

            if ($memberRoleId) {
                DB::table('research_activity_members')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'lecturer_id' => $lecturer->id,
                    ],
                    [
                        'member_role_id' => $memberRoleId,
                        'contribution_share' => 1,
                        'hours_assigned' => 40,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            if ($assistantStageId && $statusCode === 'approved') {
                DB::table('activity_approvals')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'stage_id' => $assistantStageId,
                    ],
                    [
                        'status' => 'approved',
                        'decided_by_user_id' => $user->id,
                        'decided_at' => $now,
                        'note' => 'seeded',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            if ($managerStageId && $statusCode === 'approved') {
                DB::table('activity_approvals')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'stage_id' => $managerStageId,
                    ],
                    [
                        'status' => 'approved',
                        'decided_by_user_id' => $user->id,
                        'decided_at' => $now,
                        'note' => 'seeded',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            if ($assistantStageId && $statusCode === 'submitted') {
                DB::table('activity_approvals')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'stage_id' => $assistantStageId,
                    ],
                    [
                        'status' => 'approved',
                        'decided_by_user_id' => $user->id,
                        'decided_at' => $now,
                        'note' => 'seeded faculty approval',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            if ($managerStageId && $statusCode === 'submitted') {
                DB::table('activity_approvals')->updateOrInsert(
                    [
                        'activity_id' => $activityId,
                        'stage_id' => $managerStageId,
                    ],
                    [
                        'status' => 'pending',
                        'decided_by_user_id' => null,
                        'decided_at' => null,
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}
