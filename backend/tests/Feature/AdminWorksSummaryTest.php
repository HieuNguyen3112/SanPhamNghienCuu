<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ResearchLookupSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWorksSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->seed(ResearchLookupSeeder::class);
    }

    /** @test */
    public function unauthenticated_cannot_access_summary()
    {
        $this->getJson('/api/admin/works/lecturers/summary')
            ->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_cannot_export_summary()
    {
        $this->get('/api/admin/works/lecturers/summary/export/excel')
            ->assertStatus(401);
        $this->get('/api/admin/works/lecturers/summary/export/pdf')
            ->assertStatus(401);
    }

    /** @test */
    public function non_admin_cannot_access_summary()
    {
        $user = User::create([
            'name' => 'Lecturer',
            'email' => 'gv1@local.test',
            'password' => 'password',
        ]);
        $user->syncRoles(['GV']);

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/works/lecturers/summary')
            ->assertStatus(403);
    }

    /** @test */
    public function non_admin_cannot_export_summary()
    {
        $user = User::create([
            'name' => 'Lecturer Export',
            'email' => 'gv2@local.test',
            'password' => 'password',
        ]);
        $user->syncRoles(['GV']);

        Sanctum::actingAs($user);

        $this->get('/api/admin/works/lecturers/summary/export/excel')
            ->assertStatus(403);
        $this->get('/api/admin/works/lecturers/summary/export/pdf')
            ->assertStatus(403);
    }

    /** @test */
    public function admin_can_get_summary_counts_and_filters()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@local.test',
            'password' => 'password',
        ]);
        $admin->syncRoles(['ADMIN']);

        Sanctum::actingAs($admin);

        $faculty1 = DB::table('faculties')->insertGetId([
            'code' => 'F01',
            'name' => 'Khoa Cong nghe',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $faculty2 = DB::table('faculties')->insertGetId([
            'code' => 'F02',
            'name' => 'Khoa Kinh te',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dept1 = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty1,
            'code' => 'D01',
            'name' => 'Bo mon CNTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $dept2 = DB::table('departments')->insertGetId([
            'faculty_id' => $faculty2,
            'code' => 'D02',
            'name' => 'Bo mon QTKD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lecturer1 = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV001',
            'full_name' => 'Nguyen Van A',
            'email' => 'gv001@local.test',
            'phone' => null,
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $dept1,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $lecturer2 = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV002',
            'full_name' => 'Tran Thi B',
            'email' => 'gv002@local.test',
            'phone' => null,
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $dept2,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $year1 = DB::table('academic_years')->where('code', '2024-2025')->value('id');
        $year2 = DB::table('academic_years')->insertGetId([
            'code' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
            'is_active' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kindId = DB::table('activity_kinds')->where('code', 'paper')->value('id');
        $approvedStatus = DB::table('activity_statuses')->where('code', 'approved')->value('id');
        $submittedStatus = DB::table('activity_statuses')->where('code', 'submitted')->value('id');
        $rejectedStatus = DB::table('activity_statuses')->where('code', 'rejected')->value('id');

        DB::table('research_activities')->insert([
            [
                'activity_code' => 'ACT-001',
                'owner_lecturer_id' => $lecturer1,
                'kind_id' => $kindId,
                'type_id' => null,
                'academic_year_id' => $year1,
                'status_id' => $approvedStatus,
                'title' => 'Paper A',
                'abstract' => null,
                'start_date' => null,
                'end_date' => null,
                'quantity' => 1,
                'submitted_at' => null,
                'approved_at' => now(),
                'total_hours_calc' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'activity_code' => 'ACT-002',
                'owner_lecturer_id' => $lecturer1,
                'kind_id' => $kindId,
                'type_id' => null,
                'academic_year_id' => $year1,
                'status_id' => $submittedStatus,
                'title' => 'Paper B',
                'abstract' => null,
                'start_date' => null,
                'end_date' => null,
                'quantity' => 1,
                'submitted_at' => now(),
                'approved_at' => null,
                'total_hours_calc' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'activity_code' => 'ACT-003',
                'owner_lecturer_id' => $lecturer2,
                'kind_id' => $kindId,
                'type_id' => null,
                'academic_year_id' => $year1,
                'status_id' => $rejectedStatus,
                'title' => 'Paper C',
                'abstract' => null,
                'start_date' => null,
                'end_date' => null,
                'quantity' => 1,
                'submitted_at' => now(),
                'approved_at' => null,
                'total_hours_calc' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'activity_code' => 'ACT-004',
                'owner_lecturer_id' => $lecturer2,
                'kind_id' => $kindId,
                'type_id' => null,
                'academic_year_id' => $year2,
                'status_id' => $approvedStatus,
                'title' => 'Paper D',
                'abstract' => null,
                'start_date' => null,
                'end_date' => null,
                'quantity' => 1,
                'submitted_at' => null,
                'approved_at' => now(),
                'total_hours_calc' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson("/api/admin/works/lecturers/summary?academic_year_id={$year1}");
        $response->assertOk();

        $payload = $response->json('data');
        $row1 = collect($payload)->firstWhere('lecturer_code', 'GV001');
        $row2 = collect($payload)->firstWhere('lecturer_code', 'GV002');

        $this->assertNotNull($row1);
        $this->assertNotNull($row2);

        $this->assertSame(2, $row1['total_declared_research_work_count']);
        $this->assertSame(1, $row1['approved_research_work_count']);
        $this->assertSame(1, $row1['pending_research_work_count']);
        $this->assertSame(0, $row1['rejected_research_work_count']);

        $this->assertSame(1, $row2['total_declared_research_work_count']);
        $this->assertSame(0, $row2['approved_research_work_count']);
        $this->assertSame(0, $row2['pending_research_work_count']);
        $this->assertSame(1, $row2['rejected_research_work_count']);

        $onlyFaculty1 = $this->getJson("/api/admin/works/lecturers/summary?faculty_id={$faculty1}");
        $onlyFaculty1->assertOk();
        $onlyFacultyPayload = $onlyFaculty1->json('data');
        $this->assertCount(1, $onlyFacultyPayload);
        $this->assertSame('GV001', $onlyFacultyPayload[0]['lecturer_code']);

        $onlyApproved = $this->getJson("/api/admin/works/lecturers/summary?academic_year_id={$year1}&status_mode=approved");
        $onlyApproved->assertOk();
        $row1Approved = collect($onlyApproved->json('data'))->firstWhere('lecturer_code', 'GV001');
        $this->assertSame(1, $row1Approved['total_declared_research_work_count']);
        $this->assertSame(1, $row1Approved['approved_research_work_count']);
        $this->assertSame(0, $row1Approved['pending_research_work_count']);
        $this->assertSame(0, $row1Approved['rejected_research_work_count']);

        $approvedList = $this->getJson("/api/admin/works/lecturers/{$lecturer1}/approved?academic_year_id={$year1}");
        $approvedList->assertOk();
        $this->assertCount(1, $approvedList->json('data'));
    }

    /** @test */
    public function admin_can_export_summary_files()
    {
        $admin = User::create([
            'name' => 'Admin Export',
            'email' => 'admin-export@local.test',
            'password' => 'password',
        ]);
        $admin->syncRoles(['ADMIN']);

        Sanctum::actingAs($admin);

        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'F03',
            'name' => 'Khoa X',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deptId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'D03',
            'name' => 'Bo mon X',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('lecturers')->insert([
            'user_id' => null,
            'code' => 'GV-EXP',
            'full_name' => 'Export User',
            'email' => 'exp@local.test',
            'phone' => null,
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $deptId,
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $excel = $this->get('/api/admin/works/lecturers/summary/export/excel');
        $excel->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $excel->headers->get('content-type')
        );
        $this->assertStringContainsString('attachment;', $excel->headers->get('content-disposition'));

        $pdf = $this->get('/api/admin/works/lecturers/summary/export/pdf');
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', $pdf->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }
}
