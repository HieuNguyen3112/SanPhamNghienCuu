<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            // Nếu trước đó đã set unique cho email:
            // Tên index mặc định là lecturers_email_unique
            try {
                $table->dropUnique('lecturers_email_unique');
            } catch (\Throwable $e) {
                // bỏ qua nếu chưa tồn tại
            }

            // Cho phép null để tránh trùng với users.email
            $table->string('email', 255)->nullable()->change();

            // Đảm bảo 1-1: mỗi user chỉ gắn 1 lecturer
            // Nếu trước đó đã có dữ liệu trùng, cần xử lý dữ liệu trước khi unique.
            $table->unique('user_id', 'lecturers_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            // rollback: bỏ unique user_id
            try {
                $table->dropUnique('lecturers_user_id_unique');
            } catch (\Throwable $e) {
            }

            // (tuỳ chọn) đặt lại unique cho email nếu muốn
            // $table->unique('email');
        });
    }
};
