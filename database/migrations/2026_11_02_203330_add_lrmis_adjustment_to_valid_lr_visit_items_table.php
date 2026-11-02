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
        Schema::table('valid_lr_visit_items', function (Blueprint $table) {
            $table->unsignedInteger('lrmis_original_quantity')->nullable()->after('lrmis_quantity');
            $table->foreignId('lrmis_adjusted_by')->nullable()->after('lrmis_original_quantity')->constrained('users')->nullOnDelete();
            $table->dateTime('lrmis_adjusted_at')->nullable()->after('lrmis_adjusted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valid_lr_visit_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lrmis_adjusted_by');
            $table->dropColumn(['lrmis_original_quantity', 'lrmis_adjusted_at']);
        });
    }
};
