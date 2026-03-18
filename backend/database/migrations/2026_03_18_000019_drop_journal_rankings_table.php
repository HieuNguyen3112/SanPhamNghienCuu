<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('journal_rankings');
    }

    public function down(): void
    {
        Schema::create('journal_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')
                ->constrained('journals')
                ->cascadeOnDelete();
            $table->string('rank', 20);
            $table->date('effective_from');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['journal_id', 'effective_from']);
        });
    }
};
