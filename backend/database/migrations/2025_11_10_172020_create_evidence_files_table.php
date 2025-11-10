<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evidence_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('research_activities')
                ->cascadeOnDelete();

            $table->foreignId('file_type_id')
                ->constrained('evidence_file_types')
                ->restrictOnDelete();

            $table->string('disk', 50)->default('s3'); // hoặc 'local'
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');

            // Chống trùng tệp
            $table->char('sha256', 64)->unique();

            $table->foreignId('uploaded_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('uploaded_at');

            $table->timestamps();

            $table->index(['activity_id', 'file_type_id']);
            $table->index('uploaded_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_files');
    }
};
