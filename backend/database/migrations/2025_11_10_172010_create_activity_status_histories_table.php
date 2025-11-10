<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('research_activities')
                ->cascadeOnDelete();

            // Từ trạng thái nào (có thể null ở lần đầu)
            $table->foreignId('from_status_id')
                ->nullable()
                ->constrained('activity_statuses')
                ->nullOnDelete();

            // Sang trạng thái nào
            $table->foreignId('to_status_id')
                ->constrained('activity_statuses')
                ->restrictOnDelete();

            // Ai thực hiện
            $table->foreignId('acted_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('acted_at');
            $table->string('note', 500)->nullable();

            $table->timestamps();

            $table->index(['activity_id', 'to_status_id']);
            $table->index('acted_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_status_histories');
    }
};
