<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\RoleMapper;
use App\Support\ActiveRoleContext;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AuthMeController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'code' => 'UNAUTHENTICATED',
                'message' => 'Unauthenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $backendRoles = $user->getRoleNames()->values()->all();
        $roles = RoleMapper::backendListToCanonical($backendRoles);
        $activeRole = ActiveRoleContext::get($request);

        if (! $activeRole || ! in_array($activeRole, $roles, true)) {
            $activeRole = $roles[0] ?? null;
            if ($activeRole) {
                ActiveRoleContext::set($request, $activeRole);
            }
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'must_change_password' => (bool) $user->must_change_password,
            'roles' => $roles,
            'backend_roles' => $backendRoles,
            'active_role' => $activeRole,
        ], Response::HTTP_OK);
    }

    public function switchRole(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'code' => 'UNAUTHENTICATED',
                'message' => 'Unauthenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(RoleMapper::canonicalRoles())],
        ]);

        $role = ActiveRoleContext::normalize($validated['role']);
        if (! $role || ! ActiveRoleContext::userHasRole($user, $role)) {
            return response()->json([
                'code' => 'FORBIDDEN_MISSING_ROLE',
                'message' => 'User does not have the right roles.',
            ], Response::HTTP_FORBIDDEN);
        }

        ActiveRoleContext::set($request, $role);

        return response()->json([
            'message' => 'active role updated',
            'active_role' => $role,
            'roles' => ActiveRoleContext::canonicalUserRoles($user),
        ], Response::HTTP_OK);
    }
}
