<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResearchWorkflowMemberRejectionTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerUser;
    private User $memberUser;
    private User $facultyUser;

    private int $ownerLecturerId;
    private int $memberLecturerId;
    private int $facultyLecturerId;

    private int $facultyId;
    private int $departmentId;
    private int $academicYearId;

    private int $paperKindId;
    private int $paperTypeId;
    private int $principalRoleId;
    private int $memberRoleId;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('LECTURER', 'web');
        Role::findOrCreate('DEPARTMENT_BOARD', 'web');

        $this->seedLookups();
        $this->seedUsersAndLecturers();
    }

    public function test_member_reject_moves_activity_to_member_rejected(): void
    {
        [$activityId, $memberRowId] = $this->createActivityWithMember('pending_member_confirm', 'pending');

        Sanctum::actingAs($this->memberUser);

        $this->postJson("/api/lecturer/participation-requests/{$memberRowId}/reject", [
            'reason' => 'Khong co du lieu tham gia.',
        ])->assertOk();

        $this->assertDatabaseHas('research_activity_members', [
            'id' => $memberRowId,
            'confirmation_status' => 'rejected',
            'confirmation_note' => 'Khong co du lieu tham gia.',
        ]);

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'status_id' => $this->statusId('member_rejected'),
        ]);
    }

    public function test_owner_can_edit_members_when_activity_is_member_rejected(): void
    {
        [$activityId, $memberRowId] = $this->createActivityWithMember('pending_member_confirm', 'pending');

        Sanctum::actingAs($this->memberUser);
        $this->postJson("/api/lecturer/participation-requests/{$memberRowId}/reject", [
            'reason' => 'Khong tham gia.',
        ])->assertOk();

        Sanctum::actingAs($this->ownerUser);
        $this->putJson("/api/research-activities/{$activityId}/members", [
            'items' => [
                [
                    'lecturer_id' => $this->ownerLecturerId,
                    'member_role_id' => $this->principalRoleId,
                    'contribution_share' => 1,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('research_activity_members', [
            'id' => $memberRowId,
        ]);
    }

    public function test_owner_can_submit_after_removing_rejected_member(): void
    {
        [$activityId, $memberRowId] = $this->createActivityWithMember('pending_member_confirm', 'pending');

        Sanctum::actingAs($this->memberUser);
        $this->postJson("/api/lecturer/participation-requests/{$memberRowId}/reject", [
            'reason' => 'Khong tham gia.',
        ])->assertOk();

        Sanctum::actingAs($this->ownerUser);
        $this->putJson("/api/research-activities/{$activityId}/members", [
            'items' => [
                [
                    'lecturer_id' => $this->ownerLecturerId,
                    'member_role_id' => $this->principalRoleId,
                    'contribution_share' => 1,
                ],
            ],
        ])->assertOk();

        $this->postJson("/api/research-activities/{$activityId}/submit")
            ->assertOk()
            ->assertJsonPath('workflow.status_code', 'pending_faculty_review');

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'status_id' => $this->statusId('pending_faculty_review'),
        ]);
    }

    public function test_submit_to_faculty_creates_pending_assistant_approval_record(): void
    {
        [$activityId] = $this->createActivityWithMember('draft', 'accepted');

        Sanctum::actingAs($this->ownerUser);
        $this->postJson("/api/research-activities/{$activityId}/submit")
            ->assertOk()
            ->assertJsonPath('workflow.status_code', 'pending_faculty_review');

        $assistantStageId = (int) DB::table('approval_stages')
            ->where('code', 'assistant')
            ->value('id');

        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $activityId,
            'stage_id' => $assistantStageId,
            'status' => 'pending',
        ]);
    }

    public function test_owner_can_reinvite_rejected_member(): void
    {
        [$activityId, $memberRowId] = $this->createActivityWithMember('pending_member_confirm', 'pending');

        Sanctum::actingAs($this->memberUser);
        $this->postJson("/api/lecturer/participation-requests/{$memberRowId}/reject", [
            'reason' => 'Khong tham gia.',
        ])->assertOk();

        Sanctum::actingAs($this->ownerUser);
        $this->postJson("/api/research-activities/{$activityId}/members/{$memberRowId}/reinvite")
            ->assertOk()
            ->assertJsonPath('workflow.status_code', 'pending_member_confirm')
            ->assertJsonPath('member.confirmation_status', 'pending');

        $this->assertDatabaseHas('research_activity_members', [
            'id' => $memberRowId,
            'confirmation_status' => 'pending',
            'confirmation_note' => null,
        ]);

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'status_id' => $this->statusId('pending_member_confirm'),
        ]);
    }

    public function test_faculty_approval_list_excludes_member_rejected_activity(): void
    {
        [$memberRejectedActivityId] = $this->createActivityWithMember('member_rejected', 'rejected');
        [$pendingFacultyActivityId] = $this->createActivityWithMember('pending_faculty_review', 'accepted');

        Sanctum::actingAs($this->facultyUser);

        $response = $this->getJson('/api/faculty/works/approvals?status=pending')
            ->assertOk();

        $items = $response->json('data');
        $activityIds = collect($items)->pluck('activity_id')->all();

        $this->assertContains($pendingFacultyActivityId, $activityIds);
        $this->assertNotContains($memberRejectedActivityId, $activityIds);
    }

    public function test_faculty_pending_visibility_depends_on_academic_year_filter(): void
    {
        $otherAcademicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        [$pendingFacultyActivityId] = $this->createActivityWithMember('pending_faculty_review', 'accepted');
        DB::table('research_activities')
            ->where('id', $pendingFacultyActivityId)
            ->update([
                'academic_year_id' => $otherAcademicYearId,
                'updated_at' => now(),
            ]);

        Sanctum::actingAs($this->facultyUser);

        $unfiltered = $this->getJson('/api/faculty/works/approvals?status=pending')
            ->assertOk();
        $unfilteredIds = collect($unfiltered->json('data'))->pluck('activity_id')->all();
        $this->assertContains($pendingFacultyActivityId, $unfilteredIds);

        $filteredActiveYear = $this->getJson('/api/faculty/works/approvals?status=pending&academic_year_id=' . $this->academicYearId)
            ->assertOk();
        $filteredActiveYearIds = collect($filteredActiveYear->json('data'))->pluck('activity_id')->all();
        $this->assertNotContains($pendingFacultyActivityId, $filteredActiveYearIds);
    }

    private function seedLookups(): void
    {
        $this->facultyId = DB::table('faculties')->insertGetId([
            'code' => 'F01',
            'name' => 'Khoa CNTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $this->facultyId,
            'code' => 'D01',
            'name' => 'Bo mon PM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            'draft' => 'Draft',
            'pending_member_confirm' => 'Cho thanh vien xac nhan',
            'member_rejected' => 'Thanh vien tu choi',
            'pending_faculty_review' => 'Cho khoa duyet',
            'approved' => 'Da duyet',
            'rejected' => 'Bi tu choi',
        ] as $code => $name) {
            DB::table('activity_statuses')->insert([
                'code' => $code,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('approval_stages')->insert([
            [
                'code' => 'assistant',
                'name' => 'Cap khoa',
                'order_no' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'manager',
                'name' => 'Cap truong',
                'order_no' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->paperKindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'paper',
            'name' => 'Bai bao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->paperTypeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $this->paperKindId,
            'code' => 'hdgsnn_300',
            'name' => 'ISSN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->principalRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'principal',
            'name' => 'Chu nhiem',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'member',
            'name' => 'Thanh vien',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedUsersAndLecturers(): void
    {
        $this->ownerUser = User::factory()->create(['email' => 'owner@test.local']);
        $this->ownerUser->assignRole('LECTURER');

        $this->memberUser = User::factory()->create(['email' => 'member@test.local']);
        $this->memberUser->assignRole('LECTURER');

        $this->facultyUser = User::factory()->create(['email' => 'faculty@test.local']);
        $this->facultyUser->assignRole('DEPARTMENT_BOARD');

        $this->ownerLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->ownerUser->id,
            'code' => 'GV001',
            'full_name' => 'Chu so huu',
            'email' => 'owner-lecturer@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->memberLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->memberUser->id,
            'code' => 'GV002',
            'full_name' => 'Thanh vien',
            'email' => 'member-lecturer@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->facultyLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->facultyUser->id,
            'code' => 'GV003',
            'full_name' => 'Can bo khoa',
            'email' => 'faculty-lecturer@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createActivityWithMember(string $activityStatusCode, string $memberStatus): array
    {
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-' . strtoupper(uniqid()),
            'owner_lecturer_id' => $this->ownerLecturerId,
            'kind_id' => $this->paperKindId,
            'type_id' => $this->paperTypeId,
            'academic_year_id' => $this->academicYearId,
            'status_id' => $this->statusId($activityStatusCode),
            'title' => 'Cong trinh test',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => null,
            'approved_at' => null,
            'total_hours_calc' => 20,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->ownerLecturerId,
            'member_role_id' => $this->principalRoleId,
            'contribution_share' => 0.6,
            'hours_assigned' => 12,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberRowId = DB::table('research_activity_members')->insertGetId([
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 0.4,
            'hours_assigned' => 8,
            'confirmation_status' => $memberStatus,
            'responded_at' => $memberStatus === 'pending' ? null : now(),
            'confirmation_note' => $memberStatus === 'rejected' ? 'Da tu choi o seed.' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('paper_details')->insert([
            'activity_id' => $activityId,
            'journal_name' => 'Tap chi test',
            'issn' => '1234-5678',
            'doi' => null,
            'article_url' => null,
            'volume' => null,
            'issue' => null,
            'page_start' => null,
            'page_end' => null,
            'year' => 2025,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$activityId, $memberRowId];
    }

    private function statusId(string $code): int
    {
        return (int) DB::table('activity_statuses')->where('code', $code)->value('id');
    }
}
