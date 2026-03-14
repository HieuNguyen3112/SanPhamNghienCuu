<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rule;
use App\Support\RoleMapper;
use App\Support\AuditLogger;
use Spatie\Permission\Models\Role;

class SessionAuthController extends Controller
{
    /**
     * POST /login (session + CSRF)
     *
     * Payload: email(identifier), password, remember?
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
                    'must_change_password' => (bool) $user->must_change_password,
                    'roles' => $user->getRoleNames()->values()->all(),
                    'backend_roles' => $user->getRoleNames()->values()->all(),
                ] : null,
            ], Response::HTTP_OK);
        }

        $incomingRole = $request->input('role');
        if ($incomingRole) {
            $normalizedRole = strtoupper((string) $incomingRole);

            // Chỉ chấp nhận role canonical (LECTURER/DEPARTMENT_BOARD/SCIENCE_OFFICE)
            if ($canonicalRole = RoleMapper::backendToCanonical($normalizedRole)) {
                $request->merge(['role' => $canonicalRole]);
            } elseif (in_array($normalizedRole, $allowedRoles, true)) {
                $request->merge(['role' => $normalizedRole]);
            }
        }

        $data = $request->validate([
            'email'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'role'     => ['nullable', 'string', Rule::in($allowedRoles)],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $identifier = trim((string) $data['email']);
        $remember = (bool) ($data['remember'] ?? false);

        if (! $this->attemptWithIdentifier($identifier, (string) $data['password'], $remember)) {
            AuditLogger::log($request, [
                'action_group' => 'auth',
                'action_code' => 'LOGIN_FAILED',
                'action_label' => 'Đăng nhập thất bại',
                'severity' => 'important',
                'result_status' => 'failure',
                'result_error_message' => 'Invalid credentials',
                'actor_email' => $data['email'] ?? null,
                'target_type' => 'system',
                'target_display' => 'Hệ thống SPNC',
                'request_http_status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ]);

            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            Auth::guard('web')->logout();

            AuditLogger::log($request, [
                'action_group' => 'auth',
                'action_code' => 'LOGIN_FAILED',
                'action_label' => 'Đăng nhập thất bại',
                'severity' => 'important',
                'result_status' => 'failure',
                'result_error_message' => 'User not found',
                'actor_email' => $data['email'] ?? null,
                'target_type' => 'system',
                'target_display' => 'Hệ thống SPNC',
                'request_http_status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ]);

            return response()->json(['message' => 'User not found'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($user instanceof MustVerifyEmailContract && ! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();

            AuditLogger::log($request, [
                'action_group' => 'auth',
                'action_code' => 'LOGIN_FAILED',
                'action_label' => 'Đăng nhập thất bại',
                'severity' => 'important',
                'result_status' => 'failure',
                'result_error_message' => 'Email not verified',
                'target_type' => 'system',
                'target_display' => 'Hệ thống SPNC',
                'request_http_status' => Response::HTTP_FORBIDDEN,
            ], $user);

            return response()->json([
                'code' => 'UNVERIFIED_EMAIL',
                'message' => 'Email not verified',
            ], Response::HTTP_FORBIDDEN);
        }

        // ✅ Canonical roles are stored in Spatie now -> check canonical directly (web guard)
        $requestedRole = $data['role'] ?? null;
        if ($requestedRole !== null) {
            $acceptedRoles = RoleMapper::canonicalToBackend($requestedRole);
            $hasAcceptedRole = $this->userHasAnyAcceptedRole($user, $acceptedRoles);

            if (! $hasAcceptedRole) {
                Auth::guard('web')->logout();

                AuditLogger::log($request, [
                    'action_group' => 'auth',
                    'action_code' => 'LOGIN_FAILED',
                    'action_label' => 'Đăng nhập thất bại',
                    'severity' => 'important',
                    'result_status' => 'failure',
                    'result_error_message' => 'User does not have the right roles.',
                    'target_type' => 'system',
                    'target_display' => 'Hệ thống SPNC',
                    'request_http_status' => Response::HTTP_FORBIDDEN,
                ], $user);

                return response()->json([
                    'code' => 'FORBIDDEN_MISSING_ROLE',
                    'message' => 'User does not have the right roles.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Đảm bảo user luôn có role canonical tương ứng với role đã chọn lúc đăng nhập.
            if (! $user->hasRole($requestedRole, 'web')) {
                Role::findOrCreate($requestedRole, 'web');
                $user->assignRole($requestedRole);
            }
        }

        $request->session()->regenerate();

        Log::info('login.success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        AuditLogger::log($request, [
            'action_group' => 'auth',
            'action_code' => 'LOGIN_SUCCESS',
            'action_label' => 'Đăng nhập thành công',
            'severity' => 'normal',
            'result_status' => 'success',
            'target_type' => 'system',
            'target_display' => 'Hệ thống SPNC',
            'request_http_status' => Response::HTTP_OK,
        ], $user);

        // SPA session login: không tạo Personal Access Token; token luôn null để giữ schema phản hồi cũ.
        return response()->json([
            'success' => true,
            'message' => 'Dang nhap thanh cong',
            'token'   => null,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'must_change_password' => (bool) $user->must_change_password,
                'roles' => $user->getRoleNames()->values()->all(),
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

        AuditLogger::log($request, [
            'action_group' => 'auth',
            'action_code' => 'LOGOUT',
            'action_label' => 'Đăng xuất',
            'severity' => 'normal',
            'result_status' => 'success',
            'target_type' => 'system',
            'target_display' => 'Hệ thống SPNC',
            'request_http_status' => Response::HTTP_OK,
        ], $user);

        return response()->json(['message' => 'ok'], Response::HTTP_OK);
    }

    /**
     * Check whether user has any accepted canonical role.
     */
    private function userHasAnyAcceptedRole(User $user, array $acceptedRoles): bool
    {
        if (count($acceptedRoles) === 0) {
            return false;
        }

        if (collect($acceptedRoles)->contains(fn(string $roleName) => $user->hasRole($roleName, 'web'))) {
            return true;
        }

        return DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->where('mhr.model_id', $user->id)
            ->whereIn('r.name', $acceptedRoles)
            ->exists();
    }

    /**
     * Accept login identifier as email or lecturer code.
     */
    private function attemptWithIdentifier(string $identifier, string $password, bool $remember): bool
    {
        if ($identifier === '') {
            return false;
        }

        if (Auth::guard('web')->attempt(['email' => $identifier, 'password' => $password], $remember)) {
            return true;
        }

        $emailByLecturerCode = DB::table('lecturers as l')
            ->join('users as u', 'u.id', '=', 'l.user_id')
            ->whereRaw('UPPER(l.code) = ?', [strtoupper($identifier)])
            ->value('u.email');

        if (! is_string($emailByLecturerCode) || $emailByLecturerCode === '') {
            return false;
        }

        if (strcasecmp($emailByLecturerCode, $identifier) === 0) {
            return false;
        }

        return Auth::guard('web')->attempt(['email' => $emailByLecturerCode, 'password' => $password], $remember);
    }
}
