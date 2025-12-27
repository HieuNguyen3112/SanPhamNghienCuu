<?php

namespace App\Policies;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LecturerPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Lecturer $lecturer): bool
    {
        if ($this->isOwner($user, $lecturer)) {
            return true;
        }

        if ($this->hasBackendRole($user, ['DL', 'QL', 'ADMIN'])) {
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

    private function hasBackendRole(User $user, array $roles): bool
    {
        $backendRoles = $user->getRoleNames()->values()->all();

        return (bool) array_intersect($backendRoles, $roles);
    }
}
