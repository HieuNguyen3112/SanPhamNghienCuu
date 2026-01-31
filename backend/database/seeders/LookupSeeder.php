<?php

namespace Database\Seeders;

use App\Models\AcademicRank;
use App\Models\Degree;
use App\Support\DepartmentCatalog;
use App\Support\FacultyCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $faculties = FacultyCatalog::all();

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

        $departments = DepartmentCatalog::all();

        foreach ($departments as $department) {
            $facultyId = $facultyIds[$department['faculty_code']] ?? null;
            if (! $facultyId) {
                continue;
            }

            $existingId = DB::table('departments')
                ->where('faculty_id', $facultyId)
                ->where('code', $department['code'])
                ->value('id');

            if ($existingId) {
                DB::table('departments')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $department['name'],
                        'updated_at' => $now,
                    ]);
                continue;
            }

            DB::table('departments')->insert([
                'faculty_id' => $facultyId,
                'code' => $department['code'],
                'name' => $department['name'],
                'created_at' => $now,
                'updated_at' => $now,
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
