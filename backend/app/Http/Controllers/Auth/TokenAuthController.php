<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TokenIssueRequest;
use App\Support\SanctumToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthController extends Controller
{
    // GET /auth/tokens (list PATs of current user)
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at'])
            ->map(function ($t) {
                return [
                    'id'           => $t->id,
                    'name'         => $t->name,
                    'abilities'    => $t->abilities,
                    'last_used_at' => $t->last_used_at,
                    'created_at'   => $t->created_at,
                ];
            });

        return response()->json(['tokens' => $tokens], Response::HTTP_OK);
    }

    // POST /auth/token/issue (requires existing web/basic auth session)
    public function issue(TokenIssueRequest $request)
    {
        $token = $request->user()->createToken(
            $request->input('name'),
            $request->input('abilities', ['*'])
        );

        Log::info('token.issue', [
            'user_id' => $request->user()->id,
            'token_name' => $request->input('name'),
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return response()->json(['token' => $token->plainTextToken], Response::HTTP_CREATED);
    }

    // POST /auth/token/revoke (revoke current token or by id)
    public function revoke(Request $request)
    {
        $data = $request->validate(['token_id' => ['sometimes', 'integer']]);

        $deleted = 0;
        if (isset($data['token_id'])) {
            $deleted = $request->user()->tokens()->where('id', $data['token_id'])->delete();
        } else {
            $deleted = $request->user()->currentAccessToken()?->delete() ? 1 : 0;
        }

        Log::info('token.revoke', [
            'user_id' => $request->user()->id,
            'token_id' => $data['token_id'] ?? null,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
            'deleted' => $deleted,
        ]);

        return response()->json(['message' => 'revoked'], Response::HTTP_OK);
    }

    // POST /auth/token/revoke-all (remove all PATs for this user)
    public function revokeAll(Request $request)
    {
        $count = $request->user()->tokens()->delete();

        Log::info('token.revoke_all', [
            'user_id' => $request->user()->id,
            'count' => $count,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'revoked_all', 'count' => $count], Response::HTTP_OK);
    }

    // POST /api/auth/token/rotate (requires auth:sanctum token still valid)
    public function rotate(Request $request)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'abilities'   => ['sometimes', 'array'],
            'abilities.*' => ['string'],
        ]);

        $user    = $request->user();
        $current = $user->currentAccessToken();

        $current?->delete();

        $token = $user->createToken(
            $data['name'] ?? 'api-token',
            $data['abilities'] ?? ($current?->abilities ?? ['*'])
        );

        Log::info('token.rotate', [
            'user_id' => $user->id,
            'old_token_id' => $current?->id,
            'new_token_id' => $token->accessToken->id ?? null,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return response()->json(['token' => $token->plainTextToken], Response::HTTP_CREATED);
    }

    // GET /api/auth/token/ttl (exposes minutes left so client can rotate proactively)
    public function ttl(Request $request)
    {
        $pat = $request->user()->currentAccessToken();

        return response()->json([
            'minutes_left' => SanctumToken::minutesLeft($pat),
            'threshold'    => (int) config('token.sanctum_refresh_threshold', 5),
            'expiration'   => (int) (config('sanctum.expiration') ?? 0),
        ], Response::HTTP_OK);
    }
}