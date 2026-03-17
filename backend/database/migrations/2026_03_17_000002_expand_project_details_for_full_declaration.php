<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_details', function (Blueprint $table) {
            $table->string('project_category', 255)->nullable()->after('project_code');
            $table->string('research_field', 255)->nullable()->after('project_category');
            $table->text('objectives')->nullable()->after('research_field');
            $table->text('content_summary')->nullable()->after('objectives');
            $table->string('application_address', 500)->nullable()->after('content_summary');
            $table->string('implementing_unit', 255)->nullable()->after('application_address');
            $table->string('project_status', 100)->nullable()->after('implementing_unit');
            $table->text('main_results')->nullable()->after('project_status');
        });
    }

    public function down(): void
    {
        Schema::table('project_details', function (Blueprint $table) {
            $table->dropColumn([
                'project_category',
                'research_field',
                'objectives',
                'content_summary',
                'application_address',
                'implementing_unit',
                'project_status',
                'main_results',
            ]);
        });
    }
};
