<?php

namespace App\Support;

class RoleMapper
{
    /**
     * Canonical roles exposed to frontend/API.
     */
    public static function canonicalRoles(): array
    {
        return ['LECTURER', 'DEPARTMENT_BOARD', 'SCIENCE_OFFICE'];
    }

    /**
     * Map canonical role -> list of backend (Spatie) role codes.
     * IMPORTANT: Backend now uses canonical role names directly.
     */
    public static function canonicalToBackend(string $role): array
    {
        $r = strtoupper($role);

        return match ($r) {
            'LECTURER' => ['LECTURER'],
            'DEPARTMENT_BOARD' => ['DEPARTMENT_BOARD'],
            'SCIENCE_OFFICE' => ['SCIENCE_OFFICE'],
            default => [],
        };
    }


    /**
     * Map backend role -> canonical role (first match); null if unknown.
     * IMPORTANT: Backend now uses canonical role names directly.
     */
    public static function backendToCanonical(string $role): ?string
    {
        return match (strtoupper($role)) {
            'LECTURER' => 'LECTURER',
            'DEPARTMENT_BOARD' => 'DEPARTMENT_BOARD',
            'SCIENCE_OFFICE' => 'SCIENCE_OFFICE',
            default => null,
        };
    }


    public static function backendListToCanonical(array $roles): array
    {
        $mapped = [];
        foreach ($roles as $r) {
            $c = self::backendToCanonical($r);
            if ($c) {
                $mapped[] = $c;
            }
        }
        return array_values(array_unique($mapped));
    }

    public static function canonicalListToBackend(array $roles): array
    {
        $mapped = [];
        foreach ($roles as $r) {
            $mapped = array_merge($mapped, self::canonicalToBackend($r));
        }
        return array_values(array_unique($mapped));
    }

    /**
     * Check if backend role is allowed for a canonical role.
     */
    public static function backendRoleMatchesCanonical(string $backendRole, string $canonicalRole): bool
    {
        return in_array(strtoupper($backendRole), self::canonicalToBackend(strtoupper($canonicalRole)), true);
    }
}
