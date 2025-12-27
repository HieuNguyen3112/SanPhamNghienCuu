<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\RoleMapper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMeController extends Controller
{
    // GET /api/auth/me (sanctum, JSON-only)
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

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => RoleMapper::backendListToCanonical($backendRoles),
            'backend_roles' => $backendRoles,
        ], Response::HTTP_OK);
    }
}
