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
        Schema::create('ict_equipment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ict_equipment_id')->constrained('ict_equipment')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('from_value')->nullable();
            $table->string('to_value')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'created_at']);
            $table->index(['ict_equipment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ict_equipment_movements');
    }
};
