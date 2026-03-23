<?php

namespace Tests\Feature;

use App\Notifications\WorkflowDatabaseNotification;
use App\Notifications\ParticipationInvitationNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

    public function test_faculty_rejection_notifies_owner_and_all_accepted_members(): void
    {
        Notification::fake();

        [$activityId] = $this->createActivityWithMember('pending_faculty_review', 'accepted');

        Sanctum::actingAs($this->facultyUser);

        $this->putJson("/api/faculty/works/approvals/{$activityId}/reject", [
            'reason_type' => 'MISSING_EVIDENCE',
            'reason_detail' => 'Cần bổ sung tệp PDF.',
        ])->assertOk();

        $expectedNote = 'MISSING_EVIDENCE: Cần bổ sung tệp PDF.';

        Notification::assertSentTo(
            [$this->ownerUser, $this->memberUser],
            WorkflowDatabaseNotification::class,
            function (WorkflowDatabaseNotification $notification, array $channels) use ($activityId, $expectedNote) {
                $payload = $notification->toArray(null);

                return $channels === ['database']
                    && ($payload['event_key'] ?? null) === 'work_rejected'
                    && ($payload['activity_id'] ?? null) === $activityId
                    && ($payload['rejection_note'] ?? null) === $expectedNote
                    && ($payload['target_url'] ?? null) === "/works/personal?tab=rejected&activity_id={$activityId}";
            }
        );
    }

    public function test_faculty_reject_requires_reason_detail_for_other(): void
    {
        [$activityId] = $this->createActivityWithMember('pending_faculty_review', 'accepted');

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/reject", [
            'reason_type' => 'OTHER',
            'reason_detail' => '   ',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason_detail']);

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'status_id' => $this->statusId('pending_faculty_review'),
        ]);
    }

    public function test_faculty_rejection_stays_out_of_participation_notifications_and_points_to_my_works(): void
    {
        [$activityId] = $this->createActivityWithMember('pending_faculty_review', 'accepted');

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/reject", [
            'reason_type' => 'MISSING_EVIDENCE',
            'reason_detail' => 'Bo sung tep PDF.',
        ])->assertOk();

        Sanctum::actingAs($this->memberUser);

        $this->getJson('/api/lecturer/participation-requests?status=REJECTED')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->getJson('/api/lecturer/works/my?status=rejected')
            ->assertOk()
            ->assertJsonPath('data.items.0.activity_id', $activityId);
    }

    public function test_participation_notifications_reject_reversed_date_range(): void
    {
        Sanctum::actingAs($this->memberUser);

        $this->getJson('/api/lecturer/participation-requests?from=2025-05-10&to=2025-05-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    public function test_member_confirm_updates_status_and_marks_invitation_notification_read(): void
    {
        [$activityId, $memberRowId] = $this->createActivityWithMember('pending_member_confirm', 'pending');

        DB::table('research_activity_members')
            ->where('id', $memberRowId)
            ->update([
                'confirmation_status' => 'PENDING',
                'responded_at' => null,
                'confirmation_note' => null,
                'updated_at' => now(),
            ]);

        $this->memberUser->notify(new ParticipationInvitationNotification([
            'event_key' => 'participation_invitation',
            'title' => 'Yeu cau xac nhan tham gia cong trinh',
            'message' => 'Vui long xac nhan tham gia cong trinh.',
            'activity_id' => $activityId,
            'invitation_id' => $memberRowId,
            'action_route' => '/declarations/participatier',
        ]));

        Sanctum::actingAs($this->memberUser);

        $response = $this->postJson("/api/lecturer/participation-requests/{$memberRowId}/confirm")
            ->assertOk();

        $this->assertSame('ACCEPTED', $response->json('data.status'));

        $this->assertDatabaseHas('research_activity_members', [
            'id' => $memberRowId,
            'confirmation_status' => 'accepted',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->memberUser->id,
            'type' => ParticipationInvitationNotification::class,
        ]);

        $unreadCount = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $this->memberUser->id)
            ->where('type', ParticipationInvitationNotification::class)
            ->whereNull('read_at')
            ->count();

        $this->assertSame(0, $unreadCount);
    }

    public function test_accepted_member_can_rework_faculty_rejected_activity_and_see_same_reason(): void
    {
        [$activityId] = $this->createActivityWithMember('pending_faculty_review', 'accepted');

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/reject", [
            'reason_type' => 'MISSING_EVIDENCE',
            'reason_detail' => 'Cần bổ sung tệp PDF.',
        ])->assertOk();

        Sanctum::actingAs($this->memberUser);

        $indexResponse = $this->getJson('/api/lecturer/works/my?status=rejected')
            ->assertOk();

        $memberRow = collect($indexResponse->json('data.items'))
            ->firstWhere('activity_id', $activityId);

        $this->assertNotNull($memberRow);
        $this->assertSame('rejected', $memberRow['status_code'] ?? null);
        $this->assertTrue((bool) data_get($memberRow, 'actions.can_edit'));
        $this->assertTrue((bool) data_get($memberRow, 'actions.can_submit'));
        $this->assertFalse((bool) data_get($memberRow, 'actions.can_reinvite'));

        $this->getJson("/api/lecturer/works/my/{$activityId}")
            ->assertOk()
            ->assertJsonPath('data.rejection_note', 'MISSING_EVIDENCE: Cần bổ sung tệp PDF.')
            ->assertJsonPath('data.actions.can_edit', true)
            ->assertJsonPath('data.actions.can_submit', true);

        $this->getJson("/api/research-activities/{$activityId}")
            ->assertOk()
            ->assertJsonPath('data.activity.id', $activityId);

        $this->putJson("/api/research-activities/{$activityId}", [
            'title' => 'Cong trinh test da duoc thanh vien cap nhat',
        ])->assertOk();

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'title' => 'Cong trinh test da duoc thanh vien cap nhat',
        ]);
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

        foreach (
            [
                'draft' => 'Draft',
                'pending_member_confirm' => 'Cho thanh vien xac nhan',
                'member_rejected' => 'Thanh vien tu choi',
                'pending_faculty_review' => 'Cho khoa duyet',
                'approved' => 'Da duyet',
                'rejected' => 'Bi tu choi',
            ] as $code => $name
        ) {
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
