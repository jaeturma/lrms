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
        Schema::create('valid_lr_tvl_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valid_lr_school_id')->constrained()->cascadeOnDelete();
            $table->string('specialization');
            $table->json('delivery_years')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->unique(['valid_lr_school_id', 'specialization'], 'valid_lr_tvl_programs_school_specialization_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_tvl_programs');
    }
};
