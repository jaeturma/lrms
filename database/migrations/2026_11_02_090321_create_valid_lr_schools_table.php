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
        Schema::create('valid_lr_schools', function (Blueprint $table) {
            $table->id();
            $table->string('beis_school_id')->unique();
            $table->string('region')->nullable();
            $table->string('division')->nullable()->index();
            $table->string('district')->nullable();
            $table->string('school_name');
            $table->string('street_address')->nullable();
            $table->string('mother_school_id')->nullable();
            $table->string('province')->nullable();
            $table->string('municipality')->nullable();
            $table->string('legislative_district')->nullable();
            $table->string('barangay')->nullable();
            $table->string('sector')->nullable();
            $table->string('school_type')->nullable();
            $table->string('curricular_offering')->nullable();
            $table->json('sme_deliveries')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_schools');
    }
};
