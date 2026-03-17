<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            $table->string('keywords', 500)->nullable()->after('year');
        });
    }

    public function down(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
