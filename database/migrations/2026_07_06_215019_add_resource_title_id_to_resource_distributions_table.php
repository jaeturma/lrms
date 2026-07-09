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
        Schema::table('resource_distributions', function (Blueprint $table): void {
            $table->foreignId('resource_title_id')
                ->nullable()
                ->after('learning_resource_type_id')
                ->constrained('resource_titles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_distributions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('resource_title_id');
        });
    }
};
