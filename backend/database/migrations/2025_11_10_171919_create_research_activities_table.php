<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('research_activities', function (Blueprint $table) {
            $table->id();

            // Mã hoạt động duy nhất (dễ tra cứu)
            $table->string('activity_code', 50)->unique();

            // Liên kết chủ sở hữu (người kê khai)
            $table->foreignId('owner_lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            // Loại hoạt động
            $table->foreignId('kind_id')
                ->constrained('activity_kinds')
                ->restrictOnDelete();

            // Subtype (có thể null) — set null nếu loại con bị xóa
            $table->foreignId('type_id')
                ->nullable()
                ->constrained('activity_types')
                ->nullOnDelete();

            // Năm học để tính quota
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->restrictOnDelete();

            // Trạng thái quy trình
            $table->foreignId('status_id')
                ->constrained('activity_statuses')
                ->restrictOnDelete();

            // Thông tin chung
            $table->string('title', 500);
            $table->text('abstract')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Số lượng (ví dụ số lần với conference: attend/report)
            $table->integer('quantity')->default(1);

            // Mốc thời gian quy trình
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('approved_at')->nullable();

            // Tổng giờ đã tính (denormalize để báo cáo nhanh)
            $table->decimal('total_hours_calc', 8, 2)->nullable();

            $table->string('notes', 500)->nullable();

            $table->timestamps();

            // Index tối ưu lọc/báo cáo
            $table->index(['owner_lecturer_id', 'academic_year_id']);
            $table->index(['kind_id', 'type_id']);
            $table->index('status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_activities');
    }
};
