<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthMeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('GV', 'web');
    }

    public function test_auth_me_unauthenticated_returns_401_json(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'code' => 'UNAUTHENTICATED',
            ]);
    }

    public function test_auth_me_with_token_returns_user_payload(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('GV');

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'email' => $user->email,
            ])
            ->assertJsonFragment([
                'roles' => ['LECTURER'],
                'backend_roles' => ['GV'],
            ]);
    }

    public function test_auth_me_with_stateful_session_returns_user_payload(): void
    {
        config(['sanctum.stateful' => ['localhost']]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('GV');

        $this->actingAs($user);

        $this->withHeader('Origin', 'http://localhost')
            ->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }
}
