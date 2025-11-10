<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hour_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kind_id')
                ->constrained('activity_kinds')
                ->restrictOnDelete();

            $table->foreignId('type_id')
                ->nullable()
                ->constrained('activity_types')
                ->nullOnDelete();

            $table->enum('distribution_strategy', [
                'equal_all_members',               // Bài báo: chia đều
                'principal_fraction_others_equal', // Sách: 1/5 chủ biên, 4/5 chia đều
                'per_lecturer_fixed',              // Hội thảo: giờ cố định mỗi người
            ]);

            // Tham số
            $table->decimal('hours_total_per_activity', 8, 2)->nullable(); // paper/book/project
            $table->decimal('hours_per_occurrence', 8, 2)->nullable();     // conference
            $table->decimal('principal_fraction', 6, 4)->nullable();       // ví dụ 0.2
            $table->decimal('others_fraction_total', 6, 4)->nullable();    // ví dụ 0.8
            $table->unsignedSmallInteger('max_occurrences_per_year')->nullable(); // ví dụ 40

            // Hiệu lực & phiên bản
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);

            $table->timestamps();

            $table->index(['kind_id', 'type_id']);
            $table->index('effective_from');
            $table->index('effective_to');

            // Đảm bảo quy tắc không bị trùng (lưu ý: MySQL cho phép nhiều NULL)
            $table->unique(['kind_id', 'type_id', 'version', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_rules');
    }
};
