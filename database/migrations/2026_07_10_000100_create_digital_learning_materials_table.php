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
        Schema::create('digital_learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('type');
            $table->string('publisher')->nullable();
            $table->string('link')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('description', 1000)->nullable();
            $table->boolean('quality_assured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_learning_materials');
    }
};
