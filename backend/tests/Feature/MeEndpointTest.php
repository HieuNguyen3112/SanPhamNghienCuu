<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function ensureRole(string $name): Role
    {
        return Role::findOrCreate($name, 'web');
    }

    public function test_guest_get_me_returns_401_json(): void
    {
        $this->getJson('/me')
            ->assertStatus(401)
            ->assertJson([
                'code' => 'UNAUTHENTICATED',
            ]);
    }

    public function test_unverified_user_gets_403_with_unverified_code(): void
    {
        $role = $this->ensureRole('GV');
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->getJson('/me')
            ->assertStatus(403)
            ->assertJson([
                'code' => 'UNVERIFIED_EMAIL',
            ]);
    }

    public function test_user_without_required_role_gets_403_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/me')
            ->assertStatus(403)
            ->assertJson([
                'code' => 'FORBIDDEN_MISSING_ROLE',
            ]);
    }

    public function test_authorized_user_receives_profile_json(): void
    {
        $role = $this->ensureRole('GV');
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->getJson('/me')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'email' => $user->email,
            ])
            ->assertJsonFragment([
                'backend_roles' => ['GV'],
            ]);
    }
}
