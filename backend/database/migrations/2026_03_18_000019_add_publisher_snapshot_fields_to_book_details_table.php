<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_details', function (Blueprint $table) {
            $table->string('publisher_address', 255)->nullable()->after('publisher');
            $table->string('publisher_phone', 50)->nullable()->after('publisher_address');
            $table->string('publisher_email', 100)->nullable()->after('publisher_phone');
            $table->string('publisher_website', 255)->nullable()->after('publisher_email');
        });
    }

    public function down(): void
    {
        Schema::table('book_details', function (Blueprint $table) {
            $table->dropColumn([
                'publisher_address',
                'publisher_phone',
                'publisher_email',
                'publisher_website',
            ]);
        });
    }
};
