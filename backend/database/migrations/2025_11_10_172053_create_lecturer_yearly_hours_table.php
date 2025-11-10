<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_yearly_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->restrictOnDelete();

            $table->decimal('hours_total', 8, 2)->default(0);

            $table->timestamps();

            $table->unique(['lecturer_id', 'academic_year_id']);
            $table->index('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_yearly_hours');
    }
};
