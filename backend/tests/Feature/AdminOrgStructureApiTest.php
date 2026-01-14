<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrgStructureApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('ADMIN', 'web');
        Role::findOrCreate('QL', 'web');
        Role::findOrCreate('GV', 'web');
    }

    public function test_faculty_list_requires_authentication(): void
    {
        $this->getJson('/api/admin/org-structure/faculties')
            ->assertStatus(401);
    }

    public function test_faculty_list_forbidden_for_non_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/org-structure/faculties')
            ->assertStatus(403);
    }

    public function test_admin_can_create_update_faculty(): void
    {
        $this->actingAsAdmin();

        $create = $this->postJson('/api/admin/org-structure/faculties', [
            'code' => 'CNTT',
            'name' => 'Khoa Cong nghe Thong tin',
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'CNTT');

        $facultyId = $create->json('data.id');

        $this->putJson('/api/admin/org-structure/faculties/' . $facultyId, [
            'code' => 'CNTT2',
            'name' => 'Khoa Cong nghe Thong tin 2',
        ])->assertStatus(200)
            ->assertJsonPath('data.code', 'CNTT2');
    }

    public function test_faculty_validation_duplicate_code(): void
    {
        $this->actingAsAdmin();

        DB::table('faculties')->insert([
            'code' => 'KT',
            'name' => 'Khoa Kinh te',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/admin/org-structure/faculties', [
            'code' => 'KT',
            'name' => 'Khoa Kinh te 2',
        ])->assertStatus(422);
    }

    public function test_faculty_code_change_blocked_when_has_lecturers(): void
    {
        $this->actingAsAdmin();

        $facultyId = $this->seedFaculty('CNTT');
        $departmentId = $this->seedDepartment($facultyId, 'BM01');

        DB::table('lecturers')->insert([
            'code' => 'GV001',
            'full_name' => 'Giang Vien 1',
            'email' => 'gv001@uni.test',
            'department_id' => $departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->putJson('/api/admin/org-structure/faculties/' . $facultyId, [
            'code' => 'CNTT2',
            'name' => 'Khoa Cong nghe Thong tin',
        ])->assertStatus(422);
    }

    public function test_admin_can_create_update_department(): void
    {
        $this->actingAsAdmin();

        $facultyId = $this->seedFaculty('TOAN');

        $create = $this->postJson('/api/admin/org-structure/departments', [
            'faculty_id' => $facultyId,
            'code' => 'BM-TT',
            'name' => 'Bo mon Toan',
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'BM-TT');

        $departmentId = $create->json('data.id');

        $this->putJson('/api/admin/org-structure/departments/' . $departmentId, [
            'faculty_id' => $facultyId,
            'code' => 'BM-TT2',
            'name' => 'Bo mon Toan tin',
        ])->assertStatus(200)
            ->assertJsonPath('data.code', 'BM-TT2');
    }

    public function test_department_validation_invalid_faculty(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/admin/org-structure/departments', [
            'faculty_id' => 9999,
            'code' => 'BM-XX',
            'name' => 'Bo mon Sai',
        ])->assertStatus(422);
    }

    public function test_department_code_change_blocked_when_has_lecturers(): void
    {
        $this->actingAsAdmin();

        $facultyId = $this->seedFaculty('KINH');
        $departmentId = $this->seedDepartment($facultyId, 'BM-KT');

        DB::table('lecturers')->insert([
            'code' => 'GV002',
            'full_name' => 'Giang Vien 2',
            'email' => 'gv002@uni.test',
            'department_id' => $departmentId,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->putJson('/api/admin/org-structure/departments/' . $departmentId, [
            'faculty_id' => $facultyId,
            'code' => 'BM-KT2',
            'name' => 'Bo mon Kinh te',
        ])->assertStatus(422);
    }

    public function test_department_validation_duplicate_code_in_faculty(): void
    {
        $this->actingAsAdmin();

        $facultyId = $this->seedFaculty('GD');
        $this->seedDepartment($facultyId, 'BM-GD');

        $this->postJson('/api/admin/org-structure/departments', [
            'faculty_id' => $facultyId,
            'code' => 'BM-GD',
            'name' => 'Bo mon Trung lap',
        ])->assertStatus(422);
    }

    private function actingAsAdmin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        Sanctum::actingAs($user);
    }

    private function seedFaculty(string $code): int
    {
        return DB::table('faculties')->insertGetId([
            'code' => $code,
            'name' => 'Faculty ' . $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedDepartment(int $facultyId, string $code): int
    {
        return DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => $code,
            'name' => 'Department ' . $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
