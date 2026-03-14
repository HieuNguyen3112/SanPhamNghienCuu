<?php

namespace App\Repositories\UserManagement;

use App\Models\Lecturer;
use App\Models\LecturerProfile;
use App\Models\User;

class EloquentLecturerAccountRepository implements LecturerAccountRepository
{
    public function createUser(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function createLecturer(array $attributes): Lecturer
    {
        return Lecturer::query()->create($attributes);
    }

    public function upsertLecturerProfile(int $lecturerId, array $attributes): LecturerProfile
    {
        $profile = LecturerProfile::query()->firstOrNew(['lecturer_id' => $lecturerId]);
        $profile->fill($attributes);
        $profile->save();

        return $profile;
    }
}
