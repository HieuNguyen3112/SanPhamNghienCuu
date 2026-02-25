<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AcademicYearResolver
{
    public static function resolve(?int $academicYearId = null): ?object
    {
        if ($academicYearId) {
            return DB::table('academic_years')
                ->where('id', $academicYearId)
                ->first();
        }

        return self::current();
    }

    public static function current(?CarbonInterface $asOf = null): ?object
    {
        $date = ($asOf ?? now())->toDateString();

        $inWindow = DB::table('academic_years')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->first();

        if ($inWindow) {
            return $inWindow;
        }

        $active = DB::table('academic_years')
            ->where('is_active', 1)
            ->orderByDesc('start_date')
            ->first();

        if ($active) {
            return $active;
        }

        return DB::table('academic_years')
            ->orderByDesc('start_date')
            ->first();
    }

    public static function currentId(?CarbonInterface $asOf = null): ?int
    {
        $row = self::current($asOf);
        return $row ? (int) $row->id : null;
    }
}
