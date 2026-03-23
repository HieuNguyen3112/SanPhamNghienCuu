<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResearchHoursReportApiTest extends TestCase
{
    use RefreshDatabase;

    private int $facultyOneId;

    private int $facultyTwoId;

    private int $departmentOneId;

    private int $departmentTwoId;

    private int $academicYearId;

    private int $approvedStatusId;

    private int $hoursStageId;

    private int $kindId;

    private int $typeId;

    private int $memberRoleId;

    private int $facultyOneLecturerId;

    private int $facultyTwoLecturerId;

    private User $scienceOfficeUser;

    private User $facultyBoardUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('SCIENCE_OFFICE', 'web');
        Role::findOrCreate('DEPARTMENT_BOARD', 'web');

        $this->seedLookupData();
        $this->seedUsersAndLecturers();
    }

    public function test_admin_hour_research_report_uses_approved_hours_when_yearly_table_is_empty(): void
    {
        $this->createApprovedHoursActivity(
            ownerLecturerId: $this->facultyOneLecturerId,
            memberLecturerId: $this->facultyOneLecturerId,
            hoursAssigned: 30.0
        );
        $this->createApprovedHoursActivity(
            ownerLecturerId: $this->facultyTwoLecturerId,
            memberLecturerId: $this->facultyTwoLecturerId,
            hoursAssigned: 50.0
        );

        $this->assertDatabaseCount('lecturer_yearly_hours', 0);

        Sanctum::actingAs($this->scienceOfficeUser);

        $response = $this->getJson('/api/admin/reports/hour-research?academic_year_id=' . $this->academicYearId)
            ->assertOk();

        $response->assertJsonPath('data.kpis.lecturer_count', 3);
        $response->assertJsonPath('data.kpis.total_hours', 80);

        $rows = collect($response->json('data.table.items'));
        $this->assertCount(3, $rows);
        $this->assertSame(80.0, (float) $rows->sum('total_hours'));

        $facultyFiltered = $this->getJson('/api/admin/reports/hour-research?academic_year_id=' . $this->academicYearId . '&faculty_id=' . $this->facultyOneId)
            ->assertOk();
        $facultyFiltered->assertJsonPath('data.kpis.lecturer_count', 2);
        $facultyFiltered->assertJsonPath('data.kpis.total_hours', 30);
    }

    public function test_faculty_hour_research_report_is_restricted_to_own_faculty_scope(): void
    {
        $this->createApprovedHoursActivity(
            ownerLecturerId: $this->facultyOneLecturerId,
            memberLecturerId: $this->facultyOneLecturerId,
            hoursAssigned: 28.0
        );
        $this->createApprovedHoursActivity(
            ownerLecturerId: $this->facultyTwoLecturerId,
            memberLecturerId: $this->facultyTwoLecturerId,
            hoursAssigned: 44.0
        );

        Sanctum::actingAs($this->facultyBoardUser);

        $response = $this->getJson('/api/faculty/reports/hour-research?academic_year_id=' . $this->academicYearId)
            ->assertOk();

        $response->assertJsonPath('data.kpis.lecturer_count', 2);
        $response->assertJsonPath('data.kpis.total_hours', 28);
        $response->assertJsonPath('data.applied_filters.faculty_id', $this->facultyOneId);

        $this->getJson('/api/faculty/reports/hour-research?academic_year_id=' . $this->academicYearId . '&faculty_id=' . $this->facultyTwoId)
            ->assertStatus(403);
    }

    private function seedLookupData(): void
    {
        $now = now();

        $this->facultyOneId = DB::table('faculties')->insertGetId([
            'code' => 'F01',
            'name' => 'Faculty One',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->facultyTwoId = DB::table('faculties')->insertGetId([
            'code' => 'F02',
            'name' => 'Faculty Two',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->departmentOneId = DB::table('departments')->insertGetId([
            'faculty_id' => $this->facultyOneId,
            'code' => 'D01',
            'name' => 'Department One',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->departmentTwoId = DB::table('departments')->insertGetId([
            'faculty_id' => $this->facultyTwoId,
            'code' => 'D02',
            'name' => 'Department Two',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('workload_quotas')->insert([
            'academic_year_id' => $this->academicYearId,
            'required_hours' => 60,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->approvedStatusId = DB::table('activity_statuses')->insertGetId([
            'code' => 'approved',
            'name' => 'Approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->hoursStageId = DB::table('approval_stages')->insertGetId([
            'code' => 'hours',
            'name' => 'Hours',
            'order_no' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->kindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'paper',
            'name' => 'Paper',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->typeId = DB::table('activity_types')->insertGetId([
            'kind_id' => $this->kindId,
            'code' => 'paper_standard',
            'name' => 'Paper Standard',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'principal',
            'name' => 'Principal',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedUsersAndLecturers(): void
    {
        $now = now();

        $this->scienceOfficeUser = User::factory()->create([
            'email' => 'science-office-hour-report@test.local',
        ]);
        $this->scienceOfficeUser->assignRole('SCIENCE_OFFICE');

        $this->facultyBoardUser = User::factory()->create([
            'email' => 'faculty-board-hour-report@test.local',
        ]);
        $this->facultyBoardUser->assignRole('DEPARTMENT_BOARD');

        $facultyBoardLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => $this->facultyBoardUser->id,
            'code' => 'GV-FAC-BOARD',
            'full_name' => 'Faculty Board User',
            'email' => 'gv.fac.board@test.local',
            'department_id' => $this->departmentOneId,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->assertGreaterThan(0, $facultyBoardLecturerId);

        $this->facultyOneLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV-F1-001',
            'full_name' => 'Lecturer Faculty One',
            'email' => 'gv.f1.001@test.local',
            'department_id' => $this->departmentOneId,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->facultyTwoLecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV-F2-001',
            'full_name' => 'Lecturer Faculty Two',
            'email' => 'gv.f2.001@test.local',
            'department_id' => $this->departmentTwoId,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createApprovedHoursActivity(
        int $ownerLecturerId,
        int $memberLecturerId,
        float $hoursAssigned
    ): int {
        $now = now();
        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-HR-' . strtoupper(uniqid()),
            'owner_lecturer_id' => $ownerLecturerId,
            'kind_id' => $this->kindId,
            'type_id' => $this->typeId,
            'academic_year_id' => $this->academicYearId,
            'status_id' => $this->approvedStatusId,
            'title' => 'Hour report activity',
            'abstract' => null,
            'start_date' => null,
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => $now,
            'approved_at' => $now,
            'total_hours_calc' => $hoursAssigned,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $memberLecturerId,
            'member_role_id' => $this->memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => $hoursAssigned,
            'confirmation_status' => 'accepted',
            'responded_at' => $now,
            'confirmation_note' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('activity_approvals')->insert([
            'activity_id' => $activityId,
            'stage_id' => $this->hoursStageId,
            'status' => 'approved',
            'decided_by_user_id' => null,
            'decided_at' => $now,
            'note' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $activityId;
    }
}
