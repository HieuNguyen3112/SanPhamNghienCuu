<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LookupApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    /** @test */
    public function can_list_degrees()
    {
        $this->actingAsUser();

        DB::table('degrees')->insert([
            [
                'code' => 'DEG-1',
                'name' => 'Degree A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DEG-2',
                'name' => 'Degree B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->getJson('/api/lookups/degrees')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Degree A')
            ->assertJsonPath('data.1.name', 'Degree B');
    }

    /** @test */
    public function can_list_academic_ranks()
    {
        $this->actingAsUser();

        DB::table('academic_ranks')->insert([
            [
                'code' => 'RANK-1',
                'name' => 'Rank A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'RANK-2',
                'name' => 'Rank B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->getJson('/api/lookups/academic-ranks')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Rank A')
            ->assertJsonPath('data.1.name', 'Rank B');
    }

    /** @test */
    public function can_list_faculties()
    {
        $this->actingAsUser();

        DB::table('faculties')->insert([
            [
                'code' => 'FAC-1',
                'name' => 'Faculty A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FAC-2',
                'name' => 'Faculty B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->getJson('/api/lookups/faculties')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Faculty A')
            ->assertJsonPath('data.1.name', 'Faculty B');
    }

    /** @test */
    public function can_list_departments_with_filter()
    {
        $this->actingAsUser();

        $facultyA = DB::table('faculties')->insertGetId([
            'code' => 'FAC-1',
            'name' => 'Faculty A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $facultyB = DB::table('faculties')->insertGetId([
            'code' => 'FAC-2',
            'name' => 'Faculty B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('departments')->insert([
            [
                'faculty_id' => $facultyA,
                'code' => 'DEP-1',
                'name' => 'Department A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'faculty_id' => $facultyB,
                'code' => 'DEP-2',
                'name' => 'Department B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->getJson('/api/lookups/departments?faculty_id=' . $facultyA)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Department A')
            ->assertJsonPath('data.0.faculty_id', $facultyA);
    }
}
