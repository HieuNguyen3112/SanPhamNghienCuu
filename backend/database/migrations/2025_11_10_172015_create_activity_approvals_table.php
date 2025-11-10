<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('research_activities')
                ->cascadeOnDelete();

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

            // Mỗi activity chỉ có 1 bản ghi cho mỗi stage
            $table->unique(['activity_id', 'stage_id']);

            $table->index(['activity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_approvals');
    }
};
