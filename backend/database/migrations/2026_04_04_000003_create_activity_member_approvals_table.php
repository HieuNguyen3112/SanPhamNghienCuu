<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_member_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('research_activities')
                ->cascadeOnDelete();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->foreignId('stage_id')
                ->constrained('approval_stages')
                ->restrictOnDelete();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->foreignId('decided_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('decided_at')->nullable();
            $table->string('note', 500)->nullable();

            $table->timestamps();

            $table->unique(['activity_id', 'lecturer_id', 'stage_id'], 'uix_activity_member_stage');
            $table->index(['activity_id', 'stage_id', 'status'], 'idx_activity_member_approval_status');
            $table->index(['lecturer_id', 'stage_id', 'status'], 'idx_lecturer_member_approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_member_approvals');
    }
};
