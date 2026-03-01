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
                'full_name' => 'Nguyễn Minh Tuấn',
                'phone' => '0900000001',
                'department_code' => 'BM-KTPM',
                'degree_code' => 'PHD',
                'rank_code' => 'ASSOCIATE_PROFESSOR',
                'profile' => [
                    'gender' => 'Male',
                    'date_of_birth' => '1985-05-20',
                    'place_of_birth' => 'Thành phố Hồ Chí Minh',
                    'ethnicity' => 'Kinh',
                    'hometown' => 'Quảng Nam',
                    'personal_email' => 'gv.profile@local.test',
                    'alternate_phone' => '0911111111',
                    'address' => '280 An Dương Vương, Quận 5, TP. Hồ Chí Minh',
                    'emergency_contact_name' => 'Nguyễn Thị Kim Oanh',
                    'emergency_contact_phone' => '0988000001',
                    'emergency_contact_relation' => 'Vợ',
                    'current_position' => 'Giảng viên cao cấp',
                    'current_unit' => 'Khoa Công nghệ Thông tin',
                    'research_area' => 'Trí tuệ nhân tạo, Học máy, Khai phá dữ liệu giáo dục',
                    'teaching_specialization' => 'Kỹ thuật phần mềm',
                    'orcid_id' => '0000-0002-0000-0001',
                    'google_scholar_profile' => 'https://scholar.google.com/example',
                    'research_gate_profile' => 'https://researchgate.net/profile/nguyen-minh-tuan',
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
                    'joining_place' => 'Quận 5, TP. Hồ Chí Minh',
                    'current_branch' => 'Chi bộ Khoa Công nghệ Thông tin',
                    'position' => 'Đảng viên',
                    'status' => 'Active',
                    'notes' => 'Tham gia sinh hoạt định kỳ.',
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
                        'notes' => 'Đủ năng lực công bố quốc tế.',
                    ],
                    [
                        'language' => 'Japanese',
                        'proficiency_level' => 'N3',
                        'certificate_name' => 'JLPT',
                        'certificate_level' => 'N3',
                        'certificate_score' => 'N3',
                        'issued_by' => 'JLPT',
                        'issued_at' => '2019-12-01',
                        'notes' => 'Hỗ trợ hợp tác nghiên cứu với đối tác Nhật Bản.',
                    ],
                ],
                'trainings' => [
                    [
                        'degree_id' => $degreeIds['BACHELOR'] ?? null,
                        'degree_title' => 'Cử nhân',
                        'major' => 'Công nghệ thông tin',
                        'institution' => 'Trường Đại học Khoa học Tự nhiên, ĐHQG-HCM',
                        'country' => 'VN',
                        'start_date' => '2003-09-01',
                        'end_date' => '2007-06-01',
                        'training_form' => 'Chính quy',
                        'notes' => 'Tốt nghiệp loại giỏi.',
                    ],
                    [
                        'degree_id' => $degreeIds['PHD'] ?? null,
                        'degree_title' => 'Tiến sĩ',
                        'major' => 'Hệ thống thông tin',
                        'institution' => 'Trường Đại học Sư phạm TP. Hồ Chí Minh',
                        'country' => 'VN',
                        'start_date' => '2012-09-01',
                        'end_date' => '2016-06-01',
                        'training_form' => 'Chính quy',
                        'certificate_no' => 'PHD-2016-001',
                        'notes' => 'Luận án về khai phá dữ liệu giáo dục.',
                    ],
                ],
                'works' => [
                    [
                        'organization' => 'Trường Đại học Sư phạm Thành phố Hồ Chí Minh',
                        'position' => 'Giảng viên cao cấp',
                        'department' => 'Bộ môn Kỹ thuật Phần mềm',
                        'workplace' => 'Cơ sở chính An Dương Vương',
                        'start_date' => '2010-09-01',
                        'is_current' => true,
                        'employment_type' => 'PERMANENT',
                        'notes' => 'Giảng dạy và nghiên cứu lĩnh vực AI trong giáo dục.',
                    ],
                ],
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv2@local.test',
                'code' => 'GV-002',
                'full_name' => 'Trần Thị Thu Hằng',
                'phone' => '0900000004',
                'department_code' => 'BM-KTPM',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv3@local.test',
                'code' => 'GV-003',
                'full_name' => 'Phạm Quốc Đạt',
                'phone' => '0900000005',
                'department_code' => 'TT-DL',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv4@local.test',
                'code' => 'GV-004',
                'full_name' => 'Lê Hoàng Anh',
                'phone' => '0900000006',
                'department_code' => 'BM-KTPM',
                'degree_code' => 'PHD',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv5@local.test',
                'code' => 'GV-005',
                'full_name' => 'Võ Thị Mỹ Linh',
                'phone' => '0900000007',
                'department_code' => 'BM-TT',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'LECTURER',
                'email' => 'gv6@local.test',
                'code' => 'GV-006',
                'full_name' => 'Bùi Quang Khải',
                'phone' => '0900000008',
                'department_code' => 'BM-TT',
                'degree_code' => 'PHD',
                'rank_code' => 'LECTURER',
            ],
            [
                'role' => 'DEPARTMENT_BOARD',
                'email' => 'khoa@local.test',
                'code' => 'DL-001',
                'full_name' => 'Đặng Thu Hà',
                'phone' => '0900000002',
                'department_code' => 'TT-DL',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
                'profile' => [
                    'gender' => 'Female',
                    'date_of_birth' => '1988-08-10',
                    'place_of_birth' => 'Đà Nẵng',
                    'ethnicity' => 'Kinh',
                    'hometown' => 'Đà Nẵng',
                    'personal_email' => 'dl.profile@local.test',
                    'address' => '95 Lê Lợi, Quận 1, TP. Hồ Chí Minh',
                    'current_position' => 'Phó Trưởng khoa',
                    'current_unit' => 'Khoa Toán – Tin học',
                    'research_area' => 'Toán ứng dụng, Thống kê, Khoa học dữ liệu',
                    'teaching_specialization' => 'Xác suất thống kê',
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
                        'notes' => 'Đọc tài liệu chuyên ngành.',
                    ],
                ],
                'trainings' => [
                    [
                        'degree_id' => $degreeIds['MASTER'] ?? null,
                        'degree_title' => 'Thạc sĩ',
                        'major' => 'Toán ứng dụng',
                        'institution' => 'Trường Đại học Khoa học Tự nhiên, ĐHQG-HCM',
                        'country' => 'VN',
                        'start_date' => '2010-09-01',
                        'end_date' => '2012-06-01',
                        'training_form' => 'Chính quy',
                        'notes' => 'Luận văn về mô hình hóa dữ liệu giáo dục.',
                    ],
                ],
                'works' => [
                    [
                        'organization' => 'Trường Đại học Sư phạm Thành phố Hồ Chí Minh',
                        'position' => 'Giảng viên',
                        'department' => 'Bộ môn Toán – Tin ứng dụng',
                        'workplace' => 'Cơ sở chính An Dương Vương',
                        'start_date' => '2013-09-01',
                        'is_current' => true,
                        'employment_type' => 'PERMANENT',
                        'notes' => 'Giảng dạy xác suất thống kê và khai thác dữ liệu.',
                    ],
                ],
            ],
            [
                'role' => 'SCIENCE_OFFICE',
                'email' => 'truong@local.test',
                'code' => 'QL-001',
                'full_name' => 'Nguyễn Thành Nam',
                'phone' => '0900000003',
                'department_code' => 'BM-TT',
                'degree_code' => 'MASTER',
                'rank_code' => 'LECTURER',
                'profile' => [
                    'gender' => 'Male',
                    'date_of_birth' => '1982-03-15',
                    'place_of_birth' => 'Hà Nội',
                    'ethnicity' => 'Kinh',
                    'hometown' => 'Hà Nội',
                    'personal_email' => 'ql.profile@local.test',
                    'address' => '33 Nguyễn Trãi, Quận 5, TP. Hồ Chí Minh',
                    'current_position' => 'Chuyên viên quản lý khoa học',
                    'current_unit' => 'Phòng Quản lý khoa học',
                    'research_area' => 'Quản lý hoạt động khoa học, Chính sách giáo dục',
                    'teaching_specialization' => 'Quản lý giáo dục đại học',
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
                        'notes' => 'Phục vụ hợp tác quốc tế và dự án liên kết.',
                    ],
                ],
                'trainings' => [
                    [
                        'degree_id' => $degreeIds['MASTER'] ?? null,
                        'degree_title' => 'Thạc sĩ',
                        'major' => 'Quản lý giáo dục',
                        'institution' => 'Trường Đại học Sư phạm Thành phố Hồ Chí Minh',
                        'country' => 'VN',
                        'start_date' => '2005-09-01',
                        'end_date' => '2007-06-01',
                        'training_form' => 'Chính quy',
                        'notes' => 'Định hướng quản trị đại học và chính sách khoa học.',
                    ],
                ],
                'works' => [
                    [
                        'organization' => 'Trường Đại học Sư phạm Thành phố Hồ Chí Minh',
                        'position' => 'Chuyên viên',
                        'department' => 'Phòng Quản lý khoa học',
                        'workplace' => 'Cơ sở chính An Dương Vương',
                        'start_date' => '2015-01-01',
                        'is_current' => true,
                        'employment_type' => 'PERMANENT',
                        'notes' => 'Theo dõi dữ liệu công trình và định mức giờ NCKH.',
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
