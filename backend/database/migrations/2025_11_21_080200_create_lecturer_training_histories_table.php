<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_training_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->foreignId('degree_id')
                ->nullable()
                ->constrained('degrees')
                ->nullOnDelete();

            $table->string('degree_title', 255)->nullable();
            $table->string('major', 255)->nullable();
            $table->string('institution', 255);
            $table->string('country', 100)->nullable();
            $table->string('city', 150)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('training_form', 100)->nullable();
            $table->string('funding_source', 150)->nullable();
            $table->string('certificate_no', 100)->nullable();
            $table->string('notes', 500)->nullable();

            $table->timestamps();

            $table->index(['lecturer_id', 'start_date']);
            $table->index('degree_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_training_histories');
    }
};
