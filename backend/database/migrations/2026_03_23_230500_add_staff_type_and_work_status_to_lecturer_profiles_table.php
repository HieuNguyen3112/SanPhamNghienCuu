<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lecturer_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('lecturer_profiles', 'staff_type')) {
                $table->string('staff_type', 100)->nullable()->after('current_unit');
            }

            if (! Schema::hasColumn('lecturer_profiles', 'work_status')) {
                $table->string('work_status', 100)->nullable()->after('staff_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecturer_profiles', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('lecturer_profiles', 'work_status')) {
                $columns[] = 'work_status';
            }

            if (Schema::hasColumn('lecturer_profiles', 'staff_type')) {
                $columns[] = 'staff_type';
            }

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
