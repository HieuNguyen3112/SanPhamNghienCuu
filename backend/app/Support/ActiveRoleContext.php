<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class ActiveRoleContext
{
    private const SESSION_KEY = 'auth.active_role';

    public static function sessionKey(): string
    {
        return self::SESSION_KEY;
    }

    public static function normalize(?string $role): ?string
    {
        if (! is_string($role) || trim($role) === '') {
            return null;
        }

        $upper = strtoupper(trim($role));

        if (in_array($upper, RoleMapper::canonicalRoles(), true)) {
            return $upper;
        }

        return RoleMapper::backendToCanonical($upper);
    }

    public static function get(Request $request): ?string
    {
        return self::normalize($request->session()->get(self::SESSION_KEY));
    }

    public static function set(Request $request, string $role): void
    {
        $normalized = self::normalize($role);
        if (! $normalized) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $normalized);
    }

    public static function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    public static function canonicalUserRoles(User $user): array
    {
        $backendRoles = $user->getRoleNames()->values()->all();
        return RoleMapper::backendListToCanonical($backendRoles);
    }

    public static function userHasRole(User $user, string $role): bool
    {
        $normalized = self::normalize($role);
        if (! $normalized) {
            return false;
        }

        return in_array($normalized, self::canonicalUserRoles($user), true);
    }
}
