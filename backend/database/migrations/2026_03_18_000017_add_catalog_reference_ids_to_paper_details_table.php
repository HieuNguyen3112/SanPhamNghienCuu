<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            if (! Schema::hasColumn('paper_details', 'journal_catalog_id')) {
                $table->unsignedBigInteger('journal_catalog_id')->nullable()->after('journal_name');
                $table->index('journal_catalog_id');
            }

            if (! Schema::hasColumn('paper_details', 'conference_catalog_id')) {
                $table->unsignedBigInteger('conference_catalog_id')->nullable()->after('conference_name');
                $table->index('conference_catalog_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            if (Schema::hasColumn('paper_details', 'conference_catalog_id')) {
                $table->dropIndex(['conference_catalog_id']);
                $table->dropColumn('conference_catalog_id');
            }

            if (Schema::hasColumn('paper_details', 'journal_catalog_id')) {
                $table->dropIndex(['journal_catalog_id']);
                $table->dropColumn('journal_catalog_id');
            }
        });
    }
};
