<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLecturerAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('GV', 'web');
        Role::findOrCreate('DL', 'web');
        Role::findOrCreate('QL', 'web');
        Role::findOrCreate('ADMIN', 'web');
    }

    public function test_list_requires_authentication(): void
    {
        $this->getJson('/api/admin/lecturer-accounts')
            ->assertStatus(401);
    }

    public function test_list_forbidden_for_non_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('GV');

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/lecturer-accounts')
            ->assertStatus(403);
    }

    public function test_admin_can_list_with_filters(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        Sanctum::actingAs($admin);

        [$deptA, $deptB] = $this->seedDepartments();

        $lecturerUserA = User::factory()->create([
            'name' => 'gv001',
            'email' => 'gv001@uni.test',
        ]);
        $lecturerUserA->assignRole('GV');

        $lecturerA = Lecturer::create([
            'user_id' => $lecturerUserA->id,
            'code' => 'GV001',
            'full_name' => 'Nguyen Van A',
            'email' => 'gv001@uni.test',
            'department_id' => $deptA,
            'active' => true,
        ]);

        $lecturerUserB = User::factory()->create([
            'name' => 'gv002',
            'email' => 'gv002@uni.test',
        ]);
        $lecturerUserB->assignRole('DL');

        Lecturer::create([
            'user_id' => $lecturerUserB->id,
            'code' => 'GV002',
            'full_name' => 'Tran Thi B',
            'email' => 'gv002@uni.test',
            'department_id' => $deptB,
            'active' => false,
        ]);

        $response = $this->getJson('/api/admin/lecturer-accounts?keyword=GV001&status=active&role_keys[]=LECTURER&unit_id=' . $deptA);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonFragment([
                'lecturer_code' => 'GV001',
                'status' => 'ACTIVE',
            ]);
    }

    public function test_admin_can_update_info(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        Sanctum::actingAs($admin);

        [$deptA, $deptB] = $this->seedDepartments();

        $lecturerUser = User::factory()->create([
            'name' => 'gv010',
            'email' => 'gv010@uni.test',
        ]);
        $lecturerUser->assignRole('GV');

        $lecturer = Lecturer::create([
            'user_id' => $lecturerUser->id,
            'code' => 'GV010',
            'full_name' => 'Old Name',
            'email' => 'gv010@uni.test',
            'department_id' => $deptA,
            'active' => true,
        ]);

        $payload = [
            'full_name' => 'New Name',
            'email' => 'gv010-new@uni.test',
            'unit_id' => $deptB,
            'position_title' => 'Giang vien chinh',
        ];

        $this->putJson('/api/admin/lecturer-accounts/' . $lecturer->id, $payload)
            ->assertStatus(200)
            ->assertJsonPath('data.full_name', 'New Name')
            ->assertJsonPath('data.unit_id', $deptB);

        $this->assertDatabaseHas('lecturers', [
            'id' => $lecturer->id,
            'full_name' => 'New Name',
            'email' => 'gv010-new@uni.test',
            'department_id' => $deptB,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $lecturerUser->id,
            'email' => 'gv010-new@uni.test',
        ]);

        $this->assertDatabaseHas('lecturer_profiles', [
            'lecturer_id' => $lecturer->id,
            'current_position' => 'Giang vien chinh',
        ]);
    }

    public function test_admin_update_rejects_duplicate_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        Sanctum::actingAs($admin);

        $dept = $this->seedDepartments()[0];

        $existingUser = User::factory()->create([
            'email' => 'dup@uni.test',
        ]);

        $lecturerUser = User::factory()->create([
            'name' => 'gv011',
            'email' => 'gv011@uni.test',
        ]);
        $lecturerUser->assignRole('GV');

        $lecturer = Lecturer::create([
            'user_id' => $lecturerUser->id,
            'code' => 'GV011',
            'full_name' => 'Dup Email',
            'email' => 'gv011@uni.test',
            'department_id' => $dept,
            'active' => true,
        ]);

        $this->putJson('/api/admin/lecturer-accounts/' . $lecturer->id, [
            'full_name' => 'Dup Email',
            'email' => $existingUser->email,
            'unit_id' => $dept,
            'position_title' => null,
        ])->assertStatus(422);
    }

    public function test_admin_can_sync_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        Sanctum::actingAs($admin);

        $dept = $this->seedDepartments()[0];

        $lecturerUser = User::factory()->create([
            'name' => 'gv020',
            'email' => 'gv020@uni.test',
        ]);
        $lecturerUser->assignRole('GV');

        $lecturer = Lecturer::create([
            'user_id' => $lecturerUser->id,
            'code' => 'GV020',
            'full_name' => 'Role User',
            'email' => 'gv020@uni.test',
            'department_id' => $dept,
            'active' => true,
        ]);

        $this->putJson('/api/admin/lecturer-accounts/' . $lecturer->id . '/roles', [
            'role_keys' => ['LECTURER', 'DEPARTMENT_BOARD'],
        ])->assertStatus(200);

        $lecturerUser->refresh();
        $this->assertTrue($lecturerUser->hasRole('GV'));
        $this->assertTrue($lecturerUser->hasRole('DL'));
    }

    public function test_admin_can_toggle_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        Sanctum::actingAs($admin);

        $dept = $this->seedDepartments()[0];

        $lecturer = Lecturer::create([
            'user_id' => null,
            'code' => 'GV030',
            'full_name' => 'Inactive User',
            'email' => 'gv030@uni.test',
            'department_id' => $dept,
            'active' => true,
        ]);

        $this->putJson('/api/admin/lecturer-accounts/' . $lecturer->id . '/status', [
            'is_active' => false,
            'reason' => 'Manual disable',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'INACTIVE');

        $this->assertDatabaseHas('lecturers', [
            'id' => $lecturer->id,
            'active' => false,
        ]);
    }

    private function seedDepartments(): array
    {
        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'CNTT',
            'name' => 'Khoa Cong nghe thong tin',
        ]);

        $deptA = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'BM01',
            'name' => 'Bo mon Phan mem',
        ]);

        $deptB = DB::table('departments')->insertGetId([
            'faculty_id' => $facultyId,
            'code' => 'BM02',
            'name' => 'Bo mon He thong',
        ]);

        return [$deptA, $deptB];
    }
}
