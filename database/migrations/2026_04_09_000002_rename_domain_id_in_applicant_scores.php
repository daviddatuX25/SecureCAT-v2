<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_scores', function (Blueprint $table) {
            $table->dropUnique('app_scores_gs_app_dom_unique');
            $table->dropForeign(['domain_id']);
            $table->renameColumn('domain_id', 'aptitude_area_id');
            $table->foreign('aptitude_area_id')
                ->references('id')->on('aptitude_areas')
                ->cascadeOnDelete();
            $table->unique(
                ['grading_session_id', 'applicant_id', 'aptitude_area_id'],
                'app_scores_gs_app_area_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('applicant_scores', function (Blueprint $table) {
            $table->dropUnique('app_scores_gs_app_area_unique');
            $table->dropForeign(['aptitude_area_id']);
            $table->renameColumn('aptitude_area_id', 'domain_id');
            $table->foreign('domain_id')
                ->references('id')->on('exam_domains')
                ->cascadeOnDelete();
            $table->unique(
                ['grading_session_id', 'applicant_id', 'domain_id'],
                'app_scores_gs_app_dom_unique'
            );
        });
    }
};
