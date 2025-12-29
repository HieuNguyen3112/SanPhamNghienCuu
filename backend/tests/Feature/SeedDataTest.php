<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\LecturerLanguageProficiency;
use App\Models\LecturerPartyMembership;
use App\Models\LecturerProfile;
use App\Models\LecturerTrainingHistory;
use App\Models\LecturerWorkHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = \Database\Seeders\DatabaseSeeder::class;

    /** @test */
    public function seeded_auth_and_profile_data_exist()
    {
        $this->assertTrue(Role::where('name', 'GV')->exists());
        $this->assertTrue(Role::where('name', 'ADMIN')->exists());

        $user = User::where('email', 'gv@local.test')->first();
        $this->assertNotNull($user);

        $lecturer = Lecturer::where('user_id', $user->id)->first();
        $this->assertNotNull($lecturer);

        $this->assertTrue(LecturerProfile::where('lecturer_id', $lecturer->id)->exists());
        $this->assertTrue(LecturerPartyMembership::where('lecturer_id', $lecturer->id)->exists());
        $this->assertTrue(LecturerTrainingHistory::where('lecturer_id', $lecturer->id)->exists());
        $this->assertTrue(LecturerWorkHistory::where('lecturer_id', $lecturer->id)->exists());
        $this->assertTrue(LecturerLanguageProficiency::where('lecturer_id', $lecturer->id)->exists());

        $this->assertTrue(DB::table('activity_kinds')->whereIn('code', [
            'paper',
            'book',
            'project',
            'conference',
        ])->count() >= 4);
        $this->assertTrue(DB::table('activity_statuses')->where('code', 'approved')->exists());

        $approvedStatusId = DB::table('activity_statuses')->where('code', 'approved')->value('id');
        $this->assertNotNull($approvedStatusId);

        $this->assertTrue(DB::table('research_activities')
            ->where('owner_lecturer_id', $lecturer->id)
            ->where('status_id', $approvedStatusId)
            ->exists());
    }

    /** @test */
    public function seeded_user_can_read_profile_me()
    {
        $user = User::where('email', 'gv@local.test')->first();
        $this->assertNotNull($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile/me')
            ->assertStatus(200)
            ->assertJsonPath('data.user.email', 'gv@local.test');
    }
}
