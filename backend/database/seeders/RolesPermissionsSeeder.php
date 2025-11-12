<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['ADMIN', 'QL', 'DL', 'GV'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $perms = [
            'user.viewSelf',
            'user.updateSelf',
            'user.manage',
            'report.viewPersonal',
            'report.viewDepartment',
            'report.export',
            'rules.manage',
            'quota.manage',
            'rbac.manage',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Gán quyền tối thiểu
        Role::findByName('GV')->syncPermissions(['user.viewSelf', 'user.updateSelf', 'report.viewPersonal']);
        Role::findByName('DL')->syncPermissions(['report.viewDepartment', 'report.export']);
        Role::findByName('QL')->syncPermissions(['report.viewDepartment', 'report.export', 'rules.manage', 'quota.manage']);
        Role::findByName('ADMIN')->syncPermissions(array_merge($perms, ['rbac.manage', 'user.manage']));
    }
}
