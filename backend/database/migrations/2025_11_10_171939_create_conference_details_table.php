<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conference_details', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id')->primary();

            $table->string('conference_name', 255);
            $table->string('location', 255)->nullable();
            $table->date('held_on')->nullable();

            $table->timestamps();

            $table->foreign('activity_id')
                ->references('id')->on('research_activities')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_details');
    }
};
