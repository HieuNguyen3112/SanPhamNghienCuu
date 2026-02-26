<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HoursPagesDataRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $lecturerUser;
    private User $facultyUser;
    private int $lecturerId;
    private int $facultyLecturerId;
    private int $departmentId;
    private int $facultyId;
    private int $currentAcademicYearId;
    private int $dataAcademicYearId;
    private int $approvedStatusId;
    private int $hoursStageId;
    private int $kindId;
    private int $typeId;
    private int $memberRoleId;

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

    public function test_lecturer_personal_overview_defaults_to_year_with_hours_data(): void
    {
        $this->createHoursApprovalActivity(
            ownerLecturerId: $this->lecturerId,
            memberLecturerId: $this->lecturerId,
            academicYearId: $this->dataAcademicYearId,
            approvalStatus: 'approved',
            hoursAssigned: 42.5
        );

        Sanctum::actingAs($this->lecturerUser);

        $response = $this->getJson('/api/lecturer/hours/personal/overview')->assertOk();

        $this->assertSame($this->dataAcademicYearId, (int) $response->json('data.academic_year_id'));
        $this->assertSame(42.5, (float) $response->json('data.approved_hours'));
    }

    public function test_lecturer_personal_overview_accepts_academic_year_code_filter(): void
    {
        $this->createHoursApprovalActivity(
            ownerLecturerId: $this->lecturerId,
            memberLecturerId: $this->lecturerId,
            academicYearId: $this->dataAcademicYearId,
            approvalStatus: 'approved',
            hoursAssigned: 30
        );

        Sanctum::actingAs($this->lecturerUser);

        $query = http_build_query(['academic_year' => '2024-2025']);
        $response = $this->getJson('/api/lecturer/hours/personal/overview?' . $query)->assertOk();

        $this->assertSame($this->dataAcademicYearId, (int) $response->json('data.academic_year_id'));
    }

    public function test_faculty_hours_approvals_defaults_to_year_with_pending_requests(): void
    {
        $this->createHoursApprovalActivity(
            ownerLecturerId: $this->lecturerId,
            memberLecturerId: $this->lecturerId,
            academicYearId: $this->dataAcademicYearId,
            approvalStatus: 'pending',
            hoursAssigned: 18
        );

        Sanctum::actingAs($this->facultyUser);

        $response = $this->getJson('/api/faculty/hours/approvals?status=pending')->assertOk();

        $this->assertSame($this->dataAcademicYearId, (int) $response->json('data.academic_year.id'));
        $items = collect($response->json('data.items'));
        $this->assertTrue($items->isNotEmpty());
        $this->assertSame($this->lecturerId, (int) ($items->first()['lecturer_id'] ?? 0));
    }

    public function test_faculty_hours_management_aggregates_from_approved_hours_without_cache_table(): void
    {
        $this->createHoursApprovalActivity(
            ownerLecturerId: $this->lecturerId,
            memberLecturerId: $this->lecturerId,
            academicYearId: $this->dataAcademicYearId,
            approvalStatus: 'approved',
            hoursAssigned: 55
        );

        $this->assertDatabaseMissing('lecturer_yearly_hours', [
            'lecturer_id' => $this->lecturerId,
            'academic_year_id' => $this->dataAcademicYearId,
        ]);

        Sanctum::actingAs($this->facultyUser);

        $response = $this->getJson('/api/faculty/hours/lecturers/summary')->assertOk();

        $rows = collect($response->json('data'));
        $target = $rows->firstWhere('lecturer_id', $this->lecturerId);

        $this->assertNotNull($target);
        $this->assertSame($this->dataAcademicYearId, (int) ($target['academic_year_id'] ?? 0));
        $this->assertSame(55.0, (float) ($target['hours_total'] ?? 0));
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

        $this->dataAcademicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->currentAcademicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
            'is_active' => 0,
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
            [
                'code' => 'assistant',
                'name' => 'Faculty work approval',
                'order_no' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'hours',
                'name' => 'Faculty hours approval',
                'order_no' => 2,
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
            'code' => 'paper_standard',
            'name' => 'Paper Standard',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'principal',
            'name' => 'Principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedUsers(): void
    {
        $this->lecturerUser = User::factory()->create(['email' => 'lecturer-regression@test.local']);
        $this->lecturerUser->assignRole('LECTURER');
        $this->lecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->lecturerUser->id,
            'code' => 'GV001',
            'full_name' => 'Lecturer Regression',
            'email' => 'gv001@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->facultyUser = User::factory()->create(['email' => 'faculty-regression@test.local']);
        $this->facultyUser->assignRole('DEPARTMENT_BOARD');
        $this->facultyLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->facultyUser->id,
            'code' => 'GV002',
            'full_name' => 'Faculty Regression',
            'email' => 'gv002@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createHoursApprovalActivity(
        int $ownerLecturerId,
        int $memberLecturerId,
        int $academicYearId,
        string $approvalStatus,
        float $hoursAssigned
    ): int {
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-REG-' . strtoupper(uniqid()),
            'owner_lecturer_id' => $ownerLecturerId,
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'academic_year_id' => $academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'Regression hours activity',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => $hoursAssigned,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $memberLecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => $hoursAssigned,
            'confirmation_status' => 'accepted',
            'responded_at' => now(),
            'confirmation_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('activity_approvals')->insert([
            'activity_id' => $activityId,
            'stage_id' => $this->hoursStageId,
            'status' => $approvalStatus,
            'decided_by_user_id' => null,
            'decided_at' => now(),
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $activityId;
    }
}
