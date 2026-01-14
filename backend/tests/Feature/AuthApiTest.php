<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('GV', 'web');
    }

    /** @test */
    public function login_success_returns_token_and_user_info()
    {
        $user = User::factory()->create([
            'email' => 'gv@example.com',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignRole('GV');

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $res = $this->postJson('/login', [
            'email' => 'gv@example.com',
            'password' => 'secret123',
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'roles'],
            ])
            ->assertJsonPath('user.roles.0', 'GV');

        $this->assertDatabaseHas('audit_logs', [
            'action_code' => 'LOGIN_SUCCESS',
            'actor_user_id' => $user->id,
            'result_status' => 'success',
        ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'gv@example.com',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignRole('GV');

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $res = $this->postJson('/login', [
            'email' => 'gv@example.com',
            'password' => 'wrong-password',
        ]);

        $res->assertStatus(422)
            ->assertJson(['message' => 'Invalid credentials']);

        $this->assertDatabaseHas('audit_logs', [
            'action_code' => 'LOGIN_FAILED',
            'actor_email' => 'gv@example.com',
            'result_status' => 'failure',
        ]);
    }

    /** @test */
    public function logout_creates_audit_log()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web');
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->postJson('/logout')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action_code' => 'LOGOUT',
            'actor_user_id' => $user->id,
            'result_status' => 'success',
        ]);
    }
}
