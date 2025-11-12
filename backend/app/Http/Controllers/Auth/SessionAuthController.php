<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;

class SessionAuthController extends Controller
{
    /**
     * POST /login (session + CSRF)
     */
    public function login(Request $request)
    {
        // Validate inline so IDEs that cannot resolve FormRequest stay happy.
        $data = $request->validate([
            'email'             => ['required', 'email'],
            'password'          => ['required', 'string'],
            'remember'          => ['sometimes', 'boolean'],
            'token_name'        => ['sometimes', 'string', 'max:100'],
            'token_abilities'   => ['sometimes', 'array'],
            'token_abilities.*' => ['string'],
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

        $request->session()->regenerate();

        $token = $user->createToken(
            $data['token_name'] ?? 'session-token',
            $data['token_abilities'] ?? ['*']
        );

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'token'   => $token->plainTextToken,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /logout (session + CSRF)
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'ok'], Response::HTTP_OK);
    }
}
