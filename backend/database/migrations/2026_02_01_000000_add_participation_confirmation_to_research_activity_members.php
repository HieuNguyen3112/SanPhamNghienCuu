<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('research_activity_members', function (Blueprint $table) {
            $table->string('confirmation_status', 20)
                ->default('pending')
                ->after('hours_assigned');
            $table->dateTime('responded_at')
                ->nullable()
                ->after('confirmation_status');
            $table->string('confirmation_note', 500)
                ->nullable()
                ->after('responded_at');

            $table->index('confirmation_status');
        });
    }

    public function down(): void
    {
        Schema::table('research_activity_members', function (Blueprint $table) {
            $table->dropIndex(['confirmation_status']);
            $table->dropColumn(['confirmation_status', 'responded_at', 'confirmation_note']);
        });
    }
};
