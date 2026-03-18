<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('journals', 'point')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->decimal('point', 4, 2)->nullable()->after('publisher');
            });
        }

        if (Schema::hasColumn('journals', 'point_min') || Schema::hasColumn('journals', 'point_max')) {
            DB::statement('UPDATE journals SET point = COALESCE(point, point_max, point_min)');

            Schema::table('journals', function (Blueprint $table) {
                if (Schema::hasColumn('journals', 'point_min')) {
                    $table->dropColumn('point_min');
                }
                if (Schema::hasColumn('journals', 'point_max')) {
                    $table->dropColumn('point_max');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('journals', 'point_min')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->decimal('point_min', 4, 2)->nullable()->after('source_name');
            });
        }

        if (! Schema::hasColumn('journals', 'point_max')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->decimal('point_max', 4, 2)->nullable()->after('point_min');
            });
        }

        DB::statement('UPDATE journals SET point_min = COALESCE(point_min, point), point_max = COALESCE(point_max, point)');

        if (Schema::hasColumn('journals', 'point')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn('point');
            });
        }
    }
};
