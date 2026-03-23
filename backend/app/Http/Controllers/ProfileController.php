<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Support\ActiveRoleContext;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    // GET /me (web hoặc sanctum)
    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'must_change_password' => (bool) $u->must_change_password,
            'roles' => \App\Support\RoleMapper::backendListToCanonical($u->getRoleNames()->all()),
            'backend_roles' => $u->getRoleNames(),
            'active_role' => ActiveRoleContext::get($request),
        ]);
    }

    // PUT /profile/password
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = Hash::make($data['password']);
        $user->must_change_password = false;
        $user->save();

        // Đảm bảo session an toàn sau khi đổi mật khẩu
        $request->session()->regenerate();

        return response()->json(['message' => 'password updated'], Response::HTTP_OK);
    }

    // PUT /profile
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            // Nếu muốn đổi email: cần flow verify lại (khuyên giữ email bất biến ở đây)
        ]);

        $user = $request->user();
        $user->fill($data)->save();

        return response()->json([
            'message' => 'profile updated',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'must_change_password' => (bool) $user->must_change_password,
                'roles' => \App\Support\RoleMapper::backendListToCanonical($user->getRoleNames()->all()),
                'backend_roles' => $user->getRoleNames(),
            ],
        ], Response::HTTP_OK);
    }
}
