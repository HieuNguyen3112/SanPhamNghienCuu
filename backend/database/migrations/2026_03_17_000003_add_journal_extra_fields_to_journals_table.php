<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('journal_type', 100)->nullable()->after('issn');
            $table->string('research_field', 255)->nullable()->after('publisher');
            $table->string('website', 255)->nullable()->after('research_field');
            $table->index('journal_type');
            $table->index('research_field');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex(['journal_type']);
            $table->dropIndex(['research_field']);
            $table->dropColumn(['journal_type', 'research_field', 'website']);
        });
    }
};
