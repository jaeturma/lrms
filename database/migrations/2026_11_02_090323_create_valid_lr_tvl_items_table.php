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
        Schema::create('valid_lr_tvl_items', function (Blueprint $table) {
            $table->id();
            $table->string('specialization');
            $table->string('item_name', 1000);
            $table->string('procurement_source')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->index('specialization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_tvl_items');
    }
};
