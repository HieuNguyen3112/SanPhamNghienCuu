<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLecturerHoursManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('ADMIN', 'web');
    }

    private function seedBase(): array
    {
        $yearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('workload_quotas')->insert([
            'academic_year_id' => $yearId,
            'required_hours' => 600,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $facultyA = DB::table('faculties')->insertGetId([
            'code' => 'FAC-1',
            'name' => 'Faculty A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $facultyB = DB::table('faculties')->insertGetId([
            'code' => 'FAC-2',
            'name' => 'Faculty B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentA = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyA,
            'code' => 'DEP-1',
            'name' => 'Department A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $departmentB = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyB,
            'code' => 'DEP-2',
            'name' => 'Department B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lecturerA = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV-001',
            'full_name' => 'Lecturer A',
            'email' => 'lec-a@example.com',
            'phone' => '090000001',
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $departmentA,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lecturerB = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV-002',
            'full_name' => 'Lecturer B',
            'email' => 'lec-b@example.com',
            'phone' => '090000002',
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $departmentB,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lecturer_yearly_hours')->insert([
            [
                'lecturer_id' => $lecturerA,
                'academic_year_id' => $yearId,
                'hours_total' => 650,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'lecturer_id' => $lecturerB,
                'academic_year_id' => $yearId,
                'hours_total' => 400,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [
            'academic_year_id' => $yearId,
            'faculty_a_id' => $facultyA,
            'faculty_b_id' => $facultyB,
            'department_a_id' => $departmentA,
            'department_b_id' => $departmentB,
            'lecturer_a_id' => $lecturerA,
            'lecturer_b_id' => $lecturerB,
        ];
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_guest_cannot_access_hours_summary(): void
    {
        $this->getJson('/api/admin/hours/lecturers/summary')
            ->assertStatus(401);
    }

    public function test_non_admin_cannot_access_hours_summary(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/hours/lecturers/summary')
            ->assertStatus(403);
    }

    public function test_admin_can_list_hours_summary(): void
    {
        $seed = $this->seedBase();
        $this->actingAsAdmin();

        $this->getJson('/api/admin/hours/lecturers/summary?academic_year_id=' . $seed['academic_year_id'])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'filters',
                    'options' => ['faculties', 'academic_years', 'kpi_statuses'],
                    'totals',
                    'pagination',
                ],
            ])
            ->assertJsonPath('meta.totals.total_lecturers', 2)
            ->assertJsonPath('data.0.academic_year_id', $seed['academic_year_id']);

        $this->getJson('/api/admin/hours/lecturers/summary?academic_year_id=' . $seed['academic_year_id'] . '&faculty_id=' . $seed['faculty_a_id'])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.faculty_id', $seed['faculty_a_id']);
    }

    public function test_admin_can_view_detail(): void
    {
        $seed = $this->seedBase();

        $statusApproved = DB::table('activity_statuses')->insertGetId([
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

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-001',
            'owner_lecturer_id' => $seed['lecturer_a_id'],
            'kind_id' => $kindId,
            'type_id' => null,
            'academic_year_id' => $seed['academic_year_id'],
            'status_id' => $statusApproved,
            'title' => 'Approved Work',
            'abstract' => null,
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-01',
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => 120,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $activityId,
            'lecturer_id' => $seed['lecturer_a_id'],
            'member_role_id' => $memberRoleId,
            'contribution_share' => 1,
            'hours_assigned' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsAdmin();

        $this->getJson('/api/admin/hours/lecturers/' . $seed['lecturer_a_id'] . '?academic_year_id=' . $seed['academic_year_id'])
            ->assertStatus(200)
            ->assertJsonPath('data.lecturer_id', $seed['lecturer_a_id'])
            ->assertJsonPath('data.rows.0.activity_id', $activityId)
            ->assertJsonPath('data.rows.0.hours_converted', 120.0);
    }

    public function test_admin_can_export_excel_and_pdf(): void
    {
        $seed = $this->seedBase();
        $this->actingAsAdmin();

        $this->get('/api/admin/hours/lecturers/summary/export/excel?academic_year_id=' . $seed['academic_year_id'])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get('/api/admin/hours/lecturers/summary/export/pdf?academic_year_id=' . $seed['academic_year_id'])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
