<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->dateTime('occurred_at')->index();

            $table->string('severity', 20);
            $table->string('action_group', 30);
            $table->string('action_code', 100);
            $table->string('action_label', 255);

            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('actor_name', 255)->nullable();
            $table->string('actor_email', 255)->nullable();
            $table->json('actor_roles_snapshot')->nullable();

            $table->string('target_type', 100)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->string('target_display', 255)->nullable();
            $table->string('target_route_name', 100)->nullable();
            $table->json('target_route_params')->nullable();

            $table->string('result_status', 20);
            $table->string('result_error_message', 255)->nullable();

            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained('faculties')
                ->nullOnDelete();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_path', 255)->nullable();
            $table->integer('request_http_status')->nullable();

            $table->json('changes')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('action_group');
            $table->index('action_code');
            $table->index('severity');
            $table->index('result_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
