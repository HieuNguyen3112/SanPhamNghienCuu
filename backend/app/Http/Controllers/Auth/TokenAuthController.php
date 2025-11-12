<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TokenIssueRequest;
use App\Http\Requests\Auth\TokenLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Support\SanctumToken;

class TokenAuthController extends Controller
{
    // POST /auth/token/issue  (yêu cầu đã đăng nhập web hoặc dùng Basic, type-hint Request -> TokenIssueRequest)
    public function issue(Request $request)
    {
        $token = $request->user()->createToken(
            $request->input('name'),
            $request->input('abilities', ['*'])
        );
        return response()->json(['token' => $token->plainTextToken], Response::HTTP_CREATED);
    }

    // NEW: POST /api/auth/token/login  (không cần cookie, nhận email/password -> trả token)
    public function loginAndIssue(TokenLoginRequest $request)
    {
        $creds = $request->only('email', 'password');
        if (! Auth::attempt($creds)) {
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $user = $request->user();

        // tuỳ chọn: chặn user chưa verify
        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            Auth::logout();
            return response()->json(['message' => 'Email not verified'], Response::HTTP_FORBIDDEN);
        }

        $token = $user->createToken(
            $request->input('name', 'api-token'),
            $request->input('abilities', ['*'])
        );

        // đăng xuất session ngay (để chỉ dùng token) — tuỳ chính sách
        Auth::logout();

        return response()->json(['token' => $token->plainTextToken], Response::HTTP_CREATED);
    }

    // POST /auth/token/revoke  (revoke current token hoặc theo id)
    public function revoke(Request $request)
    {
        $data = $request->validate(['token_id' => ['sometimes', 'integer']]);
        if (isset($data['token_id'])) {
            $request->user()->tokens()->where('id', $data['token_id'])->delete();
        } else {
            $request->user()->currentAccessToken()?->delete();
        }
        return response()->json(['message' => 'revoked'], Response::HTTP_OK);
    }

    // POST /api/auth/token/rotate  (yêu cầu: auth:sanctum + token hiện tại CÒN HẠN)
    public function rotate(Request $request)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'abilities'   => ['sometimes', 'array'],
            'abilities.*' => ['string'],
        ]);

        $user = $request->user();
        $current = $user->currentAccessToken();

        // Xoá token hiện tại (tuỳ bạn — có thể giữ lại nếu muốn)
        $current?->delete();

        // Cấp token mới
        $token = $user->createToken(
            $data['name'] ?? 'api-token',
            $data['abilities'] ?? ($current?->abilities ?? ['*'])
        );

        return response()->json(['token' => $token->plainTextToken], Response::HTTP_CREATED);
    }

    // GET /api/auth/token/ttl  (trả minutes_left để client chủ động rotate trước hạn)
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
