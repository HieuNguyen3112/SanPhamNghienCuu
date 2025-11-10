<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_details', function (Blueprint $table) {
            // 1-1: dùng activity_id làm khóa chính
            $table->unsignedBigInteger('activity_id')->primary();

            $table->string('publisher', 255);
            $table->string('isbn', 50)->nullable();
            $table->integer('pages')->nullable();
            $table->integer('year')->nullable();

            $table->timestamps();

            $table->foreign('activity_id')
                ->references('id')->on('research_activities')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_details');
    }
};
