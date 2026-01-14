<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResearchActivitySubmitAuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function submitting_activity_creates_hours_submitted_audit_log(): void
    {
        $user = User::factory()->create();

        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'CNTT',
            'name' => 'Khoa Cong nghe Thong tin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'IT',
            'name' => 'Cong nghe Thong tin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $user->id,
            'code' => 'GV-TEST',
            'full_name' => 'Giang vien Test',
            'email' => 'lecturer@test.local',
            'phone' => '0900000000',
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $academicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $draftStatusId = DB::table('activity_statuses')->insertGetId([
            'code' => 'draft',
            'name' => 'Draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_statuses')->insert([
            'code' => 'submitted',
            'name' => 'Submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'paper',
            'name' => 'Paper',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'principal',
            'name' => 'Principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-TEST-001',
            'owner_lecturer_id' => $lecturerId,
            'kind_id' => $kindId,
            'type_id' => null,
            'academic_year_id' => $academicYearId,
            'status_id' => $draftStatusId,
            'title' => 'De tai test',
            'abstract' => null,
            'start_date' => '2024-10-01',
            'end_date' => '2024-12-01',
            'quantity' => 1,
            'submitted_at' => null,
            'approved_at' => null,
            'total_hours_calc' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $lecturerId,
            'member_role_id' => $memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/research-activities/' . $activityId . '/submit')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action_code' => 'HOURS_SUBMITTED',
            'actor_user_id' => $user->id,
            'result_status' => 'success',
            'target_type' => 'research_activity',
            'target_id' => (string) $activityId,
        ]);
    }
}
