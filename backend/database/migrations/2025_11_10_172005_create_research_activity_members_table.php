<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('research_activity_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('research_activities')
                ->cascadeOnDelete();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->foreignId('member_role_id')
                ->constrained('member_roles')
                ->restrictOnDelete();

            // Tỉ lệ đóng góp (0..1) nếu dùng chiến lược theo tỷ lệ
            $table->decimal('contribution_share', 6, 4)->nullable();

            // Giờ được phân bổ sau khi tính
            $table->decimal('hours_assigned', 8, 2)->nullable();

            $table->timestamps();

            // 1 giảng viên chỉ xuất hiện 1 lần trong 1 activity
            $table->unique(['activity_id', 'lecturer_id']);

            $table->index('member_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_activity_members');
    }
};
