<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_catalog_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->string('suggestion_type', 20); // journal|conference
            $table->string('source_name', 255);
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->unsignedBigInteger('submitted_by_lecturer_id');
            $table->unsignedBigInteger('submitted_by_user_id')->nullable();
            $table->unsignedBigInteger('resolved_catalog_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['suggestion_type', 'status']);
            $table->index('submitted_by_lecturer_id');
            $table->index('activity_id');
            $table->unique(['activity_id', 'suggestion_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_catalog_suggestions');
    }
};
