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

class SessionAuthController extends Controller
{
    /**
     * POST /login (session + CSRF)
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'             => ['required', 'email'],
            'password'          => ['required', 'string'],
            'remember'          => ['sometimes', 'boolean'],
            'token_name'        => ['sometimes', 'string', 'max:100'],
            'token_abilities'   => ['sometimes', 'array'],
            'token_abilities.*' => ['string'],
            'single_device'     => ['sometimes', 'boolean'],
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

        $tokenName = $data['token_name'] ?? 'session-token';

        if (! empty($data['single_device'])) {
            $user->tokens()->where('name', $tokenName)->delete();
        }

        $token = $user->createToken(
            $tokenName,
            $data['token_abilities'] ?? ['*']
        );
        $request->session()->put('session_token_id', $token->accessToken->id ?? null);

        Log::info('login.success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dang nhap thanh cong',
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