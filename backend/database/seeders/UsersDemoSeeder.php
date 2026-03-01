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
            ['email' => 'truong@local.test', 'name' => 'Nguyễn Thành Nam', 'role' => 'SCIENCE_OFFICE'],
            ['email' => 'khoa@local.test',   'name' => 'Đặng Thu Hà', 'role' => 'DEPARTMENT_BOARD'],
            ['email' => 'gv@local.test',     'name' => 'Nguyễn Minh Tuấn', 'role' => 'LECTURER'],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('Password!123')
                ]
            );

            if (is_null($user->email_verified_at)) {
                $user->forceFill([
                    'email_verified_at' => now()
                ])->save();
            }

            $user->syncRoles([$u['role']]);
        }
    }
}
