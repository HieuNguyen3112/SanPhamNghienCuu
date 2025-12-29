<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ResearchLookupSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUniversityApprovalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->seed(ResearchLookupSeeder::class);
    }

    /** @test */
    public function unauthenticated_cannot_access_university_approvals()
    {
        $this->getJson('/api/admin/uni-approvals')->assertStatus(401);
    }

    /** @test */
    public function non_admin_cannot_access_university_approvals()
    {
        $user = User::create([
            'name' => 'Lecturer',
            'email' => 'gv-uni@local.test',
            'password' => 'password',
        ]);
        $user->syncRoles(['GV']);

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/uni-approvals')->assertStatus(403);
    }

    /** @test */
    public function admin_can_list_university_approvals()
    {
        $admin = $this->createAdminUser();
        [$activityId] = $this->seedPendingUniversityActivity();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/uni-approvals');
        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['filters', 'counters'],
        ]);

        $payload = $response->json('data');
        $this->assertNotEmpty($payload);
        $this->assertSame($activityId, $payload[0]['activity_id']);
        $this->assertSame('PENDING_UNIVERSITY_APPROVAL', $payload[0]['approval_status']);
    }

    /** @test */
    public function admin_can_view_university_approval_detail()
    {
        $admin = $this->createAdminUser();
        [$activityId] = $this->seedPendingUniversityActivity();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/uni-approvals/{$activityId}");
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['activity', 'members', 'evidence_files', 'approvals'],
        ]);
        $this->assertNotEmpty($response->json('data.members'));
    }

    /** @test */
    public function admin_can_finalize_university_approval()
    {
        $admin = $this->createAdminUser();
        [$activityId, $memberIds] = $this->seedPendingUniversityActivity();

        Sanctum::actingAs($admin);

        $payload = [
            'members' => [
                ['lecturer_id' => $memberIds[0], 'official_hours' => 120],
                ['lecturer_id' => $memberIds[1], 'official_hours' => 80],
            ],
        ];

        $this->putJson("/api/admin/uni-approvals/{$activityId}/finalize", $payload)
            ->assertOk();

        $approvedStatusId = DB::table('activity_statuses')->where('code', 'approved')->value('id');
        $this->assertSame(
            $approvedStatusId,
            DB::table('research_activities')->where('id', $activityId)->value('status_id')
        );

        $managerStageId = DB::table('approval_stages')->where('code', 'manager')->value('id');
        $this->assertSame(
            'approved',
            DB::table('activity_approvals')
                ->where('activity_id', $activityId)
                ->where('stage_id', $managerStageId)
                ->value('status')
        );

        $hours = DB::table('research_activity_members')
            ->where('activity_id', $activityId)
            ->pluck('hours_assigned', 'lecturer_id')
            ->all();
        $this->assertSame(120.0, (float) $hours[$memberIds[0]]);
        $this->assertSame(80.0, (float) $hours[$memberIds[1]]);
    }

    /** @test */
    public function admin_can_reject_university_approval()
    {
        $admin = $this->createAdminUser();
        [$activityId] = $this->seedPendingUniversityActivity();

        Sanctum::actingAs($admin);

        $payload = [
            'reason_type' => 'WRONG_HOUR_CONVERSION',
            'reason_detail' => 'Sai quy doi gio',
        ];

        $this->putJson("/api/admin/uni-approvals/{$activityId}/reject", $payload)
            ->assertOk();

        $rejectedStatusId = DB::table('activity_statuses')->where('code', 'rejected')->value('id');
        $this->assertSame(
            $rejectedStatusId,
            DB::table('research_activities')->where('id', $activityId)->value('status_id')
        );
    }

    private function createAdminUser(): User
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-uni@local.test',
            'password' => 'password',
        ]);
        $admin->syncRoles(['ADMIN']);

        return $admin;
    }

    private function seedPendingUniversityActivity(): array
    {
        $now = now();
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'F-U',
            'name' => 'Khoa Uni',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'D-U',
            'name' => 'Bo mon Uni',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $lecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV-U01',
            'full_name' => 'Giang Vien U',
            'email' => 'gv-u@local.test',
            'phone' => null,
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $departmentId,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $coAuthorId = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV-U02',
            'full_name' => 'Co Author',
            'email' => 'gv-u2@local.test',
            'phone' => null,
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $departmentId,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $academicYearId = DB::table('academic_years')->value('id');
        $kindId = DB::table('activity_kinds')->where('code', 'paper')->value('id');
        $typeId = DB::table('activity_types')->where('code', 'hdgsnn_600')->value('id');
        $submittedId = DB::table('activity_statuses')->where('code', 'submitted')->value('id');

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'UNI-ACT-001',
            'owner_lecturer_id' => $lecturerId,
            'kind_id' => $kindId,
            'type_id' => $typeId,
            'academic_year_id' => $academicYearId,
            'status_id' => $submittedId,
            'title' => 'Paper Pending University',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => $now,
            'approved_at' => null,
            'total_hours_calc' => null,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $principalRoleId = DB::table('member_roles')->where('code', 'principal')->value('id');
        $memberRoleId = DB::table('member_roles')->where('code', 'member')->value('id');
        DB::table('research_activity_members')->insert([
            [
                'activity_id' => $activityId,
                'lecturer_id' => $lecturerId,
                'member_role_id' => $principalRoleId,
                'contribution_share' => 0.6,
                'hours_assigned' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'activity_id' => $activityId,
                'lecturer_id' => $coAuthorId,
                'member_role_id' => $memberRoleId,
                'contribution_share' => 0.4,
                'hours_assigned' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $assistantStageId = DB::table('approval_stages')->where('code', 'assistant')->value('id');
        $managerStageId = DB::table('approval_stages')->where('code', 'manager')->value('id');
        DB::table('activity_approvals')->insert([
            [
                'activity_id' => $activityId,
                'stage_id' => $assistantStageId,
                'status' => 'approved',
                'decided_by_user_id' => null,
                'decided_at' => $now,
                'note' => 'seeded',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'activity_id' => $activityId,
                'stage_id' => $managerStageId,
                'status' => 'pending',
                'decided_by_user_id' => null,
                'decided_at' => null,
                'note' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        return [$activityId, [$lecturerId, $coAuthorId]];
    }
}
