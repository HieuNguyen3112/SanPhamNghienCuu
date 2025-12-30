<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\LecturerHourWarningDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLecturerHourWarningsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('ADMIN', 'web');
        Role::findOrCreate('QL', 'web');
    }

    private function seedDemo(): void
    {
        $this->seed(LecturerHourWarningDemoSeeder::class);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_guest_cannot_list(): void
    {
        $this->getJson('/api/admin/hours/warnings')
            ->assertStatus(401);
    }

    public function test_non_admin_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/hours/warnings')
            ->assertStatus(403);
    }

    public function test_admin_can_list(): void
    {
        $this->seedDemo();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/hours/warnings')
            ->assertStatus(200);

        $this->assertNotEmpty($response->json('data.entry_list'));
        $this->assertNotEmpty($response->json('data.faculty_option_list'));
        $this->assertNotEmpty($response->json('data.academic_year_option_list'));
    }

    public function test_filter_by_severity(): void
    {
        $this->seedDemo();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/hours/warnings?severity_filter=SEVERE')
            ->assertStatus(200);

        $items = $response->json('data.entry_list');
        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertSame('SEVERE', $item['severity']);
        }
    }

    public function test_keyword_filter(): void
    {
        $this->seedDemo();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/hours/warnings?keyword=GV-001')
            ->assertStatus(200);

        $items = $response->json('data.entry_list');
        $this->assertCount(1, $items);
        $this->assertSame('GV-001', $items[0]['lecturer_code']);
    }

    public function test_send_warning_requires_reason_note_for_other(): void
    {
        $this->seedDemo();
        $this->actingAsAdmin();

        $lecturerId = DB::table('lecturers')->where('code', 'GV-002')->value('id');
        $this->assertNotNull($lecturerId);

        $this->postJson('/api/admin/hours/warnings/' . $lecturerId . '/send', [
            'academic_year_identifier' => '2024-2025',
            'reason_code' => 'OTHER',
        ])->assertStatus(422);
    }

    public function test_admin_can_send_warning(): void
    {
        $this->seedDemo();
        $this->actingAsAdmin();

        $lecturerId = DB::table('lecturers')->where('code', 'GV-002')->value('id');
        $this->assertNotNull($lecturerId);

        $this->postJson('/api/admin/hours/warnings/' . $lecturerId . '/send', [
            'academic_year_identifier' => '2024-2025',
            'reason_code' => 'MISSING_HOURS',
            'reason_note' => 'Thiếu giờ theo quy định.',
        ])->assertStatus(200);

        $row = DB::table('lecturer_yearly_hours')
            ->where('lecturer_id', $lecturerId)
            ->where('academic_year_id', DB::table('academic_years')->where('code', '2024-2025')->value('id'))
            ->first();

        $this->assertNotNull($row);
        $this->assertTrue(
            Carbon::parse($row->updated_at)->gt(Carbon::parse($row->created_at))
        );
    }
}
