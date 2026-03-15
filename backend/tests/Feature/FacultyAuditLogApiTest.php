<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacultyAuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('DEPARTMENT_BOARD', 'web');
    }

    public function test_faculty_audit_log_rejects_reversed_date_range(): void
    {
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'CNTT',
            'name' => 'Khoa Cong nghe thong tin',
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'PM',
            'name' => 'Bo mon Phan mem',
        ]);

        $user = User::factory()->create([
            'email' => 'faculty-audit@test.local',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('DEPARTMENT_BOARD');

        Lecturer::create([
            'user_id' => $user->id,
            'code' => 'GV900',
            'full_name' => 'Can bo khoa',
            'email' => 'faculty-audit@test.local',
            'department_id' => $departmentId,
            'active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/faculty/audit-logs?date_from=2025-05-10&date_to=2025-05-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }
}
