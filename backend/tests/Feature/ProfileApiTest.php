<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Lecturer;
use App\Models\LecturerProfile;
use App\Models\Department;
use App\Models\LecturerWorkHistory;
use App\Models\LecturerTrainingHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private ?Department $department = null;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['GV', 'DL', 'QL', 'ADMIN'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    protected function createDepartment(): Department
    {
        static $sequence = 1;

        $facultyId = DB::table('faculties')->insertGetId([
            'code' => 'FAC-' . $sequence,
            'name' => 'Faculty ' . $sequence,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $department = Department::create([
            'faculty_id' => $facultyId,
            'code' => 'DEP-' . $sequence,
            'name' => 'Department ' . $sequence,
        ]);

        $sequence++;

        return $department;
    }

    protected function createDegree(): array
    {
        static $sequence = 1;

        $degreeName = 'Degree ' . $sequence;
        $degreeId = DB::table('degrees')->insertGetId([
            'code' => 'DEG-' . $sequence,
            'name' => $degreeName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence++;

        return [
            'id' => $degreeId,
            'name' => $degreeName,
        ];
    }

    protected function createAcademicRank(): array
    {
        static $sequence = 1;

        $rankName = 'Rank ' . $sequence;
        $rankId = DB::table('academic_ranks')->insertGetId([
            'code' => 'RANK-' . $sequence,
            'name' => $rankName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence++;

        return [
            'id' => $rankId,
            'name' => $rankName,
        ];
    }

    protected function createResearchLookups(): array
    {
        $academicYearId = DB::table('academic_years')->insertGetId([
            'code' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-08-31',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $statusApprovedId = DB::table('activity_statuses')->insertGetId([
            'code' => 'approved',
            'name' => 'Approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $statusDraftId = DB::table('activity_statuses')->insertGetId([
            'code' => 'draft',
            'name' => 'Draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $statusSubmittedId = DB::table('activity_statuses')->insertGetId([
            'code' => 'submitted',
            'name' => 'Submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $statusRejectedId = DB::table('activity_statuses')->insertGetId([
            'code' => 'rejected',
            'name' => 'Rejected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kindIds = [];
        foreach ([
            'paper' => 'Paper',
            'book' => 'Book',
            'project' => 'Project',
            'conference' => 'Conference',
        ] as $code => $name) {
            $kindIds[$code] = DB::table('activity_kinds')->insertGetId([
                'code' => $code,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $memberRoleId = DB::table('member_roles')->insertGetId([
            'code' => 'principal',
            'name' => 'Principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'academic_year_id' => $academicYearId,
            'status_approved_id' => $statusApprovedId,
            'status_draft_id' => $statusDraftId,
            'status_submitted_id' => $statusSubmittedId,
            'status_rejected_id' => $statusRejectedId,
            'kind_ids' => $kindIds,
            'member_role_id' => $memberRoleId,
        ];
    }

    protected function createLecturerFor(User $user): Lecturer
    {
        if (! $this->department) {
            $this->department = $this->createDepartment();
        }

        $lec = Lecturer::create([
            'user_id' => $user->id,
            'code' => 'GV-' . $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => '0900' . $user->id,
            'department_id' => $this->department->id,
            'degree_id' => null,
            'academic_rank_id' => null,
            'active' => true,
        ]);

        LecturerProfile::create([
            'lecturer_id' => $lec->id,
            'gender' => 'Nam',
            'date_of_birth' => '1990-01-01',
        ]);

        return $lec;
    }

    protected function actingAsLecturer(): array
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        $lecturer = $this->createLecturerFor($user);

        Sanctum::actingAs($user);

        return [$user, $lecturer];
    }

    /** @test */
    public function guest_cannot_get_profile()
    {
        $this->getJson('/api/profile/me')
            ->assertStatus(401);
    }

    /** @test */
    public function can_get_own_profile()
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        $this->createLecturerFor($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile/me')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'lecturer',
                    'profile',
                    'party_membership',
                    'academic_titles',
                    'educations',
                    'latest_education',
                    'languages',
                    'research_areas',
                    'work_histories',
                    'latest_work_history',
                    'scientific_profile' => [
                        'personal_info',
                        'contact_info',
                        'academic_info',
                        'research_fields',
                        'languages',
                        'work_histories',
                        'educations',
                        'party_membership',
                    ],
                    'research_works' => [
                        'counts_by_kind',
                        'items',
                        'items_by_kind',
                    ],
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.roles', ['LECTURER'])
            ->assertJsonPath('data.user.backend_roles', ['GV']);
    }

    /** @test */
    public function profile_overview_alias_returns_profile()
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        $this->createLecturerFor($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile/overview')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'lecturer',
                    'profile',
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id);
    }

    /** @test */
    public function scientific_profile_returns_approved_research_works_only()
    {
        [$user, $lecturer] = $this->actingAsLecturer();
        $lookups = $this->createResearchLookups();

        $approvedId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-APPROVED-1',
            'owner_lecturer_id' => $lecturer->id,
            'kind_id' => $lookups['kind_ids']['paper'],
            'type_id' => null,
            'academic_year_id' => $lookups['academic_year_id'],
            'status_id' => $lookups['status_approved_id'],
            'title' => 'Approved Paper',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-01',
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('paper_details')->insert([
            'activity_id' => $approvedId,
            'journal_name' => 'Approved Journal',
            'year' => 2024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $approvedId,
            'lecturer_id' => $lecturer->id,
            'member_role_id' => $lookups['member_role_id'],
            'contribution_share' => 1,
            'hours_assigned' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rejectedId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-REJECTED-1',
            'owner_lecturer_id' => $lecturer->id,
            'kind_id' => $lookups['kind_ids']['paper'],
            'type_id' => null,
            'academic_year_id' => $lookups['academic_year_id'],
            'status_id' => $lookups['status_rejected_id'],
            'title' => 'Rejected Paper',
            'start_date' => '2024-02-01',
            'end_date' => '2024-07-01',
            'quantity' => 1,
            'submitted_at' => now(),
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('paper_details')->insert([
            'activity_id' => $rejectedId,
            'journal_name' => 'Rejected Journal',
            'year' => 2024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('research_activity_members')->insert([
            'activity_id' => $rejectedId,
            'lecturer_id' => $lecturer->id,
            'member_role_id' => $lookups['member_role_id'],
            'contribution_share' => 1,
            'hours_assigned' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/profile/me')
            ->assertStatus(200)
            ->assertJsonPath('data.research_works.counts_by_kind.paper', 1)
            ->assertJsonPath('data.research_works.counts_by_kind.total', 1)
            ->assertJsonPath('data.research_works.items.0.activity_id', $approvedId)
            ->assertJsonPath('data.research_works.items.0.status_code', 'approved');

        $items = $response->json('data.research_works.items');
        $this->assertCount(1, $items);
    }

    /** @test */
    public function can_get_profile_without_existing_lecturer()
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        $this->createDepartment();

        Sanctum::actingAs($user);

        $this->getJson('/api/profile/me')
            ->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('lecturers', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function department_board_can_view_other_lecturer_profile()
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('DL');

        $subject = User::factory()->create();
        $subject->assignRole('GV');
        $lecturer = $this->createLecturerFor($subject);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/profile/lecturers/' . $lecturer->id)
            ->assertStatus(200)
            ->assertJsonPath('data.lecturer.id', $lecturer->id)
            ->assertJsonPath('data.user.id', $subject->id);
    }

    /** @test */
    public function lecturer_cannot_view_other_lecturer_profile()
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('GV');
        $this->createLecturerFor($viewer);

        $subject = User::factory()->create();
        $subject->assignRole('GV');
        $lecturer = $this->createLecturerFor($subject);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/profile/lecturers/' . $lecturer->id)
            ->assertStatus(403);
    }

    /** @test */
    public function update_contact_updates_only_current_user()
    {
        $userA = User::factory()->create(['name' => 'User A', 'email' => 'a@example.com']);
        $userA->assignRole('GV');
        $lecA = $this->createLecturerFor($userA);

        $userB = User::factory()->create(['name' => 'User B', 'email' => 'b@example.com']);
        $userB->assignRole('GV');
        $lecB = $this->createLecturerFor($userB);

        Sanctum::actingAs($userB);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/contact', [
          'full_name' => 'Lecturer B Updated',
          'phone' => '09999999',
          'department_id' => null,
          'degree_id' => null,
          'academic_rank_id' => null,
          'gender' => 'Nữ',
        ])->assertStatus(200)
          ->assertJson(['message' => 'profile contact updated']);

        $this->assertDatabaseHas('lecturers', [
            'id' => $lecB->id,
            'full_name' => 'Lecturer B Updated',
            'phone' => '09999999',
            'department_id' => $lecB->department_id,
        ]);

        $this->assertDatabaseMissing('lecturers', [
            'id' => $lecA->id,
            'full_name' => 'Lecturer B Updated',
        ]);
    }

    /** @test */
    public function update_contact_accepts_nullable_fk_fields()
    {
        $user = User::factory()->create();
        $user->assignRole('GV');
        $lec = $this->createLecturerFor($user);

        Sanctum::actingAs($user);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/contact', [
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => null,
        ])->assertStatus(200)
          ->assertJson(['message' => 'profile contact updated']);

        $this->assertDatabaseHas('lecturers', [
            'id' => $lec->id,
            'degree_id' => null,
            'academic_rank_id' => null,
            'department_id' => $lec->department_id,
        ]);
    }

    /** @test */
    public function department_board_cannot_update_contact()
    {
        $user = User::factory()->create();
        $user->assignRole('DL');
        $this->createLecturerFor($user);

        Sanctum::actingAs($user);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/contact', [
            'full_name' => 'Blocked Update',
        ])->assertStatus(403);
    }

    /** @test */
    public function can_update_academic_titles_with_nullable_ids()
    {
        [, $lec] = $this->actingAsLecturer();

        $degree = $this->createDegree();
        $rank = $this->createAcademicRank();

        $lec->update([
            'degree_id' => $degree['id'],
            'academic_rank_id' => $rank['id'],
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/academic-titles', [
            'items' => [
                [
                    'degree_id' => null,
                    'academic_rank_id' => null,
                ],
            ],
        ])->assertStatus(200)
            ->assertJson(['message' => 'academic titles updated']);

        $this->assertDatabaseHas('lecturers', [
            'id' => $lec->id,
            'degree_id' => null,
            'academic_rank_id' => null,
        ]);
    }

    /** @test */
    public function can_update_academic_titles_with_ids()
    {
        [, $lec] = $this->actingAsLecturer();

        $degree = $this->createDegree();
        $rank = $this->createAcademicRank();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/academic-titles', [
            'items' => [
                [
                    'degree_id' => $degree['id'],
                    'academic_rank_id' => $rank['id'],
                ],
            ],
        ])->assertStatus(200)
            ->assertJson(['message' => 'academic titles updated']);

        $this->assertDatabaseHas('lecturers', [
            'id' => $lec->id,
            'degree_id' => $degree['id'],
            'academic_rank_id' => $rank['id'],
        ]);
    }

    /** @test */
    public function research_areas_accepts_nullable_items()
    {
        [, $lec] = $this->actingAsLecturer();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/research-areas', [
            'items' => [],
        ])->assertStatus(200)
            ->assertJson(['message' => 'research areas updated']);

        $this->assertDatabaseHas('lecturer_profiles', [
            'lecturer_id' => $lec->id,
            'research_area' => null,
        ]);
    }

    /** @test */
    public function research_areas_truncates_long_values()
    {
        [, $lec] = $this->actingAsLecturer();

        $longArea = str_repeat('A', 300);
        $longSpecialization = str_repeat('B', 300);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/research-areas', [
            'items' => [$longArea],
            'teaching_specialization' => $longSpecialization,
        ])->assertStatus(200)
            ->assertJson(['message' => 'research areas updated']);

        $this->assertDatabaseHas('lecturer_profiles', [
            'lecturer_id' => $lec->id,
            'research_area' => substr($longArea, 0, 255),
            'teaching_specialization' => substr($longSpecialization, 0, 255),
        ]);
    }

    /** @test */
    public function can_sync_languages()
    {
        [, $lec] = $this->actingAsLecturer();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/languages', [
            'items' => [
                [
                    'language' => 'English',
                    'level' => 'B2',
                    'certificate_name' => 'IELTS',
                    'certificate_level' => 'B2',
                    'certificate_score' => '6.5',
                    'certificate_issuer' => 'IDP',
                    'issue_date' => '2020-01-01',
                    'expire_date' => '2022-01-01',
                    'note' => 'Test note',
                    'is_native' => false,
                ],
            ],
        ])->assertStatus(200)
            ->assertJson(['message' => 'languages synced'])
            ->assertJsonPath('data.0.language', 'English')
            ->assertJsonPath('data.0.level', 'B2');

        $this->assertDatabaseHas('lecturer_language_proficiencies', [
            'lecturer_id' => $lec->id,
            'language' => 'English',
            'proficiency_level' => 'B2',
            'certificate_name' => 'IELTS',
            'certificate_level' => 'B2',
            'certificate_score' => '6.5',
            'issued_by' => 'IDP',
        ]);
    }

    /** @test */
    public function can_list_work_histories()
    {
        [$user, $lec] = $this->actingAsLecturer();

        $historyA = LecturerWorkHistory::create([
            'lecturer_id' => $lec->id,
            'organization' => 'Org A',
            'start_date' => '2022-01-01',
        ]);
        $historyB = LecturerWorkHistory::create([
            'lecturer_id' => $lec->id,
            'organization' => 'Org B',
            'start_date' => null,
        ]);
        $historyC = LecturerWorkHistory::create([
            'lecturer_id' => $lec->id,
            'organization' => 'Org C',
            'start_date' => '2023-01-01',
        ]);

        $this->getJson('/api/profile/work-histories')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $historyC->id)
            ->assertJsonPath('data.1.id', $historyA->id)
            ->assertJsonPath('data.2.id', $historyB->id);
    }

    /** @test */
    public function list_work_histories_returns_empty_when_none()
    {
        $this->actingAsLecturer();

        $this->getJson('/api/profile/work-histories')
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    /** @test */
    public function can_create_work_history()
    {
        [$user, $lec] = $this->actingAsLecturer();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->postJson('/api/profile/work-histories', [
            'organization' => 'Org A',
            'position' => 'Dev',
            'start_date' => '2020-01-01',
            'is_current' => true,
        ])->assertStatus(200)
            ->assertJson(['message' => 'work history created']);

        $this->assertDatabaseHas('lecturer_work_histories', [
            'lecturer_id' => $lec->id,
            'organization' => 'Org A',
            'position' => 'Dev',
        ]);
    }

    /** @test */
    public function can_update_work_history()
    {
        [$user, $lec] = $this->actingAsLecturer();

        $history = LecturerWorkHistory::create([
            'lecturer_id' => $lec->id,
            'organization' => 'Org A',
            'position' => 'Dev',
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/work-histories/' . $history->id, [
            'organization' => 'Org Updated',
            'position' => 'Lead',
        ])->assertStatus(200)
            ->assertJson(['message' => 'work history updated']);

        $this->assertDatabaseHas('lecturer_work_histories', [
            'id' => $history->id,
            'organization' => 'Org Updated',
            'position' => 'Lead',
        ]);
    }

    /** @test */
    public function can_delete_work_history()
    {
        [$user, $lec] = $this->actingAsLecturer();

        $history = LecturerWorkHistory::create([
            'lecturer_id' => $lec->id,
            'organization' => 'Org A',
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->deleteJson('/api/profile/work-histories/' . $history->id)
            ->assertStatus(200)
            ->assertJson(['message' => 'work history deleted']);

        $this->assertDatabaseMissing('lecturer_work_histories', [
            'id' => $history->id,
        ]);
    }

    /** @test */
    public function can_sync_work_histories()
    {
        [$user, $lec] = $this->actingAsLecturer();

        $historyA = LecturerWorkHistory::create([
            'lecturer_id' => $lec->id,
            'organization' => 'Org A',
        ]);
        $historyB = LecturerWorkHistory::create([
            'lecturer_id' => $lec->id,
            'organization' => 'Org B',
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/work-histories', [
            'items' => [
                [
                    'id' => $historyA->id,
                    'organization' => 'Org A Updated',
                ],
                [
                    'organization' => 'Org C',
                    'start_date' => '2021-01-01',
                ],
            ],
        ])->assertStatus(200)
            ->assertJson(['message' => 'work histories synced']);

        $this->assertDatabaseHas('lecturer_work_histories', [
            'id' => $historyA->id,
            'organization' => 'Org A Updated',
        ]);

        $this->assertDatabaseMissing('lecturer_work_histories', [
            'id' => $historyB->id,
        ]);

        $this->assertDatabaseHas('lecturer_work_histories', [
            'lecturer_id' => $lec->id,
            'organization' => 'Org C',
        ]);
    }

    /** @test */
    public function cannot_update_other_lecturer_work_history()
    {
        [$userA] = $this->actingAsLecturer();
        [, $lecB] = $this->actingAsLecturer();

        $history = LecturerWorkHistory::create([
            'lecturer_id' => $lecB->id,
            'organization' => 'Org B',
        ]);

        Sanctum::actingAs($userA);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/work-histories/' . $history->id, [
            'organization' => 'Blocked',
        ])->assertStatus(404);
    }

    /** @test */
    public function cannot_delete_other_lecturer_work_history()
    {
        [$userA] = $this->actingAsLecturer();
        [, $lecB] = $this->actingAsLecturer();

        $history = LecturerWorkHistory::create([
            'lecturer_id' => $lecB->id,
            'organization' => 'Org B',
        ]);

        Sanctum::actingAs($userA);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->deleteJson('/api/profile/work-histories/' . $history->id)
            ->assertStatus(404);
    }

    /** @test */
    public function list_educations_returns_empty_when_none()
    {
        $this->actingAsLecturer();

        $this->getJson('/api/profile/educations')
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    }

    /** @test */
    public function can_create_education()
    {
        [, $lec] = $this->actingAsLecturer();
        $degree = $this->createDegree();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->postJson('/api/profile/educations', [
            'degree_id' => $degree['id'],
            'degree_title' => 'MSc',
            'major' => 'Computer Science',
            'institution' => 'University A',
            'country' => 'VN',
            'start_date' => '2015-01-01',
            'end_date' => '2017-01-01',
        ])->assertStatus(200)
            ->assertJson(['message' => 'education created'])
            ->assertJsonPath('data.degree_id', $degree['id'])
            ->assertJsonPath('data.degree_name', $degree['name']);

        $this->assertDatabaseHas('lecturer_training_histories', [
            'lecturer_id' => $lec->id,
            'degree_id' => $degree['id'],
            'institution' => 'University A',
        ]);
    }

    /** @test */
    public function can_update_education()
    {
        [, $lec] = $this->actingAsLecturer();

        $history = LecturerTrainingHistory::create([
            'lecturer_id' => $lec->id,
            'institution' => 'University A',
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/educations/' . $history->id, [
            'institution' => 'University Updated',
            'major' => 'Math',
        ])->assertStatus(200)
            ->assertJson(['message' => 'education updated']);

        $this->assertDatabaseHas('lecturer_training_histories', [
            'id' => $history->id,
            'institution' => 'University Updated',
            'major' => 'Math',
        ]);
    }

    /** @test */
    public function can_delete_education()
    {
        [, $lec] = $this->actingAsLecturer();

        $history = LecturerTrainingHistory::create([
            'lecturer_id' => $lec->id,
            'institution' => 'University A',
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->deleteJson('/api/profile/educations/' . $history->id)
            ->assertStatus(200)
            ->assertJson(['message' => 'education deleted']);

        $this->assertDatabaseMissing('lecturer_training_histories', [
            'id' => $history->id,
        ]);
    }

    /** @test */
    public function can_sync_educations()
    {
        [, $lec] = $this->actingAsLecturer();

        $historyA = LecturerTrainingHistory::create([
            'lecturer_id' => $lec->id,
            'institution' => 'University A',
        ]);
        $historyB = LecturerTrainingHistory::create([
            'lecturer_id' => $lec->id,
            'institution' => 'University B',
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/educations', [
            'items' => [
                [
                    'id' => $historyA->id,
                    'institution' => 'University A Updated',
                ],
                [
                    'institution' => 'University C',
                    'start_date' => '2010-01-01',
                ],
            ],
        ])->assertStatus(200)
            ->assertJson(['message' => 'educations synced']);

        $this->assertDatabaseHas('lecturer_training_histories', [
            'id' => $historyA->id,
            'institution' => 'University A Updated',
        ]);

        $this->assertDatabaseMissing('lecturer_training_histories', [
            'id' => $historyB->id,
        ]);

        $this->assertDatabaseHas('lecturer_training_histories', [
            'lecturer_id' => $lec->id,
            'institution' => 'University C',
        ]);
    }

    /** @test */
    public function cannot_update_other_lecturer_education()
    {
        [$userA] = $this->actingAsLecturer();
        [, $lecB] = $this->actingAsLecturer();

        $history = LecturerTrainingHistory::create([
            'lecturer_id' => $lecB->id,
            'institution' => 'University B',
        ]);

        Sanctum::actingAs($userA);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->putJson('/api/profile/educations/' . $history->id, [
            'institution' => 'Blocked',
        ])->assertStatus(403);
    }

    /** @test */
    public function cannot_delete_other_lecturer_education()
    {
        [$userA] = $this->actingAsLecturer();
        [, $lecB] = $this->actingAsLecturer();

        $history = LecturerTrainingHistory::create([
            'lecturer_id' => $lecB->id,
            'institution' => 'University B',
        ]);

        Sanctum::actingAs($userA);

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->deleteJson('/api/profile/educations/' . $history->id)
            ->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_create_research_activity()
    {
        $this->postJson('/api/research-activities', [])
            ->assertStatus(401);
    }

    /** @test */
    public function lecturer_can_create_draft_research_activity_and_not_in_approved_list()
    {
        [$user, $lecturer] = $this->actingAsLecturer();
        $lookups = $this->createResearchLookups();

        Sanctum::actingAs($user);

        $payload = [
            'kind_id' => $lookups['kind_ids']['paper'],
            'type_id' => null,
            'academic_year_id' => $lookups['academic_year_id'],
            'title' => 'Draft Paper',
            'abstract' => null,
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-01',
            'quantity' => 1,
            'notes' => null,
        ];

        $response = $this->postJson('/api/research-activities', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.status_id', $lookups['status_draft_id']);

        $activityId = $response->json('data.id');

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'owner_lecturer_id' => $lecturer->id,
            'status_id' => $lookups['status_draft_id'],
        ]);

        $this->putJson('/api/research-activities/' . $activityId . '/paper_details', [
            'journal_name' => 'Draft Journal',
            'year' => 2024,
        ])->assertStatus(200);

        $profileResponse = $this->getJson('/api/profile/me')
            ->assertStatus(200);

        $this->assertEquals(0, $profileResponse->json('data.research_works.counts_by_kind.paper'));
    }

    /** @test */
    public function create_research_activity_requires_title()
    {
        [$user] = $this->actingAsLecturer();
        $lookups = $this->createResearchLookups();

        Sanctum::actingAs($user);

        $this->postJson('/api/research-activities', [
            'kind_id' => $lookups['kind_ids']['paper'],
            'academic_year_id' => $lookups['academic_year_id'],
        ])->assertStatus(422);
    }

    /** @test */
    public function lecturer_can_submit_draft_research_activity()
    {
        [$user, $lecturer] = $this->actingAsLecturer();
        $lookups = $this->createResearchLookups();

        $activityId = DB::table('research_activities')->insertGetId([
            'activity_code' => 'ACT-DRAFT-1',
            'owner_lecturer_id' => $lecturer->id,
            'kind_id' => $lookups['kind_ids']['paper'],
            'type_id' => null,
            'academic_year_id' => $lookups['academic_year_id'],
            'status_id' => $lookups['status_draft_id'],
            'title' => 'Draft Paper',
            'start_date' => '2024-01-01',
            'end_date' => '2024-05-01',
            'quantity' => 1,
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/research-activities/' . $activityId . '/submit')
            ->assertStatus(200)
            ->assertJsonPath('data.status_id', $lookups['status_submitted_id']);

        $this->assertDatabaseHas('research_activities', [
            'id' => $activityId,
            'status_id' => $lookups['status_submitted_id'],
        ]);

        $this->assertDatabaseHas('activity_status_histories', [
            'activity_id' => $activityId,
            'from_status_id' => $lookups['status_draft_id'],
            'to_status_id' => $lookups['status_submitted_id'],
        ]);
    }
}
