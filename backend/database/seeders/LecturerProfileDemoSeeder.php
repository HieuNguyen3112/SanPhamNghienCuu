<?php

namespace Database\Seeders;

use App\Models\AcademicRank;
use App\Models\Degree;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\LecturerLanguageProficiency;
use App\Models\LecturerPartyMembership;
use App\Models\LecturerProfile;
use App\Models\LecturerTrainingHistory;
use App\Models\LecturerWorkHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LecturerProfileDemoSeeder extends Seeder
{
    public function run(): void
    {
        $departmentIds = Department::pluck('id', 'code')->all();
        if (! $departmentIds) {
            return;
        }

        $degreeIds = Degree::pluck('id', 'code')->all();
        $rankIds = AcademicRank::pluck('id', 'code')->all();
        $defaultDepartmentId = reset($departmentIds);

        $seedData = [
            [
                'role' => 'LECTURER',
                'email' => 'gv@local.test',
                'code' => 'GV-001',
                'full_name' => 'Giang Vien',
                'phone' => '0900000001',
                'department_code' => 'BM-KTPM',
                'degree_code' => 'PHD',
                'rank_code' => 'ASSOCIATE_PROFESSOR',
                'profile' => [
                    'gender' => 'Male',
                    'date_of_birth' => '1985-05-20',
                    'place_of_birth' => 'Ho Chi Minh',
                    'ethnicity' => 'Kinh',
                    'hometown' => 'Quang Nam',
                    'personal_email' => 'gv.profile@local.test',
                    'alternate_phone' => '0911111111',
                    'address' => '123 Nguyen Trai, District 1',
                    'emergency_contact_name' => 'Nguyen An',
                    'emergency_contact_phone' => '0988000001',
                    'emergency_contact_relation' => 'Brother',
                    'current_position' => 'Senior Lecturer',
                    'current_unit' => 'Department of IT',
                    'research_area' => 'Artificial Intelligence, Machine Learning, Data Mining',
                    'teaching_specialization' => 'Software Engineering',
                    'orcid_id' => '0000-0002-0000-0001',
                    'google_scholar_profile' => 'https://scholar.google.com/example',
                    'research_gate_profile' => 'https://researchgate.net/profile/giang-vien',
                    'scopus_id' => 'SCOPUS-001',
                    'publons_id' => 'PUBLONS-001',
                    'personal_website' => 'https://example.local',
                    'academic_portfolio_url' => 'https://example.local/portfolio',
                ],
                'party' => [
                    'is_member' => true,
                    'membership_no' => 'DANG-001',
                    'joined_at' => '2010-05-20',
                    'official_at' => '2011-06-01',
                    'joining_place' => 'District 1',
                    'current_branch' => 'IT Branch',
                    'position' => 'Member',
                    'status' => 'Active',
                    'notes' => 'Active member',
                ],
                'languages' => [
                    [
                        'language' => 'English',
                        'proficiency_level' => 'B2',
                        'certificate_name' => 'IELTS',
                        'certificate_level' => 'B2',
                        'certificate_score' => '6.5',
                        'issued_by' => 'IDP',
                        'issued_at' => '2020-01-01',
                        'expires_at' => '2025-01-01',
                        'notes' => 'Academic English proficiency.',
                    ],
                    [
                        'language' => 'Japanese',
                        'proficiency_level' => 'N3',
                        'certificate_name' => 'JLPT',
                        'certificate_level' => 'N3',
                        'certificate_score' => 'N3',
                        'issued_by' => 'JLPT',
                        'issued_at' => '2019-12-01',
                        'notes' => 'Reading and research collaboration.',
                    ],
                ],
                'trainings' => [
                    [
                        'degree_id' => $degreeIds['BACHELOR'] ?? null,
                        'degree_title' => 'Bachelor',
                        'major' => 'Computer Science',
                        'institution' => 'University of Science',
                        'country' => 'VN',
                        'start_date' => '2003-09-01',
                        'end_date' => '2007-06-01',
                        'training_form' => 'Full-time',
                        'notes' => 'Top 10% of cohort.',
                    ],
                    [
                        'degree_id' => $degreeIds['PHD'] ?? null,
                        'degree_title' => 'PhD',
                        'major' => 'Information Systems',
                        'institution' => 'University of Education',
                        'country' => 'VN',
                        'start_date' => '2012-09-01',
                        'end_date' => '2016-06-01',
                        'training_form' => 'Full-time',
                        'certificate_no' => 'PHD-2016-001',
                        'notes' => 'Dissertation on educational data mining.',
                    ],
                ],
                'works' => [
                    [
                        'organization' => 'University of Education',
                        'position' => 'Senior Lecturer',
                        'department' => 'Information Technology',
                        'workplace' => 'Main Campus',
                        'start_date' => '2010-09-01',
                        'is_current' => true,
                        'employment_type' => 'PERMANENT',
                        'notes' => 'Teaching and research',
                    ],
                ],
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv2@local.test',
                'code' => 'GV-002',
                'full_name' => 'Giang Vien 2',
                'phone' => '0900000004',
                'department_code' => 'BM-KTPM',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv3@local.test',
                'code' => 'GV-003',
                'full_name' => 'Giang Vien 3',
                'phone' => '0900000005',
                'department_code' => 'TT-DL',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv4@local.test',
                'code' => 'GV-004',
                'full_name' => 'Giang Vien 4',
                'phone' => '0900000006',
                'department_code' => 'BM-KTPM',
                'degree_code' => 'PHD',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv5@local.test',
                'code' => 'GV-005',
                'full_name' => 'Giang Vien 5',
                'phone' => '0900000007',
                'department_code' => 'BM-TT',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv6@local.test',
                'code' => 'GV-006',
                'full_name' => 'Giang Vien 6',
                'phone' => '0900000008',
                'department_code' => 'BM-TT',
                'degree_code' => 'PHD',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'DEPARTMENT_BOARD',
                'email' => 'khoa@local.test',
                'code' => 'DL-001',
                'full_name' => 'Duyet',
                'phone' => '0900000002',
                'department_code' => 'TT-DL',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
                'profile' => [
                    'gender' => 'Female',
                    'date_of_birth' => '1988-08-10',
                    'place_of_birth' => 'Da Nang',
                    'ethnicity' => 'Kinh',
                    'hometown' => 'Da Nang',
                    'personal_email' => 'dl.profile@local.test',
                    'address' => '456 Le Loi',
                    'current_position' => 'Senior Lecturer',
                    'current_unit' => 'Mathematics',
                    'research_area' => 'Applied Mathematics, Statistics',
                    'teaching_specialization' => 'Probability',
                ],
                'languages' => [
                    [
                        'language' => 'English',
                        'proficiency_level' => 'B1',
                        'certificate_name' => 'TOEIC',
                        'certificate_level' => 'B1',
                        'certificate_score' => '650',
                        'issued_by' => 'IIG',
                        'issued_at' => '2021-03-01',
                        'notes' => 'Teaching materials translation.',
                    ],
                ],
                'trainings' => [
                    [
                        'degree_id' => $degreeIds['MASTER'] ?? null,
                        'degree_title' => 'Master',
                        'major' => 'Mathematics',
                        'institution' => 'University of Science',
                        'country' => 'VN',
                        'start_date' => '2010-09-01',
                        'end_date' => '2012-06-01',
                        'training_form' => 'Full-time',
                        'notes' => 'Thesis in applied statistics.',
                    ],
                ],
                'works' => [
                    [
                        'organization' => 'University of Education',
                        'position' => 'Lecturer',
                        'department' => 'Mathematics',
                        'workplace' => 'Main Campus',
                        'start_date' => '2013-09-01',
                        'is_current' => true,
                        'employment_type' => 'PERMANENT',
                        'notes' => 'Teaching probability and statistics.',
                    ],
                ],
            ],
            [
                'role' => 'SCIENCE_OFFICE',
                'email' => 'truong@local.test',
                'code' => 'QL-001',
                'full_name' => 'Quan Ly',
                'phone' => '0900000003',
                'department_code' => 'BM-TT',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
                'profile' => [
                    'gender' => 'Male',
                    'date_of_birth' => '1982-03-15',
                    'place_of_birth' => 'Ha Noi',
                    'ethnicity' => 'Kinh',
                    'hometown' => 'Ha Noi',
                    'personal_email' => 'ql.profile@local.test',
                    'address' => '789 Tran Hung Dao',
                    'current_position' => 'Manager',
                    'current_unit' => 'Education',
                    'research_area' => 'Education Policy',
                    'teaching_specialization' => 'Education Management',
                ],
                'languages' => [
                    [
                        'language' => 'English',
                        'proficiency_level' => 'C1',
                        'certificate_name' => 'IELTS',
                        'certificate_level' => 'C1',
                        'certificate_score' => '7.5',
                        'issued_by' => 'British Council',
                        'issued_at' => '2022-05-01',
                        'notes' => 'International collaboration.',
                    ],
                ],
                'trainings' => [
                    [
                        'degree_id' => $degreeIds['MASTER'] ?? null,
                        'degree_title' => 'Master',
                        'major' => 'Education Management',
                        'institution' => 'University of Education',
                        'country' => 'VN',
                        'start_date' => '2005-09-01',
                        'end_date' => '2007-06-01',
                        'training_form' => 'Full-time',
                        'notes' => 'Policy-focused program.',
                    ],
                ],
                'works' => [
                    [
                        'organization' => 'University of Education',
                        'position' => 'Manager',
                        'department' => 'Education',
                        'workplace' => 'Main Campus',
                        'start_date' => '2015-01-01',
                        'is_current' => true,
                        'employment_type' => 'PERMANENT',
                        'notes' => 'Faculty management and policy.',
                    ],
                ],
            ],
        ];

        foreach ($seedData as $seed) {
            $user = User::updateOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['full_name'],
                    'password' => Hash::make('Password!123'),
                ]
            );

            if (is_null($user->email_verified_at)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if (! empty($seed['role'])) {
                $user->syncRoles([$seed['role']]);
            }

            $departmentId = $departmentIds[$seed['department_code']] ?? $defaultDepartmentId;
            if (! $departmentId) {
                continue;
            }

            $lecturer = Lecturer::updateOrCreate(
                ['code' => $seed['code']],
                [
                    'user_id' => $user->id,
                    'full_name' => $seed['full_name'],
                    'email' => $user->email,
                    'phone' => $seed['phone'],
                    'department_id' => $departmentId,
                    'degree_id' => $degreeIds[$seed['degree_code']] ?? null,
                    'academic_rank_id' => $rankIds[$seed['rank_code']] ?? null,
                    'active' => true,
                ]
            );

            LecturerProfile::updateOrCreate(
                ['lecturer_id' => $lecturer->id],
                $seed['profile'] ?? []
            );

            if (! empty($seed['party'])) {
                LecturerPartyMembership::updateOrCreate(
                    ['lecturer_id' => $lecturer->id],
                    $seed['party']
                );
            } else {
                LecturerPartyMembership::updateOrCreate(
                    ['lecturer_id' => $lecturer->id],
                    ['is_member' => false]
                );
            }

            foreach ($seed['trainings'] ?? [] as $training) {
                LecturerTrainingHistory::updateOrCreate(
                    [
                        'lecturer_id' => $lecturer->id,
                        'institution' => $training['institution'],
                        'start_date' => $training['start_date'] ?? null,
                        'degree_id' => $training['degree_id'] ?? null,
                    ],
                    array_merge(['lecturer_id' => $lecturer->id], $training)
                );
            }

            foreach ($seed['works'] ?? [] as $work) {
                LecturerWorkHistory::updateOrCreate(
                    [
                        'lecturer_id' => $lecturer->id,
                        'organization' => $work['organization'],
                        'start_date' => $work['start_date'] ?? null,
                    ],
                    array_merge(['lecturer_id' => $lecturer->id], $work)
                );
            }

            foreach ($seed['languages'] ?? [] as $language) {
                LecturerLanguageProficiency::updateOrCreate(
                    [
                        'lecturer_id' => $lecturer->id,
                        'language' => $language['language'],
                    ],
                    array_merge(['lecturer_id' => $lecturer->id], $language)
                );
            }
        }
    }
}
