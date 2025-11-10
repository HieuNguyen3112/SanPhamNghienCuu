<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_stages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // assistant, manager
            $table->string('name', 100);
            $table->tinyInteger('order_no')->unsigned(); // 1 = assistant, 2 = manager
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_stages');
    }
};
