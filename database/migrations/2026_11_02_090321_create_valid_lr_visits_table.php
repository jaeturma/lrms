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
        Schema::create('valid_lr_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valid_lr_school_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');
            $table->dateTime('visited_at')->nullable();
            $table->string('monitoring_team', 1000)->nullable();
            $table->string('school_representative')->nullable();
            $table->json('indicators')->nullable();
            $table->string('lr_delivery_status')->nullable();
            $table->string('sme_delivery_status')->nullable();
            $table->string('tvl_delivery_status')->nullable();
            $table->text('key_findings')->nullable();
            $table->text('recommended_actions')->nullable();
            $table->text('issues_concerns')->nullable();
            $table->text('actions_taken')->nullable();
            $table->string('prepared_by')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['valid_lr_school_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valid_lr_visits');
    }
};
