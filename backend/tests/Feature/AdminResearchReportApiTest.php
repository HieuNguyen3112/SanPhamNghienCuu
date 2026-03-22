<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminResearchReportApiTest extends TestCase
{
    use RefreshDatabase;

    private User $scienceOfficeUser;

    private int $approvedStatusId;

    private int $projectKindId;

    private int $departmentId;

    private int $lecturerId;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $this->seedLookupData();
        $this->seedScienceOfficeUser();
    }

    public function test_admin_research_report_returns_valid_empty_payloads(): void
    {
        Sanctum::actingAs($this->scienceOfficeUser);

        $filters = $this->getJson('/api/admin/reports/research/filters')
            ->assertOk();

        $filters->assertJsonPath('data.years', []);
        $filters->assertJsonPath('data.departments.0.id', $this->departmentId);
        $filters->assertHeader('X-SPNC-Research-Report-Signature', 'admin-research-report:2026-03-23.1');
        $filters->assertHeader('X-SPNC-Research-Report-Schema-Status', 'ok');

        $report = $this->getJson('/api/admin/reports/research')
            ->assertOk();

        $report->assertJsonPath('data.kpis.total_count', 0);
        $report->assertJsonPath('data.kpis.project_count', 0);
        $report->assertJsonPath('data.charts.by_year_line.labels', []);
        $report->assertJsonPath('data.table.items', []);
        $report->assertJsonPath('data.table.pagination.total', 0);
        $report->assertJsonPath('data.table.pagination.page', 1);
        $report->assertJsonPath('data.applied_filters.sort', 'year:desc');
    }

    public function test_admin_research_report_handles_missing_year_columns_without_500(): void
    {
        Schema::table('project_details', function ($table) {
            $table->dropColumn('start_month');
        });

        Schema::table('conference_details', function ($table) {
            $table->dropColumn('held_on');
        });

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-RPT-001',
            'owner_lecturer_id' => $this->lecturerId,
            'kind_id' => $this->projectKindId,
            'type_id' => null,
            'academic_year_id' => null,
            'status_id' => $this->approvedStatusId,
            'title' => 'De tai cap truong',
            'abstract' => null,
            'start_date' => '2024-03-10',
            'end_date' => null,
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'total_hours_calc' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('project_details')->insert([
            'activity_id' => $activityId,
            'project_code' => 'DT-2024-01',
            'funding' => null,
            'end_month' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->scienceOfficeUser);

        $filters = $this->getJson('/api/admin/reports/research/filters')
            ->assertOk();

        $this->assertSame(['2024'], $filters->json('data.years'));
        $filters->assertHeader('X-SPNC-Research-Report-Schema-Status', 'mismatch');
        $this->assertStringContainsString(
            'project_details.start_month',
            (string) $filters->headers->get('X-SPNC-Research-Report-Missing-Columns')
        );

        $report = $this->getJson('/api/admin/reports/research')
            ->assertOk();

        $report->assertJsonPath('data.kpis.total_count', 1);
        $report->assertJsonPath('data.kpis.project_count', 1);
        $report->assertJsonPath('data.table.items.0.title', 'De tai cap truong');
        $report->assertJsonPath('data.table.items.0.year', 2024);
        $report->assertJsonPath('data.table.items.0.venue_label', 'DT-2024-01');
        $report->assertJsonPath('data.charts.by_year_line.labels.0', '2024');
    }

    private function seedLookupData(): void
    {
        $now = now();

        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'F01',
            'name' => 'Khoa Khoa hoc',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'D01',
            'name' => 'Bo mon Nghien cuu',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->approvedStatusId = DB::table('activity_statuses')->insertGetId([
            'code' => 'approved',
            'name' => 'Approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->projectKindId = DB::table('activity_kinds')->insertGetId([
            'code' => 'project',
            'name' => 'Project',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedScienceOfficeUser(): void
    {
        $this->scienceOfficeUser = User::factory()->create([
            'email' => 'science-office-research-report@test.local',
        ]);
        $this->scienceOfficeUser->assignRole('SCIENCE_OFFICE');

        $this->lecturerId = DB::table('lecturers')->insertGetId([
            'user_id' => null,
            'code' => 'GV001',
            'full_name' => 'Nguyen Van A',
            'email' => 'gv001@test.local',
            'department_id' => $this->departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
