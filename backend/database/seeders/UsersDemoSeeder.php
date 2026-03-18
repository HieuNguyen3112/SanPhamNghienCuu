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
            // Tài khoản mẫu hệ thống
            ['email' => 'truong@hcmue.edu.vn', 'name' => 'ACC TRUONG', 'role' => 'SCIENCE_OFFICE'],
            ['email' => 'bcnkhoa@hcmue.edu.vn', 'name' => 'ACC BAN CHU NHIEM KHOA', 'role' => 'DEPARTMENT_BOARD'],
            ['email' => 'giangvien@hcmue.edu.vn', 'name' => 'ACC GIẢNG VIÊN DEMO', 'role' => 'LECTURER'],


        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make((string) config('users_management.default_lecturer_password', 'hcmue@123')),
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
