<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['ADMIN', 'GV'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    /** @test */
    public function admin_ping_requires_auth()
    {
        $this->getJson('/api/admin/ping')->assertStatus(401);
    }

    /** @test */
    public function admin_ping_forbidden_for_non_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('GV');

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/ping')->assertStatus(403);
    }

    /** @test */
    public function admin_ping_allows_admin_role()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/ping')
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }
}
