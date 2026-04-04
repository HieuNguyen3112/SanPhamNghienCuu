<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('research_activity_members', function (Blueprint $table) {
            $table->decimal('hours_claimed_before', 8, 2)
                ->default(0)
                ->after('hours_assigned');
        });
    }

    public function down(): void
    {
        Schema::table('research_activity_members', function (Blueprint $table) {
            $table->dropColumn('hours_claimed_before');
        });
    }
};
