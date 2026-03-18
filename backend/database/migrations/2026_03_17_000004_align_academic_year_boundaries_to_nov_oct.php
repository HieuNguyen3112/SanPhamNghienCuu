<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $rows = DB::table('academic_years')
            ->select(['id', 'code'])
            ->get();

        foreach ($rows as $row) {
            $code = (string) ($row->code ?? '');
            if (! preg_match('/^(\d{4})-(\d{4})$/', $code, $matches)) {
                continue;
            }

            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];

            if ($endYear !== ($startYear + 1)) {
                continue;
            }

            DB::table('academic_years')
                ->where('id', $row->id)
                ->update([
                    'start_date' => sprintf('%04d-11-01', $startYear),
                    'end_date' => sprintf('%04d-10-31', $endYear),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('academic_years')
            ->select(['id', 'code'])
            ->get();

        foreach ($rows as $row) {
            $code = (string) ($row->code ?? '');
            if (! preg_match('/^(\d{4})-(\d{4})$/', $code, $matches)) {
                continue;
            }

            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];

            if ($endYear !== ($startYear + 1)) {
                continue;
            }

            DB::table('academic_years')
                ->where('id', $row->id)
                ->update([
                    'start_date' => sprintf('%04d-09-01', $startYear),
                    'end_date' => sprintf('%04d-08-31', $endYear),
                    'updated_at' => now(),
                ]);
        }
    }
};
