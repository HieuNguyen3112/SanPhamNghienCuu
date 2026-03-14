<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Evidence\ResearchEvidenceStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HoursWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $lecturerUser;
    private User $memberUser;
    private User $facultyUser;
    private User $scienceOfficeUser;

    private int $lecturerId;
    private int $memberLecturerId;
    private int $facultyLecturerId;
    private int $scienceOfficeLecturerId;
    private int $departmentId;
    private int $facultyId;
    private int $academicYearId;
    private int $kindId;
    private int $typeId;
    private int $memberRoleId;
    private int $hoursStageId;
    private int $evidenceFileTypeId;
    private int $hourRuleId;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('LECTURER', 'web');
        Role::findOrCreate('DEPARTMENT_BOARD', 'web');
        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $this->seedBaseData();
        $this->seedUsers();
    }

    public function test_work_must_be_faculty_approved_to_submit_hours(): void
    {
        $activityId = $this->createActivity('pending_faculty_review');

        Sanctum::actingAs($this->lecturerUser);

        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'WORK_NOT_FACULTY_APPROVED')
            ->assertJsonPath('invalid_activity_ids.0', $activityId);

        $this->assertDatabaseMissing('activity_approvals', [
            'activity_id' => $activityId,
            'stage_id' => $this->hoursStageId,
        ]);
    }

    public function test_submit_creates_pending_hours_approval(): void
    {
        $activityId = $this->createActivity('approved');
        $this->attachEvidenceRecord($activityId, $this->lecturerUser->id);

        Sanctum::actingAs($this->lecturerUser);

        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])
            ->assertOk()
            ->assertJsonPath('data.submitted_count', 1);

        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $activityId,
            'stage_id' => $this->hoursStageId,
            'status' => 'pending',
        ]);
    }

    public function test_faculty_approve_updates_lecturer_view_to_approved(): void
    {
        $activityId = $this->createActivity('approved');
        $this->attachEvidenceRecord($activityId, $this->lecturerUser->id);

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])->assertOk();

        Sanctum::actingAs($this->facultyUser);
        $this->putJson('/api/faculty/hours/approvals/' . $this->lecturerId . '/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        Sanctum::actingAs($this->lecturerUser);
        $response = $this->getJson('/api/lecturer/hours/calculate')
            ->assertOk();

        $this->assertSame('hours_approved', $response->json('data.items.0.hours_request_state'));
        $this->assertDatabaseHas('lecturer_yearly_hours', [
            'lecturer_id' => $this->lecturerId,
            'academic_year_id' => $this->academicYearId,
            'hours_total' => 300.0,
        ]);
    }

    public function test_hours_labels_are_returned_in_proper_utf8_vietnamese(): void
    {
        $notSubmittedActivityId = $this->createActivity('approved');
        $approvedActivityId = $this->createActivity('approved');

        DB::table('activity_approvals')->insert([
            'activity_id' => $approvedActivityId,
            'stage_id' => $this->hoursStageId,
            'status' => 'approved',
            'decided_by_user_id' => $this->facultyUser->id,
            'decided_at' => now(),
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);

        $notSubmitted = $this->getJson('/api/lecturer/hours/calculate/' . $notSubmittedActivityId)
            ->assertOk();
        $approved = $this->getJson('/api/lecturer/hours/calculate/' . $approvedActivityId)
            ->assertOk();

        $notSubmittedText = (string) $notSubmitted->json('data.next_action_text');
        $approvedText = (string) $approved->json('data.next_action_text');

        $this->assertSame(
            'Tải tối thiểu 1 minh chứng PDF và bấm Gửi duyệt giờ',
            $notSubmittedText
        );
        $this->assertSame('Đã duyệt giờ', $approvedText);
        $this->assertStringNotContainsString("\u{00C3}", $notSubmittedText);
        $this->assertStringNotContainsString("\u{00C2}", $notSubmittedText);
        $this->assertStringNotContainsString("\u{00C3}", $approvedText);
        $this->assertStringNotContainsString("\u{00C2}", $approvedText);
    }

    public function test_faculty_can_approve_selected_hours_items_only(): void
    {
        $activityA = $this->createActivity('approved');
        $activityB = $this->createActivity('approved');

        $this->attachEvidenceRecord($activityA, $this->lecturerUser->id);
        $this->attachEvidenceRecord($activityB, $this->lecturerUser->id);

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityA, $activityB],
        ])->assertOk();

        Sanctum::actingAs($this->facultyUser);
        $response = $this->putJson('/api/faculty/hours/approvals/' . $this->lecturerId . '/approve', [
            'activity_ids' => [$activityA],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $items = collect($response->json('data.items'))->keyBy('activity_id');
        $this->assertSame('approved', $items[$activityA]['approval_status']);
        $this->assertSame('pending', $items[$activityB]['approval_status']);

        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $activityA,
            'stage_id' => $this->hoursStageId,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $activityB,
            'stage_id' => $this->hoursStageId,
            'status' => 'pending',
        ]);
    }

    public function test_hours_calculate_defaults_to_current_september_august_academic_year(): void
    {
        Carbon::setTestNow('2026-02-24 09:00:00');

        DB::table('academic_years')
            ->where('id', $this->academicYearId)
            ->update([
                'is_active' => 0,
                'updated_at' => now(),
            ]);

        $legacyAcademicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentActivityId = $this->createActivity('approved', 20, null, null, $this->academicYearId);
        $legacyActivityId = $this->createActivity('approved', 20, null, null, $legacyAcademicYearId);

        $this->attachEvidenceRecord($currentActivityId, $this->lecturerUser->id);
        $this->attachEvidenceRecord($legacyActivityId, $this->lecturerUser->id);

        Sanctum::actingAs($this->lecturerUser);
        $response = $this->getJson('/api/lecturer/hours/calculate')->assertOk();

        $returnedActivityIds = collect($response->json('data.items'))
            ->pluck('activity_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertContains($currentActivityId, $returnedActivityIds);
        $this->assertNotContains($legacyActivityId, $returnedActivityIds);
        $this->assertSame('2025-2026', $response->json('data.academic_year.code'));

        Carbon::setTestNow();
    }

    public function test_faculty_reject_returns_reason_and_lecturer_can_resubmit(): void
    {
        $activityId = $this->createActivity('approved');
        $this->attachEvidenceRecord($activityId, $this->lecturerUser->id);

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])->assertOk();

        Sanctum::actingAs($this->facultyUser);
        $this->putJson('/api/faculty/hours/approvals/' . $this->lecturerId . '/reject', [
            'reason_code' => 'missing_evidence',
            'reason_detail' => 'Missing acceptance document.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.note_from_faculty', 'Missing acceptance document.');

        Sanctum::actingAs($this->lecturerUser);
        $this->getJson('/api/lecturer/hours/calculate')
            ->assertOk()
            ->assertJsonPath('data.items.0.hours_request_state', 'hours_rejected')
            ->assertJsonPath('data.items.0.hours_rejection_reason', 'Missing acceptance document.');

        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])
            ->assertOk()
            ->assertJsonPath('data.submitted_count', 1);

        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $activityId,
            'stage_id' => $this->hoursStageId,
            'status' => 'pending',
            'note' => null,
        ]);
    }

    public function test_faculty_can_reject_selected_hours_items_only(): void
    {
        $activityA = $this->createActivity('approved');
        $activityB = $this->createActivity('approved');

        $this->attachEvidenceRecord($activityA, $this->lecturerUser->id);
        $this->attachEvidenceRecord($activityB, $this->lecturerUser->id);

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityA, $activityB],
        ])->assertOk();

        Sanctum::actingAs($this->facultyUser);
        $response = $this->putJson('/api/faculty/hours/approvals/' . $this->lecturerId . '/reject', [
            'reason_code' => 'hours_not_reasonable',
            'reason_detail' => 'Cần rà soát lại minh chứng.',
            'activity_ids' => [$activityA],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $items = collect($response->json('data.items'))->keyBy('activity_id');
        $this->assertSame('rejected', $items[$activityA]['approval_status']);
        $this->assertSame('pending', $items[$activityB]['approval_status']);

        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $activityA,
            'stage_id' => $this->hoursStageId,
            'status' => 'rejected',
        ]);
        $this->assertDatabaseHas('activity_approvals', [
            'activity_id' => $activityB,
            'stage_id' => $this->hoursStageId,
            'status' => 'pending',
        ]);
    }

    public function test_lecturer_can_upload_and_list_hours_evidence(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $activityId = $this->createActivity('approved');

        Sanctum::actingAs($this->lecturerUser);

        $upload = $this->postJson(
            "/api/lecturer/hours/calculate/{$activityId}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => UploadedFile::fake()->create('acceptance-minutes.pdf', 120, 'application/pdf'),
            ]
        )->assertCreated();

        $evidenceId = (int) $upload->json('data.id');
        $storedPath = DB::table('evidence_files')->where('id', $evidenceId)->value('path');

        $this->assertDatabaseHas('evidence_files', [
            'id' => $evidenceId,
            'activity_id' => $activityId,
            'file_type_id' => $this->evidenceFileTypeId,
            'uploaded_by_user_id' => $this->lecturerUser->id,
        ]);

        Storage::disk('local')->assertExists($storedPath);

        $this->getJson("/api/lecturer/hours/calculate/{$activityId}/evidence")
            ->assertOk()
            ->assertJsonPath('data.0.id', $evidenceId)
            ->assertJsonPath('data.0.file_type_id', $this->evidenceFileTypeId)
            ->assertJsonPath('data.0.download_url', url("/api/lecturer/hours/evidence/{$evidenceId}/download"))
            ->assertJsonMissingPath('data.0.preview_url');

        $this->getJson('/api/lecturer/hours/calculate')
            ->assertOk()
            ->assertJsonPath('data.items.0.evidence_count', 1)
            ->assertJsonPath('data.items.0.valid_evidence_count', 1)
            ->assertJsonPath('data.items.0.can_submit_hours', true);

        $this->get("/api/lecturer/hours/evidence/{$evidenceId}/download")
            ->assertOk();

        $this->deleteJson("/api/lecturer/hours/evidence/{$evidenceId}")
            ->assertOk()
            ->assertJsonPath('data.evidence_id', $evidenceId);

        $this->assertDatabaseMissing('evidence_files', ['id' => $evidenceId]);
    }

    public function test_link_disk_is_not_counted_as_valid_hours_evidence(): void
    {
        $activityId = $this->createActivity('approved');

        DB::table('evidence_files')->insert([
            'activity_id' => $activityId,
            'file_type_id' => $this->evidenceFileTypeId,
            'disk' => ResearchEvidenceStorageService::LINK_DISK,
            'path' => 'https://drive.google.com/mock-evidence/' . $activityId,
            'original_name' => 'drive-link-only',
            'mime_type' => 'text/uri-list',
            'size_bytes' => 0,
            'sha256' => hash('sha256', 'link-only-' . $activityId),
            'uploaded_by_user_id' => $this->lecturerUser->id,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);

        $this->getJson('/api/lecturer/hours/calculate')
            ->assertOk()
            ->assertJsonPath('data.items.0.activity_id', $activityId)
            ->assertJsonPath('data.items.0.evidence_count', 0)
            ->assertJsonPath('data.items.0.valid_evidence_count', 0)
            ->assertJsonPath('data.items.0.has_valid_evidence', false)
            ->assertJsonPath('data.items.0.can_submit_hours', false);

        $this->getJson("/api/lecturer/hours/calculate/{$activityId}")
            ->assertOk()
            ->assertJsonCount(0, 'data.evidence_files');

        $this->getJson("/api/lecturer/hours/calculate/{$activityId}/evidence")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'EVIDENCE_REQUIRED')
            ->assertJsonPath('invalid_activity_ids.0', $activityId);
    }

    public function test_lecturer_can_upload_same_pdf_content_for_different_activities(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $activityA = $this->createActivity('approved');
        $activityB = $this->createActivity('approved');

        Sanctum::actingAs($this->lecturerUser);

        $fileA = UploadedFile::fake()->createWithContent(
            'minutes.pdf',
            'same-binary-content-for-two-activities'
        );
        $first = $this->postJson(
            "/api/lecturer/hours/calculate/{$activityA}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => $fileA,
            ]
        )->assertCreated();

        $fileB = UploadedFile::fake()->createWithContent(
            'minutes-copy.pdf',
            'same-binary-content-for-two-activities'
        );
        $second = $this->postJson(
            "/api/lecturer/hours/calculate/{$activityB}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => $fileB,
            ]
        )->assertCreated();

        $firstId = (int) $first->json('data.id');
        $secondId = (int) $second->json('data.id');

        $this->assertNotSame($firstId, $secondId);
        $this->assertDatabaseHas('evidence_files', [
            'id' => $firstId,
            'activity_id' => $activityA,
        ]);
        $this->assertDatabaseHas('evidence_files', [
            'id' => $secondId,
            'activity_id' => $activityB,
        ]);
    }

    public function test_accepted_member_can_upload_hours_evidence(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $activityId = $this->createActivity('approved');

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 0.2,
            'hours_assigned' => 4,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->memberUser);
        $response = $this->postJson(
            "/api/lecturer/hours/calculate/{$activityId}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => UploadedFile::fake()->create('member-evidence.pdf', 64, 'application/pdf'),
            ]
        )->assertCreated();

        $evidenceId = (int) $response->json('data.id');
        $this->assertDatabaseHas('evidence_files', [
            'id' => $evidenceId,
            'activity_id' => $activityId,
            'uploaded_by_user_id' => $this->memberUser->id,
        ]);
    }

    public function test_faculty_detail_contains_hours_evidence_files(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $activityId = $this->createActivity('approved');

        Sanctum::actingAs($this->lecturerUser);
        $upload = $this->postJson(
            "/api/lecturer/hours/calculate/{$activityId}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => UploadedFile::fake()->create('evidence-hours.pdf', 80, 'application/pdf'),
            ]
        )->assertCreated();

        $evidenceId = (int) $upload->json('data.id');

        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])->assertOk();

        Sanctum::actingAs($this->facultyUser);
        $response = $this->getJson('/api/faculty/hours/approvals/' . $this->lecturerId)
            ->assertOk()
            ->assertJsonPath('data.items.0.activity_id', $activityId)
            ->assertJsonPath('data.items.0.evidence_files.0.id', $evidenceId)
            ->assertJsonPath('data.items.0.evidence_files.0.file_type_id', $this->evidenceFileTypeId);

        $downloadUrl = $response->json('data.items.0.evidence_files.0.download_url');
        $this->assertIsString($downloadUrl);

        $this->get($downloadUrl)->assertOk();
    }

    public function test_admin_hours_approval_route_is_removed(): void
    {
        Sanctum::actingAs($this->scienceOfficeUser);
        $this->getJson('/api/admin/hours/approvals')->assertStatus(404);
    }

    public function test_missing_rule_blocks_submit_even_when_evidence_exists(): void
    {
        DB::table('hour_rules')->where('id', $this->hourRuleId)->update([
            'is_active' => 0,
            'updated_at' => now(),
        ]);

        $activityId = $this->createActivity('approved', null);

        Sanctum::actingAs($this->lecturerUser);

        $this->postJson(
            "/api/lecturer/hours/calculate/{$activityId}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => UploadedFile::fake()->create('manual-hours-proof.pdf', 64, 'application/pdf'),
            ]
        )->assertCreated();

        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'HOURS_VALUE_REQUIRED')
            ->assertJsonPath('invalid_items.0.activity_id', $activityId);
    }

    public function test_submit_is_blocked_when_rule_missing_and_hours_not_declared(): void
    {
        DB::table('hour_rules')->where('id', $this->hourRuleId)->update([
            'is_active' => 0,
            'updated_at' => now(),
        ]);

        $activityId = $this->createActivity('approved', null);

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'HOURS_VALUE_REQUIRED');
    }

    public function test_hours_approved_locks_hours_evidence_edits(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $activityId = $this->createActivity('approved');

        Sanctum::actingAs($this->lecturerUser);

        $upload = $this->postJson(
            "/api/lecturer/hours/calculate/{$activityId}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => UploadedFile::fake()->create('lock-check.pdf', 64, 'application/pdf'),
            ]
        )->assertCreated();
        $evidenceId = (int) $upload->json('data.id');

        $this->postJson('/api/lecturer/hours/calculate/submit', [
            'activity_ids' => [$activityId],
        ])->assertOk();

        Sanctum::actingAs($this->facultyUser);
        $this->putJson('/api/faculty/hours/approvals/' . $this->lecturerId . '/approve')
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson(
            "/api/lecturer/hours/calculate/{$activityId}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => UploadedFile::fake()->create('after-approved.pdf', 32, 'application/pdf'),
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'EVIDENCE_LOCKED_BY_APPROVED_HOURS');

        $this->deleteJson("/api/lecturer/hours/evidence/{$evidenceId}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'EVIDENCE_LOCKED_BY_APPROVED_HOURS');
    }

    public function test_faculty_work_approval_auto_calculates_equal_distribution_for_members(): void
    {
        $activityId = $this->createActivity('pending_faculty_review', null);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => null,
            'hours_assigned' => null,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'total_hours_calc' => 300.0,
            'status_id' => $this->statusId('approved'),
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'hours_assigned' => 150.0,
            'contribution_share' => 0.5,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'hours_assigned' => 150.0,
            'contribution_share' => 0.5,
        ]);

        $this->assertDatabaseHas('calculation_logs', [
            'activity_id' => $activityId,
            'rule_id' => $this->hourRuleId,
            'total_hours' => 300.0,
        ]);
    }

    public function test_faculty_work_detail_shows_computed_member_hours_in_drawer_payload(): void
    {
        $activityId = $this->createActivity('pending_faculty_review', null);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => null,
            'hours_assigned' => null,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->facultyUser);
        $response = $this->getJson("/api/faculty/works/approvals/{$activityId}")
            ->assertOk();

        $response
            ->assertJsonPath('data.activity.computed_total_hours', 300)
            ->assertJsonPath('data.activity.member_count', 2)
            ->assertJsonPath('data.activity.hours_value_label', 'Giờ quy đổi (dự kiến)');

        $memberRows = collect($response->json('data.members'));
        $this->assertCount(2, $memberRows);
        $this->assertSame(
            [150.0, 150.0],
            $memberRows
                ->pluck('computed_member_hours')
                ->map(fn ($value) => $value !== null ? (float) $value : null)
                ->all()
        );
    }

    public function test_faculty_work_approval_applies_paper_600_equal_split_for_three_members(): void
    {
        $paper600TypeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $this->kindId,
            'code' => 'hdgsnn_600',
            'name' => 'HDGSNN 600',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hour_rules')->insert([
            'kind_id' => $this->kindId,
            'type_id' => $paper600TypeId,
            'distribution_strategy' => 'equal_all_members',
            // Cố ý lưu khác để kiểm tra engine chuẩn hóa về 600 theo yêu cầu nghiệp vụ.
            'hours_total_per_activity' => 30,
            'hours_per_occurrence' => null,
            'principal_fraction' => null,
            'others_fraction_total' => null,
            'max_occurrences_per_year' => null,
            'effective_from' => '2020-01-01',
            'effective_to' => null,
            'is_active' => 1,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $coauthorRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'coauthor',
            'name' => 'Co-author',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thirdUser = User::factory()->create(['email' => 'member600-hours@test.local']);
        $thirdUser->assignRole('LECTURER');
        $thirdLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $thirdUser->id,
            'code' => 'GV006',
            'full_name' => 'Lecturer Member 600',
            'email' => 'gv006@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activityId = $this->createActivity(
            'pending_faculty_review',
            null,
            $this->kindId,
            $paper600TypeId,
            $this->academicYearId,
            $this->memberRoleId
        );

        DB::table('research_activity_members')->insert([
            [
                'activity_id' => $activityId,
                'lecturer_id' => $this->memberLecturerId,
                'member_role_id' => $coauthorRoleId,
                'contribution_share' => null,
                'hours_assigned' => null,
                'confirmation_status' => 'accepted',
                'responded_at' => now(),
                'confirmation_note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'activity_id' => $activityId,
                'lecturer_id' => $thirdLecturerId,
                'member_role_id' => $coauthorRoleId,
                'contribution_share' => null,
                'hours_assigned' => null,
                'confirmation_status' => 'accepted',
                'responded_at' => now(),
                'confirmation_note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'total_hours_calc' => 600.0,
            'status_id' => $this->statusId('approved'),
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'hours_assigned' => 200.0,
            'contribution_share' => 0.3333,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'hours_assigned' => 200.0,
            'contribution_share' => 0.3333,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $thirdLecturerId,
            'hours_assigned' => 200.0,
            'contribution_share' => 0.3333,
        ]);
    }

    public function test_faculty_work_approval_uses_paper_family_fallback_for_hdgsnn_300_when_specific_rule_missing(): void
    {
        DB::table('hour_rules')->where('id', $this->hourRuleId)->update([
            'is_active' => 0,
            'updated_at' => now(),
        ]);

        $paper600TypeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $this->kindId,
            'code' => 'hdgsnn_600',
            'name' => 'HDGSNN 600',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hour_rules')->insert([
            'kind_id' => $this->kindId,
            'type_id' => $paper600TypeId,
            'distribution_strategy' => 'equal_all_members',
            // Cố ý cấu hình khác để xác nhận engine chuẩn hóa paper 300/600/900.
            'hours_total_per_activity' => 30,
            'hours_per_occurrence' => null,
            'principal_fraction' => null,
            'others_fraction_total' => null,
            'max_occurrences_per_year' => null,
            'effective_from' => '2020-01-01',
            'effective_to' => null,
            'is_active' => 1,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activityId = $this->createActivity('pending_faculty_review', null);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => null,
            'hours_assigned' => null,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'total_hours_calc' => 300.0,
            'status_id' => $this->statusId('approved'),
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'hours_assigned' => 150.0,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'hours_assigned' => 150.0,
        ]);
    }

    public function test_faculty_work_approval_uses_principal_fraction_distribution_for_books(): void
    {
        $bookKindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'book',
            'name' => 'Book',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bookTypeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $bookKindId,
            'code' => 'reference',
            'name' => 'Reference',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hour_rules')->insert([
            'kind_id' => $bookKindId,
            'type_id' => $bookTypeId,
            'distribution_strategy' => 'principal_fraction_others_equal',
            'hours_total_per_activity' => 100,
            'hours_per_occurrence' => null,
            'principal_fraction' => 0.2,
            'others_fraction_total' => 0.8,
            'max_occurrences_per_year' => null,
            'effective_from' => '2020-01-01',
            'effective_to' => null,
            'is_active' => 1,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'member',
            'name' => 'Member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activityId = $this->createActivity(
            'pending_faculty_review',
            null,
            $bookKindId,
            $bookTypeId,
            $this->academicYearId,
            $this->memberRoleId
        );

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'member_role_id' => $memberRoleId,
            'contribution_share' => null,
            'hours_assigned' => null,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'hours_assigned' => 60.0,
            'contribution_share' => 0.6,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'hours_assigned' => 40.0,
            'contribution_share' => 0.4,
        ]);
    }

    public function test_faculty_work_approval_applies_complex_split_for_chief_editor(): void
    {
        $bookKindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'book',
            'name' => 'Book',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $textbookTypeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $bookKindId,
            'code' => 'textbook',
            'name' => 'Textbook',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chiefEditorRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'chief_editor',
            'name' => 'Chief editor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'coauthor',
            'name' => 'Co-author',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hour_rules')->insert([
            'kind_id' => $bookKindId,
            'type_id' => $textbookTypeId,
            'distribution_strategy' => 'principal_fraction_others_equal',
            'hours_total_per_activity' => 900,
            'hours_per_occurrence' => null,
            'principal_fraction' => 0.2,
            'others_fraction_total' => 0.8,
            'max_occurrences_per_year' => null,
            'effective_from' => '2020-01-01',
            'effective_to' => null,
            'is_active' => 1,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $thirdUser = User::factory()->create(['email' => 'member3-hours@test.local']);
        $thirdUser->assignRole('LECTURER');
        $thirdLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $thirdUser->id,
            'code' => 'GV005',
            'full_name' => 'Lecturer Member 3',
            'email' => 'gv005@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activityId = $this->createActivity(
            'pending_faculty_review',
            null,
            $bookKindId,
            $textbookTypeId,
            $this->academicYearId,
            $chiefEditorRoleId
        );

        DB::table('research_activity_members')->insert([
            [
                'activity_id' => $activityId,
                'lecturer_id' => $this->memberLecturerId,
                'member_role_id' => $memberRoleId,
                'contribution_share' => null,
                'hours_assigned' => null,
                'confirmation_status' => 'accepted',
                'responded_at' => now(),
                'confirmation_note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'activity_id' => $activityId,
                'lecturer_id' => $thirdLecturerId,
                'member_role_id' => $memberRoleId,
                'contribution_share' => null,
                'hours_assigned' => null,
                'confirmation_status' => 'accepted',
                'responded_at' => now(),
                'confirmation_note' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'hours_assigned' => 420.0,
            'contribution_share' => 0.4667,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'hours_assigned' => 240.0,
            'contribution_share' => 0.2667,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $thirdLecturerId,
            'hours_assigned' => 240.0,
            'contribution_share' => 0.2667,
        ]);
    }

    public function test_faculty_work_approval_applies_project_leader_pool_split_rule(): void
    {
        $projectKindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'project',
            'name' => 'Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectTypeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $projectKindId,
            'code' => 'bo',
            'name' => 'Project - Ministry',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'member',
            'name' => 'Member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hour_rules')->insert([
            'kind_id' => $projectKindId,
            'type_id' => $projectTypeId,
            'distribution_strategy' => 'principal_fraction_others_equal',
            'hours_total_per_activity' => 720,
            'hours_per_occurrence' => 480,
            'principal_fraction' => null,
            'others_fraction_total' => null,
            'max_occurrences_per_year' => null,
            'effective_from' => '2020-01-01',
            'effective_to' => null,
            'is_active' => 1,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activityId = $this->createActivity(
            'pending_faculty_review',
            null,
            $projectKindId,
            $projectTypeId,
            $this->academicYearId,
            $this->memberRoleId
        );

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'member_role_id' => $memberRoleId,
            'contribution_share' => null,
            'hours_assigned' => null,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->facultyUser);
        $this->putJson("/api/faculty/works/approvals/{$activityId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'total_hours_calc' => 1200.0,
            'status_id' => $this->statusId('approved'),
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'hours_assigned' => 720.0,
            'contribution_share' => 0.6,
        ]);

        $this->assertDatabaseHas('research_activity_members', [
            'activity_id' => $activityId,
            'lecturer_id' => $this->memberLecturerId,
            'hours_assigned' => 480.0,
            'contribution_share' => 0.4,
        ]);
    }

    public function test_hours_evidence_upload_requires_pdf_file(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $activityId = $this->createActivity('approved');
        Sanctum::actingAs($this->lecturerUser);

        $this->postJson(
            "/api/lecturer/hours/calculate/{$activityId}/evidence",
            [
                'file_type_id' => $this->evidenceFileTypeId,
                'file' => UploadedFile::fake()->image('wrong-type.jpg'),
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['errors' => ['file']]);
    }

    private function seedBaseData(): void
    {
        $this->facultyId = DB::table('faculties')->insertGetId([
            'code' => 'F01',
            'name' => 'Faculty IT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $this->facultyId,
            'code' => 'D01',
            'name' => 'Department SE',
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

        foreach ([
            'draft' => 'Draft',
            'pending_member_confirm' => 'Pending member confirm',
            'member_rejected' => 'Member rejected',
            'pending_faculty_review' => 'Pending faculty review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
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
                'name' => 'Faculty work approval',
                'order_no' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'manager',
                'name' => 'University level',
                'order_no' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'hours',
                'name' => 'Faculty hours approval',
                'order_no' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->hoursStageId = (int) DB::table('approval_stages')->where('code', 'hours')->value('id');

        $this->kindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'paper',
            'name' => 'Paper',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $this->kindId,
            'code' => 'hdgsnn_300',
            'name' => 'HDGSNN 300',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'principal',
            'name' => 'Principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->evidenceFileTypeId = DB::table('evidence_file_types')->insertGetId([
            'code' => 'acceptance_decision',
            'name' => 'Acceptance minutes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->hourRuleId = DB::table('hour_rules')->insertGetId([
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'distribution_strategy' => 'equal_all_members',
            'hours_total_per_activity' => 20,
            'hours_per_occurrence' => null,
            'principal_fraction' => null,
            'others_fraction_total' => null,
            'max_occurrences_per_year' => null,
            'effective_from' => '2020-01-01',
            'effective_to' => null,
            'is_active' => 1,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedUsers(): void
    {
        $this->lecturerUser = User::factory()->create(['email' => 'lecturer-hours@test.local']);
        $this->lecturerUser->assignRole('LECTURER');
        $this->lecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->lecturerUser->id,
            'code' => 'GV001',
            'full_name' => 'Lecturer A',
            'email' => 'gv001@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->memberUser = User::factory()->create(['email' => 'member-hours@test.local']);
        $this->memberUser->assignRole('LECTURER');
        $this->memberLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->memberUser->id,
            'code' => 'GV004',
            'full_name' => 'Lecturer Member',
            'email' => 'gv004@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->facultyUser = User::factory()->create(['email' => 'faculty-hours@test.local']);
        $this->facultyUser->assignRole('DEPARTMENT_BOARD');
        $this->facultyLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->facultyUser->id,
            'code' => 'GV002',
            'full_name' => 'Faculty Staff',
            'email' => 'gv002@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->scienceOfficeUser = User::factory()->create(['email' => 'sci-hours@test.local']);
        $this->scienceOfficeUser->assignRole('SCIENCE_OFFICE');
        $this->scienceOfficeLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->scienceOfficeUser->id,
            'code' => 'GV003',
            'full_name' => 'Science Office Staff',
            'email' => 'gv003@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createActivity(
        string $statusCode,
        ?float $hoursAssigned = 20,
        ?int $kindId = null,
        ?int $typeId = null,
        ?int $academicYearId = null,
        ?int $ownerMemberRoleId = null
    ): int
    {
        $resolvedKindId = $kindId ?? $this->kindId;
        $resolvedTypeId = $typeId ?? $this->typeId;
        $resolvedAcademicYearId = $academicYearId ?? $this->academicYearId;
        $resolvedOwnerRoleId = $ownerMemberRoleId ?? $this->memberRoleId;

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-' . strtoupper(uniqid()),
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $resolvedKindId,
            'type_id' => $resolvedTypeId,
            'academic_year_id' => $resolvedAcademicYearId,
            'status_id' => $this->statusId($statusCode),
            'title' => 'Hours flow test activity',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => null,
            'approved_at' => $statusCode === 'approved' ? now() : null,
            'total_hours_calc' => 20,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'member_role_id' => $resolvedOwnerRoleId,
            'contribution_share' => 1,
            'hours_assigned' => $hoursAssigned,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($resolvedKindId === $this->kindId) {
            DB::table('paper_details')->insert([
                'activity_id' => $activityId,
                'journal_name' => 'Testing Journal',
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
        }

        return $activityId;
    }

    private function attachEvidenceRecord(int $activityId, int $uploadedByUserId): int
    {
        return (int) DB::table('evidence_files')->insertGetId([
            'activity_id' => $activityId,
            'file_type_id' => $this->evidenceFileTypeId,
            'disk' => 'local',
            'path' => 'evidence/test/' . uniqid('hours-', true) . '.pdf',
            'original_name' => 'existing-evidence.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', uniqid((string) $activityId, true)),
            'uploaded_by_user_id' => $uploadedByUserId,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function statusId(string $code): int
    {
        return (int) DB::table('activity_statuses')->where('code', $code)->value('id');
    }
}
