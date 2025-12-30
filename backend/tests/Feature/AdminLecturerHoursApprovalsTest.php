<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LecturerHourApprovalDemoSeeder;
use Database\Seeders\ResearchLookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLecturerHoursApprovalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('ADMIN', 'web');
        Role::findOrCreate('QL', 'web');
    }

    private function seedBase(): array
    {
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'CNTT',
            'name' => 'Khoa Công nghệ Thông tin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'IT',
            'name' => 'Công nghệ Thông tin',
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

        $statusApprovedId = DB::table('activity_statuses')->insertGetId([
            'code' => 'approved',
            'name' => 'Approved',
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

        $stageId = DB::table('approval_stages')->insertGetId([
            'code' => 'hours',
            'name' => 'Hours Approval',
            'order_no' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV-001',
            'full_name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'phone' => '090000001',
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-001',
            'owner_lecturer_id' => $lecturerId,
            'kind_id' => $kindId,
            'type_id' => null,
            'academic_year_id' => $academicYearId,
            'status_id' => $statusApprovedId,
            'title' => 'Approved Work',
            'abstract' => null,
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-01',
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => 40,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $lecturerId,
            'member_role_id' => $memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_approvals')->insert([
            'activity_id' => $activityId,
            'stage_id' => $stageId,
            'status' => 'pending',
            'decided_by_user_id' => null,
            'decided_at' => null,
            'note' => null,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        return [
            'faculty_id' => $facultyId,
            'department_id' => $departmentId,
            'academic_year_id' => $academicYearId,
            'lecturer_id' => $lecturerId,
            'activity_id' => $activityId,
            'stage_id' => $stageId,
        ];
    }

    private function seedDemoApprovals(): void
    {
        $this->seed([ResearchLookupSeeder::class, LecturerHourApprovalDemoSeeder::class]);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_guest_cannot_list(): void
    {
        $this->getJson('/api/admin/hours/approvals')
            ->assertStatus(401);
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/hours/approvals')
            ->assertStatus(403);
    }

    public function test_admin_can_list_with_filters(): void
    {
        $seed = $this->seedBase();
        $this->actingAsAdmin();

        $this->getJson('/api/admin/hours/approvals?faculty_id=' . $seed['faculty_id'] . '&status=pending')
            ->assertStatus(200)
            ->assertJsonPath('data.items.0.lecturer_id', $seed['lecturer_id'])
            ->assertJsonPath('data.items.0.status_code', 'pending');
    }

    public function test_admin_can_view_detail(): void
    {
        $seed = $this->seedBase();
        $this->actingAsAdmin();

        $this->getJson('/api/admin/hours/approvals/' . $seed['lecturer_id'])
            ->assertStatus(200)
            ->assertJsonPath('data.request_id', $seed['lecturer_id'])
            ->assertJsonPath('data.items.0.activity_id', $seed['activity_id']);
    }

    public function test_admin_can_list_demo_seed(): void
    {
        $this->seedDemoApprovals();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/hours/approvals')
            ->assertStatus(200);

        $this->assertNotEmpty($response->json('data.items'));
    }

    public function test_filter_pending_only(): void
    {
        $this->seedDemoApprovals();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/hours/approvals?status=pending')
            ->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertSame('pending', $item['status_code']);
        }
    }

    public function test_keyword_search_finds_one_lecturer(): void
    {
        $this->seedDemoApprovals();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/hours/approvals?keyword=GV-002')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data.items'));
        $this->assertSame('GV-002', $response->json('data.items.0.lecturer_code'));
    }

    public function test_approve_requires_pending(): void
    {
        $seed = $this->seedBase();
        DB::table('activity_approvals')
            ->where('activity_id', $seed['activity_id'])
            ->update(['status' => 'approved']);

        $this->actingAsAdmin();

        $this->putJson('/api/admin/hours/approvals/' . $seed['lecturer_id'] . '/approve')
            ->assertStatus(422);
    }

    public function test_admin_can_approve_pending(): void
    {
        $seed = $this->seedBase();
        $this->actingAsAdmin();

        $this->putJson('/api/admin/hours/approvals/' . $seed['lecturer_id'] . '/approve')
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $seed['activity_id'],
            'stage_id' => $seed['stage_id'],
            'status' => 'approved',
        ]);
    }

    public function test_reject_requires_pending(): void
    {
        $seed = $this->seedBase();
        DB::table('activity_approvals')
            ->where('activity_id', $seed['activity_id'])
            ->update(['status' => 'approved']);

        $this->actingAsAdmin();

        $this->putJson('/api/admin/hours/approvals/' . $seed['lecturer_id'] . '/reject', [
            'reason_code' => 'missing_evidence',
        ])->assertStatus(422);
    }

    public function test_reject_requires_reason_detail_for_other(): void
    {
        $seed = $this->seedBase();
        $this->actingAsAdmin();

        $this->putJson('/api/admin/hours/approvals/' . $seed['lecturer_id'] . '/reject', [
            'reason_code' => 'other',
        ])->assertStatus(422);
    }

    public function test_admin_can_reject_pending(): void
    {
        $seed = $this->seedBase();
        $this->actingAsAdmin();

        $this->putJson('/api/admin/hours/approvals/' . $seed['lecturer_id'] . '/reject', [
            'reason_code' => 'missing_evidence',
            'reason_detail' => 'Thiếu minh chứng',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $seed['activity_id'],
            'stage_id' => $seed['stage_id'],
            'status' => 'rejected',
        ]);
    }
}