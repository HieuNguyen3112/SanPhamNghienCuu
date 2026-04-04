<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lecturer_yearly_hours', function (Blueprint $table) {
            if (! Schema::hasColumn('lecturer_yearly_hours', 'last_notified_at')) {
                $table->dateTime('last_notified_at')->nullable()->after('hours_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lecturer_yearly_hours', function (Blueprint $table) {
            if (Schema::hasColumn('lecturer_yearly_hours', 'last_notified_at')) {
                $table->dropColumn('last_notified_at');
            }
        });
    }
};
