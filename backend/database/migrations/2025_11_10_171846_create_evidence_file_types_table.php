<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evidence_file_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // content, cover, toc, acceptance_decision, publication_decision
            $table->string('name', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_file_types');
    }
};
