<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            $table->string('research_field', 255)->nullable()->after('year');
            $table->string('publication_status', 100)->nullable()->after('research_field');

            $table->string('journal_scope', 100)->nullable()->after('issn');
            $table->string('journal_source_name', 255)->nullable()->after('journal_scope');
            $table->string('journal_publisher', 255)->nullable()->after('journal_source_name');
            $table->string('journal_website', 255)->nullable()->after('journal_publisher');
            $table->decimal('work_score', 5, 2)->nullable()->after('journal_website');
        });
    }

    public function down(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            $table->dropColumn([
                'research_field',
                'publication_status',
                'journal_scope',
                'journal_source_name',
                'journal_publisher',
                'journal_website',
                'work_score',
            ]);
        });
    }
};
