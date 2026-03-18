<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectHoursSplitTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerUser;
    private int $ownerLecturerId;
    private int $academicYearId;
    private int $projectKindId;
    private int $typeBoId;
    private int $typeCosoId;
    private int $principalRoleId;
    private int $memberRoleId;
    private int $secretaryRoleId;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('LECTURER', 'web');

        $this->ownerUser = User::factory()->create([
            'email' => 'owner@test.local',
        ]);
        $this->seedCatalogData();
        $this->seedUsers();
    }

    public function test_preview_hours_for_ministry_project_splits_240_and_480_pool_when_has_members(): void
    {
        $member1 = $this->createLecturer('GV002', 'member-1@test.local');
        $member2 = $this->createLecturer('GV003', 'member-2@test.local');

        Sanctum::actingAs($this->ownerUser);
        $response = $this->postJson('/api/lecturer/declarations/projects/preview-hours', [
            'academic_year_id' => $this->academicYearId,
            'type_id' => $this->typeBoId,
            'members' => [
                ['lecturer_id' => $this->ownerLecturerId, 'member_role_id' => $this->principalRoleId],
                ['lecturer_id' => $member1, 'member_role_id' => $this->memberRoleId],
                ['lecturer_id' => $member2, 'member_role_id' => $this->secretaryRoleId],
            ],
        ])->assertOk();

        $data = $response->json('data');
        $this->assertSame('bo', $data['type_code']);
        $this->assertSame(240.0, (float) $data['formula']['leader_hours']);
        $this->assertSame(480.0, (float) $data['formula']['member_pool_hours']);
        $this->assertSame(2, (int) $data['formula']['member_pool_count']);
        $this->assertSame(240.0, (float) $data['formula']['member_pool_each']);
        $this->assertSame(720.0, (float) $data['formula']['total_hours_allocated']);

        $byLecturer = collect($data['members'])->keyBy('lecturer_id');
        $this->assertSame(240.0, (float) $byLecturer[$this->ownerLecturerId]['hours_assigned']);
        $this->assertSame(240.0, (float) $byLecturer[$member1]['hours_assigned']);
        $this->assertSame(240.0, (float) $byLecturer[$member2]['hours_assigned']);
    }

    public function test_preview_hours_for_university_project_splits_360_and_240_pool_when_has_members(): void
    {
        $member = $this->createLecturer('GV004', 'member-3@test.local');

        Sanctum::actingAs($this->ownerUser);
        $response = $this->postJson('/api/lecturer/declarations/projects/preview-hours', [
            'academic_year_id' => $this->academicYearId,
            'type_id' => $this->typeCosoId,
            'members' => [
                ['lecturer_id' => $this->ownerLecturerId, 'member_role_id' => $this->principalRoleId],
                ['lecturer_id' => $member, 'member_role_id' => $this->memberRoleId],
            ],
        ])->assertOk();

        $data = $response->json('data');
        $this->assertSame('coso', $data['type_code']);
        $this->assertSame(360.0, (float) $data['formula']['leader_hours']);
        $this->assertSame(240.0, (float) $data['formula']['member_pool_hours']);
        $this->assertSame(1, (int) $data['formula']['member_pool_count']);
        $this->assertSame(240.0, (float) $data['formula']['member_pool_each']);
        $this->assertSame(600.0, (float) $data['formula']['total_hours_allocated']);
    }

    public function test_preview_falls_back_to_owner_as_leader_when_no_principal_role_selected(): void
    {
        $member = $this->createLecturer('GV005', 'member-4@test.local');

        Sanctum::actingAs($this->ownerUser);
        $response = $this->postJson('/api/lecturer/declarations/projects/preview-hours', [
            'academic_year_id' => $this->academicYearId,
            'type_id' => $this->typeBoId,
            'members' => [
                ['lecturer_id' => $this->ownerLecturerId, 'member_role_id' => $this->memberRoleId],
                ['lecturer_id' => $member, 'member_role_id' => $this->memberRoleId],
            ],
        ])->assertOk();

        $data = $response->json('data');
        $byLecturer = collect($data['members'])->keyBy('lecturer_id');
        $this->assertSame(240.0, (float) $byLecturer[$this->ownerLecturerId]['hours_assigned']);
        $this->assertSame(480.0, (float) $byLecturer[$member]['hours_assigned']);
    }

    public function test_preview_assigns_only_leader_hours_when_no_pool_members(): void
    {
        Sanctum::actingAs($this->ownerUser);
        $response = $this->postJson('/api/lecturer/declarations/projects/preview-hours', [
            'academic_year_id' => $this->academicYearId,
            'type_id' => $this->typeBoId,
            'members' => [
                ['lecturer_id' => $this->ownerLecturerId, 'member_role_id' => $this->principalRoleId],
            ],
        ])->assertOk();

        $data = $response->json('data');
        $this->assertSame(0, (int) $data['formula']['member_pool_count']);
        $this->assertSame(0.0, (float) $data['formula']['member_pool_each']);
        $this->assertSame(720.0, (float) $data['formula']['total_hours_allocated']);
    }

    private function seedCatalogData(): void
    {
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'FIT',
            'name' => 'Khoa CNTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'SE',
            'name' => 'Bộ môn CNPM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->projectKindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'project',
            'name' => 'Đề tài KH&CN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeBoId = DB::table('activity_types')->insertGetId([
            'kind_id' => $this->projectKindId,
            'code' => 'bo',
            'name' => 'Đề tài cấp Bộ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeCosoId = DB::table('activity_types')->insertGetId([
            'kind_id' => $this->projectKindId,
            'code' => 'coso',
            'name' => 'Đề tài cấp cơ sở',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->principalRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'principal',
            'name' => 'Chủ nhiệm',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'member',
            'name' => 'Thành viên',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->secretaryRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'secretary',
            'name' => 'Thư ký',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hour_rules')->insert([
            [
                'kind_id' => $this->projectKindId,
                'type_id' => $this->typeBoId,
                'distribution_strategy' => 'principal_fraction_others_equal',
                'hours_total_per_activity' => 720,
                'hours_per_occurrence' => 480,
                'principal_fraction' => null,
                'others_fraction_total' => null,
                'max_occurrences_per_year' => null,
                'effective_from' => '2025-09-01',
                'effective_to' => '2026-08-31',
                'is_active' => 1,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kind_id' => $this->projectKindId,
                'type_id' => $this->typeCosoId,
                'distribution_strategy' => 'principal_fraction_others_equal',
                'hours_total_per_activity' => 600,
                'hours_per_occurrence' => 240,
                'principal_fraction' => null,
                'others_fraction_total' => null,
                'max_occurrences_per_year' => null,
                'effective_from' => '2025-09-01',
                'effective_to' => '2026-08-31',
                'is_active' => 1,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->ownerLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->ownerUser->id,
            'code' => 'GV001',
            'full_name' => 'Giảng viên A',
            'email' => 'owner@test.local',
            'department_id' => $departmentId,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedUsers(): void
    {
        if (! isset($this->ownerUser)) {
            $this->ownerUser = User::factory()->create([
                'email' => 'owner@test.local',
            ]);
        }
        $this->ownerUser->assignRole('LECTURER');
    }

    private function createLecturer(string $code, string $email): int
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('LECTURER');

        return DB::table('lecturers')->insertGetId([
            'user_id' => $user->id,
            'code' => $code,
            'full_name' => 'Giảng viên ' . $code,
            'email' => $email,
            'department_id' => DB::table('departments')->value('id'),
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
