<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('publisher', 255)->nullable()->after('source_name');
            $table->index('publisher');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex(['publisher']);
            $table->dropColumn('publisher');
        });
    }
};
