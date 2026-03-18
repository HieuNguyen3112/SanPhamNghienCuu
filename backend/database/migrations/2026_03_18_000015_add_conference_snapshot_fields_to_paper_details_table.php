<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            if (! Schema::hasColumn('paper_details', 'conference_name')) {
                $table->string('conference_name', 255)->nullable()->after('keywords');
            }

            if (! Schema::hasColumn('paper_details', 'conference_level')) {
                $table->string('conference_level', 20)->nullable()->after('conference_name');
            }

            if (! Schema::hasColumn('paper_details', 'conference_research_field')) {
                $table->string('conference_research_field', 255)->nullable()->after('conference_level');
            }

            if (! Schema::hasColumn('paper_details', 'conference_organization')) {
                $table->string('conference_organization', 255)->nullable()->after('conference_research_field');
            }

            if (! Schema::hasColumn('paper_details', 'conference_has_isbn')) {
                $table->boolean('conference_has_isbn')->default(false)->after('conference_organization');
            }

            if (! Schema::hasColumn('paper_details', 'conference_isbn')) {
                $table->string('conference_isbn', 50)->nullable()->after('conference_has_isbn');
            }

            if (! Schema::hasColumn('paper_details', 'conference_point')) {
                $table->decimal('conference_point', 4, 2)->nullable()->after('conference_isbn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'conference_name',
                'conference_level',
                'conference_research_field',
                'conference_organization',
                'conference_has_isbn',
                'conference_isbn',
                'conference_point',
            ] as $column) {
                if (Schema::hasColumn('paper_details', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
