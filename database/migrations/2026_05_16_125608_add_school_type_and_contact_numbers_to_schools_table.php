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
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('school_type')->nullable()->after('school_name');
            $table->string('primary_mobile_no')->nullable()->after('property_custodian');
            $table->string('secondary_mobile_no')->nullable()->after('primary_mobile_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['school_type', 'primary_mobile_no', 'secondary_mobile_no']);
        });
    }
};
