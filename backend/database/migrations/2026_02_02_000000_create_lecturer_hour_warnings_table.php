<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_hour_warnings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->restrictOnDelete();

            $table->string('type_key', 100);
            $table->string('status_key', 20)->default('unseen');
            $table->dateTime('seen_at')->nullable();
            $table->dateTime('resolved_at')->nullable();

            $table->timestamps();

            $table->unique(['lecturer_id', 'academic_year_id', 'type_key'], 'uix_lecturer_hour_warning');
            $table->index(['lecturer_id', 'academic_year_id'], 'idx_lecturer_hour_warning_owner');
            $table->index('status_key', 'idx_lecturer_hour_warning_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_hour_warnings');
    }
};
