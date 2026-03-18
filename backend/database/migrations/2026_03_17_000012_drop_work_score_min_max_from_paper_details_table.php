<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $hasMin = Schema::hasColumn('paper_details', 'work_score_min');
        $hasMax = Schema::hasColumn('paper_details', 'work_score_max');

        if (! $hasMin && ! $hasMax) {
            return;
        }

        Schema::table('paper_details', function (Blueprint $table) use ($hasMin, $hasMax) {
            $cols = [];
            if ($hasMin) {
                $cols[] = 'work_score_min';
            }
            if ($hasMax) {
                $cols[] = 'work_score_max';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }

    public function down(): void
    {
        $hasMin = Schema::hasColumn('paper_details', 'work_score_min');
        $hasMax = Schema::hasColumn('paper_details', 'work_score_max');

        if (! $hasMin) {
            Schema::table('paper_details', function (Blueprint $table) {
                $table->decimal('work_score_min', 5, 2)->nullable()->after('work_score');
            });
        }

        if (! $hasMax) {
            Schema::table('paper_details', function (Blueprint $table) {
                $table->decimal('work_score_max', 5, 2)->nullable()->after('work_score_min');
            });
        }
    }
};
