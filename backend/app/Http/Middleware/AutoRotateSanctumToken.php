<?php

namespace App\Http\Middleware;

use App\Support\SanctumToken;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken as PAT;

class AutoRotateSanctumToken
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (auth('sanctum')->check()) {
            $user = $request->user();
            $pat  = $user->currentAccessToken();
            if (! $pat instanceof PAT) {
                return $response;
            }
            $left = SanctumToken::minutesLeft($pat);
            $thr  = (int) config('sanctum.refresh_threshold', 5);

            if ($left !== null && $left <= $thr) {
                // cấp token mới, giữ abilities cũ nếu có
                $new = $user->createToken('auto-rotated', $pat?->abilities ?? ['*']);

                // gắn header để client cập nhật Bearer token
                $response->headers->set('X-Token-Renewed', '1');
                $response->headers->set('X-New-Token', $new->plainTextToken);
                $response->headers->set('X-Token-Minutes-Left', (string) $left);

                // tuỳ chọn: đánh dấu revoke token cũ ở terminate
                if (config('sanctum.revoke_old_on_rotate', false) && $pat) {
                    $request->attributes->set('old_pat_id', $pat->id);
                }
            }
        }

        return $response;
    }

    public function terminate($request, $response)
    {
        if (config('sanctum.revoke_old_on_rotate', false)) {
            if ($oldId = $request->attributes->get('old_pat_id')) {
                PAT::where('id', $oldId)->delete();
            }
        }
    }
}
