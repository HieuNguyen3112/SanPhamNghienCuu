<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workload_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->restrictOnDelete(); // mỗi quota gắn 1 năm học
            $table->decimal('required_hours', 6, 2)->default(600.00);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            // 1 năm học chỉ có 1 quota
            $table->unique('academic_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workload_quotas');
    }
};
