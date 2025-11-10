<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')
                ->constrained('faculties')
                ->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->timestamps();

            // Mã bộ môn là duy nhất trong phạm vi 1 khoa
            $table->unique(['faculty_id', 'code']);
            $table->index('faculty_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
