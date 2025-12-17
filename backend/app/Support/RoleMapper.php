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
     */
    public static function canonicalToBackend(string $role): array
    {
        return match ($role) {
            'LECTURER' => ['GV'],
            'DEPARTMENT_BOARD' => ['DL'],
            'SCIENCE_OFFICE' => ['QL', 'ADMIN'],
            default => [],
        };
    }

    /**
     * Map backend role -> canonical role (first match); null if unknown.
     */
    public static function backendToCanonical(string $role): ?string
    {
        return match ($role) {
            'GV' => 'LECTURER',
            'DL' => 'DEPARTMENT_BOARD',
            'QL', 'ADMIN' => 'SCIENCE_OFFICE',
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
        return in_array($backendRole, self::canonicalToBackend($canonicalRole), true);
    }
}
