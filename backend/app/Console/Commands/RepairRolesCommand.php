<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\RoleMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RepairRolesCommand extends Command
{
    protected $signature = 'spnc:roles:repair {--prune-legacy : Remove legacy role assignments/rows (GV,DL,QL,ADMIN)}';

    protected $description = 'Repair and normalize RBAC roles to canonical roles for SPNC';

    public function handle(): int
    {
        $canonicalRoles = RoleMapper::canonicalRoles();
        $legacyRoles = ['GV', 'DL', 'QL', 'ADMIN'];

        $createdRoles = 0;
        $migratedAssignments = [];
        $seedUsersRepaired = 0;
        $prunedAssignments = 0;
        $prunedRoles = 0;

        DB::transaction(function () use (
            $canonicalRoles,
            $legacyRoles,
            &$createdRoles,
            &$migratedAssignments,
            &$seedUsersRepaired,
            &$prunedAssignments,
            &$prunedRoles
        ) {
            foreach ($canonicalRoles as $canonicalRole) {
                $existing = Role::query()
                    ->where('name', $canonicalRole)
                    ->where('guard_name', 'web')
                    ->exists();

                Role::findOrCreate($canonicalRole, 'web');
                if (! $existing) {
                    $createdRoles++;
                }
            }

            $roles = DB::table('roles')
                ->select(['id', 'name', 'guard_name'])
                ->get();

            $canonicalRoleIds = $roles
                ->where('guard_name', 'web')
                ->whereIn('name', $canonicalRoles)
                ->pluck('id', 'name')
                ->all();

            foreach ($canonicalRoles as $canonicalRole) {
                $targetRoleId = (int) ($canonicalRoleIds[$canonicalRole] ?? 0);
                if (! $targetRoleId) {
                    continue;
                }

                $sourceNames = array_values(array_unique(array_map('strtoupper', RoleMapper::canonicalToBackend($canonicalRole))));
                $sourceRoleIds = $roles
                    ->filter(fn ($role) => in_array(strtoupper((string) $role->name), $sourceNames, true))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (count($sourceRoleIds) === 0) {
                    $migratedAssignments[$canonicalRole] = 0;
                    continue;
                }

                $assignments = DB::table('model_has_roles')
                    ->whereIn('role_id', $sourceRoleIds)
                    ->select(['model_type', 'model_id'])
                    ->get()
                    ->unique(fn ($row) => $row->model_type . '#' . $row->model_id)
                    ->values();

                if ($assignments->isEmpty()) {
                    $migratedAssignments[$canonicalRole] = 0;
                    continue;
                }

                $rowsToInsert = $assignments
                    ->map(fn ($row) => [
                        'role_id' => $targetRoleId,
                        'model_type' => $row->model_type,
                        'model_id' => $row->model_id,
                    ])
                    ->all();

                $migratedAssignments[$canonicalRole] = (int) DB::table('model_has_roles')->insertOrIgnore($rowsToInsert);
            }

            $seedUserRoles = [
                'gv@local.test' => 'LECTURER',
                'khoa@local.test' => 'DEPARTMENT_BOARD',
                'truong@local.test' => 'SCIENCE_OFFICE',
            ];

            foreach ($seedUserRoles as $email => $canonicalRole) {
                $user = User::query()->where('email', $email)->first();
                if (! $user) {
                    continue;
                }

                $roleId = (int) ($canonicalRoleIds[$canonicalRole] ?? 0);
                if (! $roleId) {
                    continue;
                }

                $inserted = DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ]);

                if ($inserted > 0) {
                    $seedUsersRepaired += $inserted;
                }
            }

            if ($this->option('prune-legacy')) {
                $legacyRoleIds = $roles
                    ->filter(fn ($role) => in_array(strtoupper((string) $role->name), $legacyRoles, true))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (count($legacyRoleIds) > 0) {
                    $prunedAssignments = DB::table('model_has_roles')
                        ->whereIn('role_id', $legacyRoleIds)
                        ->delete();

                    DB::table('role_has_permissions')
                        ->whereIn('role_id', $legacyRoleIds)
                        ->delete();

                    $prunedRoles = DB::table('roles')
                        ->whereIn('id', $legacyRoleIds)
                        ->delete();
                }
            }
        });

        $this->info('RBAC repair completed.');
        $this->line('Created canonical roles: ' . $createdRoles);
        foreach (RoleMapper::canonicalRoles() as $role) {
            $this->line(sprintf('Migrated assignments -> %s: %d', $role, (int) ($migratedAssignments[$role] ?? 0)));
        }
        $this->line('Seed user canonical assignments added: ' . $seedUsersRepaired);
        if ($this->option('prune-legacy')) {
            $this->line('Legacy assignments pruned: ' . $prunedAssignments);
            $this->line('Legacy role rows pruned: ' . $prunedRoles);
        }

        return self::SUCCESS;
    }
}

