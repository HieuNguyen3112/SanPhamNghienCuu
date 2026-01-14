<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('work_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedInteger('priority')->default(1);
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('address', 255)->nullable();
            $table->string('issn', 50)->nullable()->unique();
            $table->string('classification', 20)->default('OTHER');
            $table->string('country', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')
                ->constrained('journals')
                ->cascadeOnDelete();
            $table->string('rank', 20);
            $table->date('effective_from');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['journal_id', 'effective_from']);
        });

        Schema::create('conferences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('level', 20)->default('FACULTY');
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('research_fields', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable()->unique();
            $table->string('name', 255);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_fields');
        Schema::dropIfExists('conferences');
        Schema::dropIfExists('journal_rankings');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('work_levels');
        Schema::dropIfExists('work_types');
    }
};
