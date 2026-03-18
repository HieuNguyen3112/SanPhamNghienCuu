<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Evidence\ResearchEvidenceStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HoursListFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $lecturerUser;
    private User $facultyUser;
    private int $lecturerId;
    private int $departmentId;
    private int $academicYearId;
    private int $approvedStatusId;
    private int $kindId;
    private int $typeId;
    private int $memberRoleId;
    private int $hoursStageId;
    private int $evidenceFileTypeId;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-02-24 09:00:00');

        Role::findOrCreate('LECTURER', 'web');
        Role::findOrCreate('DEPARTMENT_BOARD', 'web');

        $this->seedBaseData();
        $this->seedUsers();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calculate_list_includes_owner_work_even_without_member_row(): void
    {
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-OWNER-NO-MEMBER',
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'academic_year_id' => $this->academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'Owner-only approved work',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => 300,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);

        $response = $this->getJson('/api/lecturer/hours/calculate?status=all')->assertOk();

        $returnedActivityIds = collect($response->json('data.items'))
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->assertContains($activityId, $returnedActivityIds);
    }

    public function test_faculty_pending_list_contains_submitted_hours_request(): void
    {
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-PENDING-HOURS',
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'academic_year_id' => $this->academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'Approved work for submit',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => 300,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => 300,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('evidence_files')->insert([
            'activity_id' => $activityId,
            'file_type_id' => $this->evidenceFileTypeId,
            'disk' => 'local',
            'path' => 'evidence/test/pending-hours.pdf',
            'original_name' => 'pending-hours.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', 'pending-hours-' . $activityId),
            'uploaded_by_user_id' => $this->lecturerUser->id,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson('/api/lecturer/hours/submit', [
            'activity_ids' => [$activityId],
        ])->assertOk();

        Sanctum::actingAs($this->facultyUser);
        $response = $this->getJson('/api/faculty/hours/approvals?status=pending')->assertOk();

        $items = collect($response->json('data.items'));
        $this->assertTrue($items->isNotEmpty());

        $first = $items->first();
        $this->assertSame($this->lecturerId, (int) ($first['lecturer_id'] ?? 0));
        $this->assertSame('pending', (string) ($first['status'] ?? ''));
    }

    public function test_faculty_list_include_all_years_returns_pending_requests_across_years(): void
    {
        $oldAcademicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createPendingHoursApprovalForYear('ACT-FAC-YEAR-CURRENT', $this->academicYearId);
        $this->createPendingHoursApprovalForYear('ACT-FAC-YEAR-OLD', $oldAcademicYearId);

        Sanctum::actingAs($this->facultyUser);

        $defaultResponse = $this->getJson('/api/faculty/hours/approvals?status=pending')->assertOk();
        $defaultItem = collect($defaultResponse->json('data.items'))->first();
        $this->assertNotNull($defaultItem);
        $this->assertSame(1, (int) ($defaultItem['works_count'] ?? 0));

        $allYearsResponse = $this->getJson('/api/faculty/hours/approvals?status=pending&include_all_years=1')->assertOk();
        $allYearsItem = collect($allYearsResponse->json('data.items'))->first();
        $this->assertNotNull($allYearsItem);
        $this->assertSame(2, (int) ($allYearsItem['works_count'] ?? 0));
    }

    public function test_calculate_status_filter_returns_not_submitted_items(): void
    {
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-NOT-SUBMITTED',
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'academic_year_id' => $this->academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'Approved work not submitted',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => 300,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => 300,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);

        $response = $this->getJson('/api/lecturer/hours/calculate?status=hours_not_submitted')
            ->assertOk();

        $items = collect($response->json('data.items'));
        $this->assertSame(1, $items->count());
        $this->assertSame($activityId, (int) ($items->first()['activity_id'] ?? 0));
        $this->assertSame('hours_not_submitted', (string) ($items->first()['hours_request_state'] ?? ''));
    }

    public function test_calculate_list_include_all_years_returns_rows_across_academic_years(): void
    {
        $oldAcademicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentYearActivityId = $this->createApprovedActivityForYear(
            'ACT-YEAR-CURRENT',
            $this->academicYearId
        );
        $oldYearActivityId = $this->createApprovedActivityForYear(
            'ACT-YEAR-OLD',
            $oldAcademicYearId
        );

        Sanctum::actingAs($this->lecturerUser);

        $response = $this->getJson('/api/lecturer/hours/calculate?include_all_years=1&status=all')
            ->assertOk();

        $activityIds = collect($response->json('data.items'))
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->assertContains($currentYearActivityId, $activityIds);
        $this->assertContains($oldYearActivityId, $activityIds);
    }

    public function test_calculate_endpoint_handles_missing_project_rule_without_crashing(): void
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

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-PROJECT-SYNTHETIC',
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $projectKindId,
            'type_id' => $projectTypeId,
            'academic_year_id' => $this->academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'Project hours synthetic rule',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => null,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);

        $response = $this->getJson('/api/lecturer/hours/calculate?status=all&include_all_years=1')
            ->assertOk();

        $item = collect($response->json('data.items'))
            ->firstWhere('activity_id', $activityId);

        $this->assertNotNull($item);
        $this->assertSame('hours_not_submitted', (string) ($item['hours_request_state'] ?? ''));
        $this->assertNull($item['effective_hours_display'] ?? null);
    }

    public function test_calculate_endpoint_accepts_academic_year_code_and_vietnamese_status_filter(): void
    {
        $activityId = $this->createApprovedActivityForYear(
            'ACT-STATUS-VI',
            $this->academicYearId
        );

        Sanctum::actingAs($this->lecturerUser);

        $query = http_build_query([
            'academic_year' => '2025-2026',
            'status' => 'Chưa gửi duyệt giờ',
        ]);

        $response = $this->getJson('/api/lecturer/hours/calculate?' . $query)->assertOk();

        $activityIds = collect($response->json('data.items'))
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->assertContains($activityId, $activityIds);
    }

    public function test_calculate_endpoint_normalizes_keyword_whitespace_before_filtering(): void
    {
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-KEYWORD-NORMALIZE',
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'academic_year_id' => $this->academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'De tai machine learning ung dung',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => 300,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => 300,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);

        $response = $this->getJson(
            '/api/lecturer/hours/calculate?q=' . urlencode('  machine    learning   ')
        )->assertOk();

        $activityIds = collect($response->json('data.items'))
            ->pluck('activity_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->assertContains($activityId, $activityIds);
    }

    public function test_missing_evidence_only_filter_is_stable_across_pagination(): void
    {
        $missingA = $this->createApprovedActivityForYear('ACT-MISSING-A', $this->academicYearId);
        $missingB = $this->createApprovedActivityForYear('ACT-MISSING-B', $this->academicYearId);
        $linkOnly = $this->createApprovedActivityForYear('ACT-LINK-ONLY', $this->academicYearId);
        $withPdf = $this->createApprovedActivityForYear('ACT-WITH-PDF', $this->academicYearId);

        DB::table('evidence_files')->insert([
            'activity_id' => $linkOnly,
            'file_type_id' => $this->evidenceFileTypeId,
            'disk' => ResearchEvidenceStorageService::LINK_DISK,
            'path' => 'https://drive.google.com/mock-evidence/' . $linkOnly,
            'original_name' => 'link-only',
            'mime_type' => 'text/uri-list',
            'size_bytes' => 0,
            'sha256' => hash('sha256', 'link-only-' . $linkOnly),
            'uploaded_by_user_id' => $this->lecturerUser->id,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('evidence_files')->insert([
            'activity_id' => $withPdf,
            'file_type_id' => $this->evidenceFileTypeId,
            'disk' => 'local',
            'path' => 'evidence/test/with-pdf.pdf',
            'original_name' => 'with-pdf.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', 'with-pdf-' . $withPdf),
            'uploaded_by_user_id' => $this->lecturerUser->id,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);

        $pageOne = $this->getJson('/api/lecturer/hours/calculate?status=hours_not_submitted&missing_evidence_only=1&page=1&per_page=1')
            ->assertOk();
        $pageTwo = $this->getJson('/api/lecturer/hours/calculate?status=hours_not_submitted&missing_evidence_only=1&page=2&per_page=1')
            ->assertOk();
        $widePage = $this->getJson('/api/lecturer/hours/calculate?status=hours_not_submitted&missing_evidence_only=1&page=1&per_page=10')
            ->assertOk();

        foreach ([$pageOne, $pageTwo, $widePage] as $response) {
            $response
                ->assertJsonPath('data.pagination.total', 3)
                ->assertJsonPath('data.summary.missing_evidence_count', 3);

            $this->assertSame(900.0, (float) $response->json('data.summary.missing_evidence_hours_total'));
        }

        $this->assertCount(1, $pageOne->json('data.items'));
        $this->assertCount(1, $pageTwo->json('data.items'));

        $items = collect($widePage->json('data.items'));
        $activityIds = $items->pluck('activity_id')->map(fn($id) => (int) $id)->all();

        $this->assertContains($missingA, $activityIds);
        $this->assertContains($missingB, $activityIds);
        $this->assertContains($linkOnly, $activityIds);
        $this->assertNotContains($withPdf, $activityIds);
        $this->assertTrue($items->every(fn(array $item) => (int) ($item['evidence_count'] ?? -1) === 0));
    }

    private function seedBaseData(): void
    {
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'F01',
            'name' => 'Faculty IT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
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

        $this->approvedStatusId = DB::table('activity_statuses')->insertGetId([
            'code' => 'approved',
            'name' => 'Approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('approval_stages')->insert([
            ['code' => 'assistant', 'name' => 'Faculty work approval', 'order_no' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'hours', 'name' => 'Faculty hours approval', 'order_no' => 2, 'created_at' => now(), 'updated_at' => now()],
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
            'name' => 'Acceptance decision',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hour_rules')->insert([
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'distribution_strategy' => 'equal_all_members',
            'hours_total_per_activity' => 300,
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
        $this->lecturerUser = User::factory()->create(['email' => 'lecturer-list@test.local']);
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

        $this->facultyUser = User::factory()->create(['email' => 'faculty-list@test.local']);
        $this->facultyUser->assignRole('DEPARTMENT_BOARD');
        DB::table('lecturers')->insert([
            'user_id' => $this->facultyUser->id,
            'code' => 'GV002',
            'full_name' => 'Faculty Staff',
            'email' => 'gv002@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createApprovedActivityForYear(string $code, int $academicYearId): int
    {
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => $code,
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'academic_year_id' => $academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'Work ' . $code,
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => 300,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $this->lecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => 300,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $activityId;
    }

    private function createPendingHoursApprovalForYear(string $code, int $academicYearId): int
    {
        $activityId = $this->createApprovedActivityForYear($code, $academicYearId);

        DB::table('evidence_files')->insert([
            'activity_id' => $activityId,
            'file_type_id' => $this->evidenceFileTypeId,
            'disk' => 'local',
            'path' => 'evidence/test/' . strtolower($code) . '.pdf',
            'original_name' => strtolower($code) . '.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', 'evidence-' . $code),
            'uploaded_by_user_id' => $this->lecturerUser->id,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->lecturerUser);
        $this->postJson('/api/lecturer/hours/submit', [
            'activity_ids' => [$activityId],
        ])->assertOk();

        return $activityId;
    }
}
