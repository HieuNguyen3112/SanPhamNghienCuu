<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kind_id')
                ->constrained('activity_kinds')
                ->restrictOnDelete();
            $table->string('code', 50)->unique(); // ví dụ: hdgsnn_900, textbook, bo, report...
            $table->string('name', 255);
            $table->timestamps();

            $table->index('kind_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
