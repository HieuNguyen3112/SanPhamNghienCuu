<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Lecturer;
use App\Models\LecturerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('GV', 'web');
    }

    protected function createLecturerFor(User $user): Lecturer
    {
        $lec = Lecturer::create([
            'user_id' => $user->id,
            'code' => 'GV-' . $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => '0900' . $user->id,
            'department_id' => null,
            'degree_id' => null,
            'academic_rank_id' => null,
            'active' => true,
        ]);

        LecturerProfile::create([
            'lecturer_id' => $lec->id,
            'gender' => 'Nam',
            'date_of_birth' => '1990-01-01',
        ]);

        return $lec;
    }

    /** @test */
    public function can_get_own_profile()
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        $this->createLecturerFor($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile/me')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['user', 'lecturer']])
            ->assertJsonPath('data.user.id', $user->id);
    }

    /** @test */
    public function update_contact_updates_only_current_user()
    {
        $userA = User::factory()->create(['name' => 'User A', 'email' => 'a@example.com']);
        $userA->assignRole('GV');
        $lecA = $this->createLecturerFor($userA);

        $userB = User::factory()->create(['name' => 'User B', 'email' => 'b@example.com']);
        $userB->assignRole('GV');
        $lecB = $this->createLecturerFor($userB);

        Sanctum::actingAs($userB);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/contact', [
          'full_name' => 'Lecturer B Updated',
          'phone' => '09999999',
          'gender' => 'Nữ',
        ])->assertStatus(200)
          ->assertJson(['message' => 'profile contact updated']);

        $this->assertDatabaseHas('lecturers', [
            'id' => $lecB->id,
            'full_name' => 'Lecturer B Updated',
            'phone' => '09999999',
        ]);

        $this->assertDatabaseMissing('lecturers', [
            'id' => $lecA->id,
            'full_name' => 'Lecturer B Updated',
        ]);
    }
}
