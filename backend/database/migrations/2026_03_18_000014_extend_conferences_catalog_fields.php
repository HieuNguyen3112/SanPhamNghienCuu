<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conferences', function (Blueprint $table) {
            if (! Schema::hasColumn('conferences', 'research_field')) {
                $table->string('research_field', 255)->nullable()->after('level');
            }

            if (! Schema::hasColumn('conferences', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('research_field');
            }

            if (! Schema::hasColumn('conferences', 'organization')) {
                $table->string('organization', 255)->nullable()->after('year');
            }

            if (! Schema::hasColumn('conferences', 'has_proceedings')) {
                $table->boolean('has_proceedings')->default(false)->after('organization');
            }

            if (! Schema::hasColumn('conferences', 'has_isbn')) {
                $table->boolean('has_isbn')->default(false)->after('has_proceedings');
            }

            if (! Schema::hasColumn('conferences', 'isbn')) {
                $table->string('isbn', 50)->nullable()->after('has_isbn');
            }

            if (! Schema::hasColumn('conferences', 'point')) {
                $table->decimal('point', 4, 2)->nullable()->after('isbn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conferences', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['research_field', 'year', 'organization', 'has_proceedings', 'has_isbn', 'isbn', 'point'] as $column) {
                if (Schema::hasColumn('conferences', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
