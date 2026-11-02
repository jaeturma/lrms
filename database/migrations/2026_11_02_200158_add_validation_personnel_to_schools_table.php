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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('lr_designate')->nullable()->after('librarian');
            $table->string('ict_coordinator')->nullable()->after('lr_designate');
            $table->string('math_coordinator')->nullable()->after('ict_coordinator');
            $table->string('science_coordinator')->nullable()->after('math_coordinator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['lr_designate', 'ict_coordinator', 'math_coordinator', 'science_coordinator']);
        });
    }
};
