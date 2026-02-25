<?php

namespace App\Policies;

use App\Models\Lecturer;
use App\Models\User;
use App\Support\RoleMapper;
use Illuminate\Auth\Access\HandlesAuthorization;

class LecturerPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Lecturer $lecturer): bool
    {
        if ($this->isOwner($user, $lecturer)) {
            return true;
        }

        $canonicalRoles = RoleMapper::backendListToCanonical($user->getRoleNames()->values()->all());
        if (in_array('DEPARTMENT_BOARD', $canonicalRoles, true) || in_array('SCIENCE_OFFICE', $canonicalRoles, true)) {
            return true;
        }

        return false;
    }

    public function update(User $user, Lecturer $lecturer): bool
    {
        return $this->isOwner($user, $lecturer);
    }

    private function isOwner(User $user, Lecturer $lecturer): bool
    {
        return $user->lecturer?->id === $lecturer->id;
    }
}
