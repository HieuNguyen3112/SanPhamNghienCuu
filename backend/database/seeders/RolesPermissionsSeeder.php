<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Canonical roles (match FE)
        $roles = [
            'SCIENCE_OFFICE',     // Trường
            'DEPARTMENT_BOARD',   // Khoa
            'LECTURER',           // Giảng viên
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
