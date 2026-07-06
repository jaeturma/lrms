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
        Schema::create('resource_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_resource_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_level_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->string('language')->nullable();
            $table->string('subject')->nullable();
            $table->string('volume')->nullable();
            $table->string('edition')->nullable();
            $table->unsignedSmallInteger('copyright_year')->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->string('isbn')->nullable();
            $table->string('description', 1000)->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('media_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['learning_resource_type_id', 'is_active']);
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_titles');
    }
};
