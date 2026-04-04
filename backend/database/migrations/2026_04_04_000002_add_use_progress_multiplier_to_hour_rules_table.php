<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hour_rules', function (Blueprint $table) {
            $table->boolean('use_progress_multiplier')
                ->nullable()
                ->after('max_occurrences_per_year');
        });

        $projectKindIds = DB::table('activity_kinds')
            ->where('code', 'project')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (! empty($projectKindIds)) {
            DB::table('hour_rules')
                ->whereIn('kind_id', $projectKindIds)
                ->update(['use_progress_multiplier' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('hour_rules', function (Blueprint $table) {
            $table->dropColumn('use_progress_multiplier');
        });
    }
};
