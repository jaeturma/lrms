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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('item_code')->nullable()->unique();
            $table->string('item_name');
            $table->string('category');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('specifications', 2000)->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('property_number')->nullable();
            $table->string('barcode')->nullable();
            $table->string('qr_code')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 12, 2)->nullable();
            $table->string('funding_source')->nullable();
            $table->string('supplier')->nullable();
            $table->date('warranty_expires_on')->nullable();
            $table->unsignedSmallInteger('useful_life_years')->nullable();
            $table->string('current_location')->nullable();
            $table->string('assigned_personnel')->nullable();
            $table->string('condition');
            $table->string('status');
            $table->string('remarks', 1000)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
