<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rule;
use App\Support\RoleMapper;

class SessionAuthController extends Controller
{
    /**
     * POST /login (session + CSRF)
     *
     * Payload: email, password, remember?
     * Success response: { success, message, token: null, user: { id, name, email, roles } }
     * Lưu ý: không phát hành Sanctum PAT cho SPA login; field token luôn null để giữ nguyên schema phản hồi.
     */
    public function login(Request $request)
    {
        $allowedRoles = RoleMapper::canonicalRoles();

        if (Auth::guard('web')->check()) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'message' => 'Ban da dang nhap roi',
                'token'   => null,
                'user'    => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'roles' => RoleMapper::backendListToCanonical($user->getRoleNames()->values()->all()),
                    'backend_roles' => $user->getRoleNames()->values()->all(),
                ] : null,
            ], Response::HTTP_OK); // 200: keep SPA flow happy without creating a new session/token
        }

        $incomingRole = $request->input('role');
        if ($incomingRole) {
            $normalizedRole = strtoupper((string) $incomingRole);

            // Chấp nhận cả mã backend (GV/DL/QL/ADMIN) lẫn role canonical (LECTURER/DEPARTMENT_BOARD/SCIENCE_OFFICE)
            if ($canonicalRole = RoleMapper::backendToCanonical($normalizedRole)) {
                $request->merge(['role' => $canonicalRole]);
            } elseif (in_array($normalizedRole, $allowedRoles, true)) {
                $request->merge(['role' => $normalizedRole]);
            }
        }

        $data = $request->validate([
            'email'             => ['required', 'email'],
            'password'          => ['required', 'string'],
            'role'              => ['nullable', 'string', Rule::in($allowedRoles)],
            'remember'          => ['sometimes', 'boolean'],
        ]);

        if (! Auth::guard('web')->attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $data['remember'] ?? false
        )) {
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            Auth::guard('web')->logout();
            return response()->json(['message' => 'User not found'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($user instanceof MustVerifyEmailContract && ! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            return response()->json(['message' => 'Email not verified'], Response::HTTP_FORBIDDEN);
        }

        $requestedRole = $data['role'] ?? null;
        if ($requestedRole !== null) {
            $backendRolesForRequest = RoleMapper::canonicalToBackend($requestedRole);
            $hasAcceptedRole = false;
            foreach ($backendRolesForRequest as $backendRole) {
                if ($user->hasRole($backendRole)) {
                    $hasAcceptedRole = true;
                    break;
                }
            }

            if (! $hasAcceptedRole) {
                Auth::guard('web')->logout();
                return response()->json(['message' => 'Role not allowed for this user'], Response::HTTP_FORBIDDEN);
            }
        }

        $request->session()->regenerate();

        Log::info('login.success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        // SPA session login: không tạo Personal Access Token; token luôn null để giữ schema phản hồi cũ.
        return response()->json([
            'success' => true,
            'message' => 'Dang nhap thanh cong',
            'token'   => null,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => RoleMapper::backendListToCanonical($user->getRoleNames()->values()->all()),
                'backend_roles' => $user->getRoleNames()->values()->all(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /logout (session + CSRF)
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $tokenIds = [];

        if ($sessionTokenId = $request->session()->pull('session_token_id')) {
            $tokenIds[] = $sessionTokenId;
        }

        if ($token = $request->bearerToken()) {
            $pat = PersonalAccessToken::findToken($token);
            if (
                $pat &&
                $user &&
                $pat->tokenable_type === get_class($user) &&
                (int) $pat->tokenable_id === (int) $user->getKey()
            ) {
                $tokenIds[] = $pat->id;
            }
        }

        if (! empty($tokenIds)) {
            PersonalAccessToken::whereIn('id', $tokenIds)->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('logout.success', [
            'user_id' => $user?->id,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'revoked_token_ids' => $tokenIds,
        ]);

        return response()->json(['message' => 'ok'], Response::HTTP_OK);
    }
}
