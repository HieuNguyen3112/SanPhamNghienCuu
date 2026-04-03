<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUniversityApprovalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_university_approval_list_route_is_removed(): void
    {
        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $user = User::factory()->create();
        $user->assignRole('SCIENCE_OFFICE');

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/uni-approvals')->assertStatus(404);
    }

    public function test_admin_university_approval_detail_route_is_removed(): void
    {
        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $user = User::factory()->create();
        $user->assignRole('SCIENCE_OFFICE');

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/uni-approvals/1')->assertStatus(404);
    }

    public function test_admin_university_approval_finalize_route_is_removed(): void
    {
        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $user = User::factory()->create();
        $user->assignRole('SCIENCE_OFFICE');

        Sanctum::actingAs($user);

        $this->putJson('/api/admin/uni-approvals/1/finalize', [])->assertStatus(404);
    }

    public function test_admin_university_approval_reject_route_is_removed(): void
    {
        Role::findOrCreate('SCIENCE_OFFICE', 'web');

        $user = User::factory()->create();
        $user->assignRole('SCIENCE_OFFICE');

        Sanctum::actingAs($user);

        $this->putJson('/api/admin/uni-approvals/1/reject', [])->assertStatus(404);
    }
}
