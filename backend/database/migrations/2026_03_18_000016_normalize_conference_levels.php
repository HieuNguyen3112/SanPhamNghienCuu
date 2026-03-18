<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('conferences') && Schema::hasColumn('conferences', 'level')) {
            DB::table('conferences')
                ->whereIn('level', ['FACULTY', 'UNIVERSITY'])
                ->update(['level' => 'NATIONAL']);
        }

        if (Schema::hasTable('paper_details') && Schema::hasColumn('paper_details', 'conference_level')) {
            DB::table('paper_details')
                ->whereIn('conference_level', ['FACULTY', 'UNIVERSITY'])
                ->update(['conference_level' => 'NATIONAL']);
        }
    }

    public function down(): void
    {
        // Irreversible normalization: old levels are consolidated to NATIONAL.
    }
};
