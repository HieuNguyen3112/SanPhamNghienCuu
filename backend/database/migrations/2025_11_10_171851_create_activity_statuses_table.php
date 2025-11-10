<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // draft, submitted, approved, rejected
            $table->string('name', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_statuses');
    }
};
