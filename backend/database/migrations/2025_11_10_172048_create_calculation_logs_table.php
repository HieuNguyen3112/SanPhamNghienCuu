<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calculation_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('research_activities')
                ->cascadeOnDelete();

            $table->dateTime('executed_at');

            $table->foreignId('rule_id')
                ->constrained('hour_rules')
                ->restrictOnDelete();

            $table->json('input_snapshot');   // thành viên, vai trò, quantity, năm học, ...
            $table->json('result_snapshot');  // phân bổ giờ theo từng thành viên

            $table->decimal('total_hours', 8, 2);

            $table->timestamps();

            $table->index('activity_id');
            $table->index('rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculation_logs');
    }
};
