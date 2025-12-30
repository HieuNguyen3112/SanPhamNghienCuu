<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResearchLookupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $academicYears = [
            [
                'code' => '2024-2025',
                'start_date' => '2024-09-01',
                'end_date' => '2025-08-31',
                'is_active' => true,
            ],
        ];

        foreach ($academicYears as $year) {
            DB::table('academic_years')->updateOrInsert(
                ['code' => $year['code']],
                array_merge($year, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $kinds = [
            ['code' => 'paper', 'name' => 'Paper'],
            ['code' => 'book', 'name' => 'Book'],
            ['code' => 'project', 'name' => 'Project'],
            ['code' => 'conference', 'name' => 'Conference'],
        ];

        foreach ($kinds as $kind) {
            DB::table('activity_kinds')->updateOrInsert(
                ['code' => $kind['code']],
                array_merge($kind, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $kindIds = DB::table('activity_kinds')
            ->whereIn('code', array_column($kinds, 'code'))
            ->pluck('id', 'code')
            ->all();

        $types = [
            ['code' => 'hdgsnn_900', 'name' => 'HDGSNN 900', 'kind_code' => 'paper'],
            ['code' => 'hdgsnn_600', 'name' => 'HDGSNN 600', 'kind_code' => 'paper'],
            ['code' => 'hdgsnn_300', 'name' => 'HDGSNN 300', 'kind_code' => 'paper'],
            ['code' => 'textbook', 'name' => 'Textbook', 'kind_code' => 'book'],
            ['code' => 'reference', 'name' => 'Reference', 'kind_code' => 'book'],
            ['code' => 'bo', 'name' => 'Project - Ministry', 'kind_code' => 'project'],
            ['code' => 'coso', 'name' => 'Project - Institution', 'kind_code' => 'project'],
            ['code' => 'report', 'name' => 'Conference Report', 'kind_code' => 'conference'],
            ['code' => 'attend', 'name' => 'Conference Attend', 'kind_code' => 'conference'],
        ];

        foreach ($types as $type) {
            $kindId = $kindIds[$type['kind_code']] ?? null;
            if (! $kindId) {
                continue;
            }

            DB::table('activity_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'kind_id' => $kindId,
                    'name' => $type['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $statuses = [
            ['code' => 'draft', 'name' => 'Draft'],
            ['code' => 'submitted', 'name' => 'Submitted'],
            ['code' => 'approved', 'name' => 'Approved'],
            ['code' => 'rejected', 'name' => 'Rejected'],
        ];

        foreach ($statuses as $status) {
            DB::table('activity_statuses')->updateOrInsert(
                ['code' => $status['code']],
                array_merge($status, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $memberRoles = [
            ['code' => 'principal', 'name' => 'Principal'],
            ['code' => 'member', 'name' => 'Member'],
            ['code' => 'secretary', 'name' => 'Secretary'],
            ['code' => 'corresponding_author', 'name' => 'Corresponding Author'],
            ['code' => 'coauthor', 'name' => 'Co-author'],
            ['code' => 'chief_editor', 'name' => 'Chief Editor'],
        ];

        foreach ($memberRoles as $role) {
            DB::table('member_roles')->updateOrInsert(
                ['code' => $role['code']],
                array_merge($role, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $approvalStages = [
            ['code' => 'assistant', 'name' => 'Assistant', 'order_no' => 1],
            ['code' => 'manager', 'name' => 'Manager', 'order_no' => 2],
            ['code' => 'hours', 'name' => 'Hours Approval', 'order_no' => 3],
        ];

        foreach ($approvalStages as $stage) {
            DB::table('approval_stages')->updateOrInsert(
                ['code' => $stage['code']],
                array_merge($stage, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $evidenceTypes = [
            ['code' => 'content', 'name' => 'Content'],
            ['code' => 'cover', 'name' => 'Cover'],
            ['code' => 'toc', 'name' => 'Table of Contents'],
            ['code' => 'acceptance_decision', 'name' => 'Acceptance Decision'],
            ['code' => 'publication_decision', 'name' => 'Publication Decision'],
        ];

        foreach ($evidenceTypes as $type) {
            DB::table('evidence_file_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
