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
        Schema::create('valid_lr_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valid_lr_school_id')->constrained()->cascadeOnDelete();
            $table->string('grade_level');
            $table->string('learning_area');
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['valid_lr_school_id', 'grade_level', 'learning_area'], 'valid_lr_allocations_school_grade_area_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_allocations');
    }
};
