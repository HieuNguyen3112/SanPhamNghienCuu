<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacultyLecturerAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('DEPARTMENT_BOARD', 'web');
        Role::findOrCreate('LECTURER', 'web');
    }

    public function test_department_board_can_create_lecturer_account_in_own_department_only(): void
    {
        [$facultyId, $departmentA, $departmentB] = $this->seedFacultyDepartments();

        $departmentAdmin = User::factory()->create([
            'email' => 'dept-admin@uni.test',
            'password' => bcrypt('secret123'),
            'email_verified_at' => now(),
        ]);
        $departmentAdmin->assignRole('DEPARTMENT_BOARD');

        Lecturer::create([
            'user_id' => $departmentAdmin->id,
            'code' => 'DL001',
            'full_name' => 'Department Admin',
            'email' => 'dept-admin@uni.test',
            'department_id' => $departmentA,
            'active' => true,
        ]);

        Sanctum::actingAs($departmentAdmin);

        $response = $this->postJson('/api/faculty/users/lecturer-accounts', [
            'lecturer_code' => 'GV900',
            'full_name' => 'Nguyen Van Lecturer',
            'email' => 'gv900@uni.test',
            'phone_number' => '0988999777',
            'academic_title' => 'Giang vien',
            'degree_id' => null,
            'academic_rank_id' => null,
            'status' => 'ACTIVE',
            // Malicious fields should be ignored by design.
            'role' => 'DEPARTMENT_BOARD',
            'role_keys' => ['DEPARTMENT_BOARD'],
            'department_id' => $departmentB,
            'unit_id' => $departmentB,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lecturer_code', 'GV900')
            ->assertJsonPath('data.unit_id', $departmentA)
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonPath('data.role_keys.0', 'LECTURER');

        $createdUserId = (int) DB::table('users')->where('email', 'gv900@uni.test')->value('id');

        $this->assertDatabaseHas('users', [
            'id' => $createdUserId,
            'email' => 'gv900@uni.test',
            'must_change_password' => true,
        ]);

        $this->assertDatabaseHas('lecturers', [
            'user_id' => $createdUserId,
            'code' => 'GV900',
            'department_id' => $departmentA,
            'active' => true,
        ]);

        $createdUser = User::query()->findOrFail($createdUserId);
        $this->assertTrue($createdUser->hasRole('LECTURER', 'web'));
        $this->assertFalse($createdUser->hasRole('DEPARTMENT_BOARD', 'web'));

        $this->assertDatabaseHas('audit_logs', [
            'action_code' => 'FACULTY_LECTURER_ACCOUNT_CREATED',
            'actor_user_id' => $departmentAdmin->id,
            'faculty_id' => $facultyId,
            'result_status' => 'success',
        ]);
    }

    public function test_create_lecturer_rejects_duplicate_email_or_lecturer_code(): void
    {
        [, $departmentA] = $this->seedFacultyDepartments();

        $departmentAdmin = User::factory()->create([
            'email' => 'dept-admin-2@uni.test',
            'email_verified_at' => now(),
        ]);
        $departmentAdmin->assignRole('DEPARTMENT_BOARD');

        Lecturer::create([
            'user_id' => $departmentAdmin->id,
            'code' => 'DL002',
            'full_name' => 'Department Admin 2',
            'email' => 'dept-admin-2@uni.test',
            'department_id' => $departmentA,
            'active' => true,
        ]);

        User::factory()->create([
            'email' => 'dup-email@uni.test',
        ]);

        Lecturer::create([
            'user_id' => null,
            'code' => 'GV_EXIST',
            'full_name' => 'Existing Lecturer',
            'email' => 'existing@uni.test',
            'department_id' => $departmentA,
            'active' => true,
        ]);

        Sanctum::actingAs($departmentAdmin);

        $this->postJson('/api/faculty/users/lecturer-accounts', [
            'lecturer_code' => 'GV_EXIST',
            'full_name' => 'Will Fail',
            'email' => 'dup-email@uni.test',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['lecturer_code', 'email']);
    }

    private function seedFacultyDepartments(): array
    {
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'CNTT',
            'name' => 'Khoa Cong nghe thong tin',
        ]);

        $departmentA = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'BM01',
            'name' => 'Bo mon Phan mem',
        ]);

        $departmentB = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'BM02',
            'name' => 'Bo mon He thong',
        ]);

        return [$facultyId, $departmentA, $departmentB];
    }
}
