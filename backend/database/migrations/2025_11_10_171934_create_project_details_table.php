<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_details', function (Blueprint $table) {
            $table->unsignedBigInteger('activity_id')->primary();

            $table->string('project_code', 100)->nullable();
            $table->decimal('funding', 12, 2)->nullable();
            $table->date('start_month')->nullable();
            $table->date('end_month')->nullable();

            $table->timestamps();

            $table->foreign('activity_id')
                ->references('id')->on('research_activities')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_details');
    }
};
