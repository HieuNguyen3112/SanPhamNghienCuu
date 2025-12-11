<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_work_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->string('organization', 255);
            $table->string('position', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->string('workplace', 255)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('employment_type', 100)->nullable();
            $table->string('reason_for_leaving', 255)->nullable();
            $table->string('notes', 500)->nullable();

            $table->timestamps();

            $table->index(['lecturer_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_work_histories');
    }
};
