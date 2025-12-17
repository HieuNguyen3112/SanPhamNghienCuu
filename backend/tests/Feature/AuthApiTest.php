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
    }
}
