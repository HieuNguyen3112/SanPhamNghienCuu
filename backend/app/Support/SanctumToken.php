<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumToken
{
    /** minutes left; null nếu không cấu hình SANCTUM_EXPIRATION */
    public static function minutesLeft(?PersonalAccessToken $pat): ?int
    {
        $exp = config('sanctum.expiration'); // phút; null => không hết hạn
        if (!$exp) return null;
        if (!$pat || !$pat->created_at) return null;

        $created = $pat->created_at instanceof Carbon ? $pat->created_at : Carbon::parse($pat->created_at);
        $elapsed = $created->diffInMinutes(now());
        return max((int)$exp - $elapsed, 0);
    }
}
