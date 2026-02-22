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

            // Basic info
            $table->string('name', 255);
            $table->string('issn', 50)->nullable(); // có thể trùng NULL, unique theo non-null ở dưới
            $table->string('address', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('notes', 255)->nullable();

            // Source / credibility
            // VD: "HDGSNN 2025", "SCI", "SCIE", "Scopus", "ISI", "VAST", link...
            $table->string('source_name', 255)->nullable();

            // Point range (theo nguồn uy tín)
            // DECIMAL để tránh lỗi float (0.75, 1.5, 3.0...)
            $table->decimal('point_min', 4, 2)->nullable();
            $table->decimal('point_max', 4, 2)->nullable();

            // Classification: nên là enum/tier do hệ thống tự derive, không cho user gõ tự do
            // VD: OTHER, ISSN_ISBN, HDGSNN_GE_1, HDGSNN_GE_2 ...
            $table->string('classification', 30)->default('OTHER');

            // Cached hours (optional nhưng rất đáng làm để query/report nhanh)
            // Nếu bạn muốn luôn derive runtime thì có thể bỏ cột này
            $table->unsignedSmallInteger('research_hours')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique ISSN nhưng cho phép nhiều NULL
            $table->unique('issn');
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
