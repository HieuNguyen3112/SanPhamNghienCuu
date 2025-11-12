<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@local.test', 'name' => 'Admin', 'role' => 'ADMIN'],
            ['email' => 'ql@local.test',    'name' => 'Quan Ly', 'role' => 'QL'],
            ['email' => 'dl@local.test',    'name' => 'Duyet',   'role' => 'DL'],
            ['email' => 'gv@local.test',    'name' => 'Giang Vien', 'role' => 'GV'],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                ['name' => $u['name'], 'password' => Hash::make('Password!123')]
            );

            // Đánh dấu đã verify để tiện test
            if (is_null($user->email_verified_at)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->syncRoles([$u['role']]);
        }
    }
}
