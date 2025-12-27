<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class SanctumToken
{
    // Minutes left; return null when PAT expiration is disabled or token is transient.
    public static function minutesLeft(?object $pat): ?int
    {
        if (! $pat instanceof PersonalAccessToken) {
            return null;
        }

        $exp = config('sanctum.expiration');
        if (! $exp) {
            return null;
        }
        if (! $pat->created_at) {
            return null;
        }

        $created = $pat->created_at instanceof Carbon ? $pat->created_at : Carbon::parse($pat->created_at);
        $elapsed = $created->diffInMinutes(now());

        return max((int) $exp - $elapsed, 0);
    }
}
