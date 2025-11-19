<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            $table->integer('page_start')->nullable()->after('issue');
            $table->integer('page_end')->nullable()->after('page_start');
            $table->string('article_url', 500)->nullable()->after('doi');
        });

        Schema::table('project_details', function (Blueprint $table) {
            $table->string('decision_no', 100)->nullable()->after('project_code');
            $table->date('decision_date')->nullable()->after('decision_no');
        });

        Schema::table('book_details', function (Blueprint $table) {
            $table->string('approval_decision_no', 100)->nullable()->after('publisher');
            $table->date('approval_decision_date')->nullable()->after('approval_decision_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paper_details', function (Blueprint $table) {
            $table->dropColumn(['page_start', 'page_end', 'article_url']);
        });

        Schema::table('project_details', function (Blueprint $table) {
            $table->dropColumn(['decision_no', 'decision_date']);
        });

        Schema::table('book_details', function (Blueprint $table) {
            $table->dropColumn(['approval_decision_no', 'approval_decision_date']);
        });
    }
};
