<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();

            // Liên kết tài khoản (có thể tạo sau)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('code', 50)->unique();           // Mã giảng viên
            $table->string('full_name', 255);
            $table->string('email', 255)->unique();
            $table->string('phone', 30)->nullable();

            $table->foreignId('degree_id')
                ->nullable()
                ->constrained('degrees')
                ->nullOnDelete();

            $table->foreignId('academic_rank_id')
                ->nullable()
                ->constrained('academic_ranks')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index('department_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturers');
    }
};
