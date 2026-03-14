<?php

namespace App\Repositories\UserManagement;

use App\Models\Lecturer;
use App\Models\LecturerProfile;
use App\Models\User;

interface LecturerAccountRepository
{
    public function createUser(array $attributes): User;

    public function createLecturer(array $attributes): Lecturer;

    public function upsertLecturerProfile(int $lecturerId, array $attributes): LecturerProfile;
}
