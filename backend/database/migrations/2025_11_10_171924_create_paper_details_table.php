<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paper_details', function (Blueprint $table) {
            // Dùng activity_id làm khóa chính để đảm bảo 1-1
            $table->unsignedBigInteger('activity_id')->primary();

            $table->string('journal_name', 255)->nullable();
            $table->string('issn', 50)->nullable();
            $table->string('doi', 100)->nullable();
            $table->string('volume', 50)->nullable();
            $table->string('issue', 50)->nullable();
            $table->integer('year')->nullable();

            $table->timestamps();

            $table->foreign('activity_id')
                ->references('id')->on('research_activities')
                ->onDelete('cascade'); // Xóa activity sẽ xóa chi tiết
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_details');
    }
};
