<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\RoleMapper;
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

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roles,
            'backend_roles' => $backendRoles,
        ], Response::HTTP_OK);
    }
}
