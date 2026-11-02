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
        Schema::create('valid_lr_sme_items', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('group_label', 500)->nullable();
            $table->string('item_name', 1000);
            $table->string('unit')->nullable();
            $table->string('key_stage');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->index(['source', 'key_stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_sme_items');
    }
};
