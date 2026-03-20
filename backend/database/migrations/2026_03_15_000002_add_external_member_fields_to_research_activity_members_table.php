<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('research_activity_members', function (Blueprint $table) {
            $table->boolean('is_external')->default(false)->after('member_role_id');
            $table->string('external_full_name', 255)->nullable()->after('is_external');
            $table->string('external_department_name', 255)->nullable()->after('external_full_name');
            $table->index('is_external');
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE research_activity_members ALTER COLUMN lecturer_id DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE research_activity_members MODIFY lecturer_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE research_activity_members ALTER COLUMN lecturer_id SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE research_activity_members MODIFY lecturer_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('research_activity_members', function (Blueprint $table) {
            $table->dropIndex(['is_external']);
            $table->dropColumn(['is_external', 'external_full_name', 'external_department_name']);
        });
    }
};
