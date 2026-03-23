<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ActiveRoleContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        $acceptedRoles = $this->acceptedRolesFromRoute($request);
        if (count($acceptedRoles) === 0) {
            return $next($request);
        }

        $userRoles = ActiveRoleContext::canonicalUserRoles($user);
        $activeRole = ActiveRoleContext::get($request);

        if (! $activeRole || ! in_array($activeRole, $userRoles, true)) {
            $intersection = array_values(array_intersect($acceptedRoles, $userRoles));

            if (count($intersection) === 1) {
                $activeRole = $intersection[0];
                ActiveRoleContext::set($request, $activeRole);
            } else {
                return response()->json([
                    'code' => 'ACTIVE_ROLE_REQUIRED',
                    'message' => 'Active role is required for this operation.',
                    'expected_roles' => $acceptedRoles,
                    'available_roles' => $userRoles,
                    'active_role' => $activeRole,
                ], Response::HTTP_CONFLICT);
            }
        }

        if (! in_array($activeRole, $acceptedRoles, true)) {
            return response()->json([
                'code' => 'ACTIVE_ROLE_MISMATCH',
                'message' => 'Current active role cannot access this resource.',
                'expected_roles' => $acceptedRoles,
                'available_roles' => $userRoles,
                'active_role' => $activeRole,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function acceptedRolesFromRoute(Request $request): array
    {
        $middlewares = $request->route()?->gatherMiddleware() ?? [];

        foreach ($middlewares as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (! str_starts_with($middleware, 'role:')) {
                continue;
            }

            $raw = substr($middleware, 5);
            $parts = preg_split('/[|,]/', $raw) ?: [];

            $normalized = [];
            foreach ($parts as $part) {
                $role = ActiveRoleContext::normalize($part);
                if ($role) {
                    $normalized[] = $role;
                }
            }

            return array_values(array_unique($normalized));
        }

        return [];
    }
}
