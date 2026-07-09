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
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->string('source')->nullable()->after('remarks');
            $table->string('supplier')->nullable()->after('source');
            $table->date('date_delivered')->nullable()->after('supplier');
            $table->string('ier_no')->nullable()->after('date_delivered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropColumn(['source', 'supplier', 'date_delivered', 'ier_no']);
        });
    }
};
