<?php

use App\Services\LearningResourceDuplicateMerger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Merges any existing duplicate (school_id, resource_title_id) rows
     * before adding the unique index, so this migration is safe to run on
     * its own — an operator does not have to remember to run the
     * `learning-resources:merge-duplicates` command first.
     */
    public function up(): void
    {
        app(LearningResourceDuplicateMerger::class)->mergeAll();

        Schema::table('learning_resources', function (Blueprint $table) {
            $table->unique(['school_id', 'resource_title_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Only drops the index — merged rows are not un-merged (they were
     * soft-deleted, not destroyed, so the original data is still present
     * for manual recovery if ever needed).
     */
    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'resource_title_id']);
        });
    }
};
