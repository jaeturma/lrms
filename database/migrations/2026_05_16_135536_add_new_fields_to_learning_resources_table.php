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
        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('resource_type');
            $table->unsignedInteger('quantity_delivered')->nullable()->after('publisher');
            $table->unsignedInteger('quantity_with_issue_defect')->nullable()->after('quantity_delivered');
            $table->string('remarks')->nullable()->after('quantity_with_issue_defect');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->dropColumn(['title', 'quantity_delivered', 'quantity_with_issue_defect', 'remarks']);
        });
    }
};
