<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('valid_lr_visits', function (Blueprint $table) {
            $table->foreignId('team_leader_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable()->after('team_leader_id');
            $table->dateTime('school_submitted_at')->nullable()->after('scheduled_at');
            $table->foreignId('school_submitted_by')->nullable()->after('school_submitted_at')->constrained('users')->nullOnDelete();
            $table->text('override_reason')->nullable()->after('school_submitted_by');
            $table->foreignId('overridden_by')->nullable()->after('override_reason')->constrained('users')->nullOnDelete();
            $table->dateTime('overridden_at')->nullable()->after('overridden_by');
            $table->dateTime('validation_started_at')->nullable()->after('overridden_at');
            $table->json('school_personnel')->nullable()->after('school_representative');
        });

        Schema::table('valid_lr_visit_items', function (Blueprint $table) {
            $table->unsignedInteger('school_quantity')->nullable()->after('distributed_quantity');
        });

        // Visits recorded before this workflow were started by the monitoring
        // team, so they count as already under validation.
        DB::table('valid_lr_visits')->update(['validation_started_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valid_lr_visit_items', function (Blueprint $table) {
            $table->dropColumn('school_quantity');
        });

        Schema::table('valid_lr_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_leader_id');
            $table->dropConstrainedForeignId('school_submitted_by');
            $table->dropConstrainedForeignId('overridden_by');
            $table->dropColumn(['scheduled_at', 'school_submitted_at', 'override_reason', 'overridden_at', 'validation_started_at', 'school_personnel']);
        });
    }
};
