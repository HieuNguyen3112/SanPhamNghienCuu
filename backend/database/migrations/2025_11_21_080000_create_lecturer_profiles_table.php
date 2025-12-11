<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lecturer_id')
                ->constrained('lecturers')
                ->restrictOnDelete();

            // Nhân thân
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth', 255)->nullable();
            $table->string('ethnicity', 100)->nullable();
            $table->string('hometown', 255)->nullable();

            $table->string('personal_email', 255)->nullable();
            $table->string('alternate_phone', 50)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('emergency_contact_relation', 100)->nullable();

            // Thông tin hiện tại
            $table->string('current_position', 255)->nullable();
            $table->string('current_unit', 255)->nullable();
            $table->string('research_area', 255)->nullable();
            $table->string('teaching_specialization', 255)->nullable();

            $table->string('orcid_id', 50)->nullable();
            $table->string('google_scholar_profile', 500)->nullable();
            $table->string('research_gate_profile', 500)->nullable();
            $table->string('scopus_id', 100)->nullable();
            $table->string('publons_id', 100)->nullable();
            $table->string('personal_website', 500)->nullable();
            $table->string('academic_portfolio_url', 500)->nullable();

            $table->timestamps();

            $table->unique('lecturer_id');
            $table->index('personal_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_profiles');
    }
};
