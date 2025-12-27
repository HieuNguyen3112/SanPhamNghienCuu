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
            ['code' => 'FOS', 'name' => 'Faculty of Science'],
            ['code' => 'FOE', 'name' => 'Faculty of Education'],
        ];

        $facultyIds = [];
        foreach ($faculties as $faculty) {
            $existingId = DB::table('faculties')
                ->where('code', $faculty['code'])
                ->value('id');

            if ($existingId) {
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
