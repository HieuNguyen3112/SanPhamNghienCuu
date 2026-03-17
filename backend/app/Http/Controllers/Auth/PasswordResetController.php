<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetController extends Controller
{
    // POST /password/forgot
    public function sendResetLinkEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $payload = ['email' => Str::lower(trim((string) $data['email']))];

        try {
            $status = Password::sendResetLink($payload);
        } catch (\Throwable $exception) {
            Log::error('password_reset_link_dispatch_failed', [
                'email' => $payload['email'],
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'code' => 'PASSWORD_RESET_EMAIL_DISPATCH_FAILED',
                'message' => 'Khong the gui email dat lai mat khau luc nay. Vui long thu lai sau.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        Log::info('password_reset_link_requested', [
            'email' => $payload['email'],
            'broker_status' => $status,
        ]);

        return response()->json([
            'code' => 'PASSWORD_RESET_LINK_SENT_IF_ACCOUNT_EXISTS',
            'message' => 'Neu thong tin hop le, huong dan dat lai mat khau da duoc gui toi email cua ban.',
        ], Response::HTTP_OK);
    }

    // POST /password/reset
    public function reset(Request $request, ResetUserPassword $resetUserPassword)
    {
        $data = $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        $email = Str::lower(trim((string) $data['email']));

        $status = Password::reset(
            array_merge($data, ['email' => $email]),
            function ($user, $password) use ($data, $resetUserPassword) {
                $resetUserPassword->reset($user, [
                    'password' => $password,
                    'password_confirmation' => $data['password_confirmation'],
                ]);

                $user->forceFill([
                    'remember_token' => Str::random(60),
                ])->save();

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('password_reset_completed', [
                'email' => $email,
            ]);

            return response()->json([
                'code' => 'PASSWORD_RESET_SUCCESS',
                'message' => 'Dat lai mat khau thanh cong.',
            ], Response::HTTP_OK);
        }

        Log::warning('password_reset_failed', [
            'email' => $email,
            'broker_status' => $status,
        ]);

        return response()->json([
            'code' => $status === Password::INVALID_TOKEN
                ? 'INVALID_RESET_TOKEN'
                : 'PASSWORD_RESET_FAILED',
            'message' => $status === Password::INVALID_TOKEN
                ? 'Lien ket dat lai mat khau khong hop le hoac da het han.'
                : 'Khong the dat lai mat khau. Vui long kiem tra lai thong tin va thu lai.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
