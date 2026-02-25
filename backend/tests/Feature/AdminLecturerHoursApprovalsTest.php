<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLecturerHoursApprovalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hours_approvals_list_route_returns_404(): void
    {
        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $user = User::factory()->create();
        $user->assignRole('SCIENCE_OFFICE');

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/hours/approvals')->assertStatus(404);
    }

    public function test_admin_hours_approvals_detail_route_returns_404(): void
    {
        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $user = User::factory()->create();
        $user->assignRole('SCIENCE_OFFICE');

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/hours/approvals/1')->assertStatus(404);
    }
}
