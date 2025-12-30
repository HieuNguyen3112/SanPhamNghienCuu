<?php

namespace Database\Seeders;

use App\Models\AcademicRank;
use App\Models\Degree;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $faculties = [
            ['code' => 'FOS', 'name' => 'Khoa Khoa học tự nhiên'],
            ['code' => 'FOE', 'name' => 'Khoa Khoa học Giáo dục'],
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ Thông tin'],
            ['code' => 'TOANTIN', 'name' => 'Khoa Toán - Tin'],
            ['code' => 'NGUVAN', 'name' => 'Khoa Ngữ văn'],
            ['code' => 'LICHSU', 'name' => 'Khoa Lịch sử'],
            ['code' => 'DIALY', 'name' => 'Khoa Địa lý'],
            ['code' => 'VATLY', 'name' => 'Khoa Vật lý'],
            ['code' => 'HOAHOC', 'name' => 'Khoa Hóa học'],
            ['code' => 'SINHHOC', 'name' => 'Khoa Sinh học'],
            ['code' => 'TIENGANH', 'name' => 'Khoa Tiếng Anh'],
            ['code' => 'GDMN', 'name' => 'Khoa Giáo dục Mầm non'],
            ['code' => 'GDTIEUHOC', 'name' => 'Khoa Giáo dục Tiểu học'],
            ['code' => 'GDCT', 'name' => 'Khoa Giáo dục Chính trị'],
        ];

        $facultyIds = [];
        foreach ($faculties as $faculty) {
            $existingId = DB::table('faculties')
                ->where('code', $faculty['code'])
                ->value('id');

            if ($existingId) {
                DB::table('faculties')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $faculty['name'],
                        'updated_at' => $now,
                    ]);
                $facultyIds[$faculty['code']] = $existingId;
                continue;
            }

            $facultyIds[$faculty['code']] = DB::table('faculties')->insertGetId([
                'code' => $faculty['code'],
                'name' => $faculty['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $departments = [
            ['faculty_code' => 'FOS', 'code' => 'IT', 'name' => 'Information Technology'],
            ['faculty_code' => 'FOS', 'code' => 'MATH', 'name' => 'Mathematics'],
            ['faculty_code' => 'FOE', 'code' => 'EDU', 'name' => 'Education'],
        ];

        foreach ($departments as $department) {
            $facultyId = $facultyIds[$department['faculty_code']] ?? null;
            if (! $facultyId) {
                continue;
            }

            Department::firstOrCreate([
                'faculty_id' => $facultyId,
                'code' => $department['code'],
            ], [
                'name' => $department['name'],
            ]);
        }

        $degrees = [
            ['code' => 'BACHELOR', 'name' => 'Bachelor'],
            ['code' => 'MASTER', 'name' => 'Master'],
            ['code' => 'PHD', 'name' => 'PhD'],
        ];

        foreach ($degrees as $degree) {
            Degree::firstOrCreate(['code' => $degree['code']], [
                'name' => $degree['name'],
            ]);
        }

        $ranks = [
            ['code' => 'LECTURER', 'name' => 'Lecturer'],
            ['code' => 'ASSOCIATE_PROFESSOR', 'name' => 'Associate Professor'],
            ['code' => 'PROFESSOR', 'name' => 'Professor'],
        ];

        foreach ($ranks as $rank) {
            AcademicRank::firstOrCreate(['code' => $rank['code']], [
                'name' => $rank['name'],
            ]);
        }
    }
}
