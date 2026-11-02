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
        Schema::create('valid_lr_titles', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('grade_level');
            $table->string('learning_area');
            $table->string('title', 500)->nullable();
            $table->string('publisher')->nullable();
            $table->timestamps();

            $table->unique(['region', 'grade_level', 'learning_area'], 'valid_lr_titles_region_grade_area_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_titles');
    }
};
