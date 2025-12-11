<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_party_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            $table->boolean('is_member')->default(false);
            $table->string('membership_no', 100)->nullable()->unique();
            $table->date('joined_at')->nullable();
            $table->date('official_at')->nullable();
            $table->string('joining_place', 255)->nullable();
            $table->string('current_branch', 255)->nullable();
            $table->string('position', 255)->nullable();
            $table->string('status', 100)->nullable();
            $table->string('notes', 500)->nullable();

            $table->timestamps();

            $table->unique('lecturer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_party_memberships');
    }
};
