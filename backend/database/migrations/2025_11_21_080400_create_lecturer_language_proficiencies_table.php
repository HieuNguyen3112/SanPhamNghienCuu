<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_language_proficiencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->string('language', 100);
            $table->string('proficiency_level', 100)->nullable();
            $table->boolean('is_native')->default(false);
            $table->string('certificate_name', 150)->nullable();
            $table->string('certificate_level', 100)->nullable();
            $table->string('certificate_score', 50)->nullable();
            $table->string('issued_by', 255)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('notes', 500)->nullable();

            $table->timestamps();

            $table->index(['lecturer_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_language_proficiencies');
    }
};
