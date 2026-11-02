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
        Schema::create('valid_lr_visit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valid_lr_visit_id')->constrained()->cascadeOnDelete();
            $table->string('section', 20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('group_label', 500)->nullable();
            $table->string('label', 1000);
            $table->string('title', 500)->nullable();
            $table->string('publisher')->nullable();
            $table->string('unit')->nullable();
            $table->string('procurement_source')->nullable();
            $table->unsignedInteger('reference_quantity')->nullable();
            $table->unsignedInteger('lrmis_quantity')->nullable();
            $table->unsignedInteger('lrms_quantity')->nullable();
            $table->unsignedInteger('distributed_quantity')->nullable();
            $table->unsignedInteger('actual_quantity')->nullable();
            $table->unsignedInteger('enrolment')->nullable();
            $table->string('year_received')->nullable();
            $table->json('mov')->nullable();
            $table->json('extra')->nullable();
            $table->string('remarks_repairs', 1000)->nullable();
            $table->string('remarks_missing', 1000)->nullable();
            $table->string('remarks_others', 1000)->nullable();
            $table->timestamps();

            $table->index(['valid_lr_visit_id', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_visit_items');
    }
};
