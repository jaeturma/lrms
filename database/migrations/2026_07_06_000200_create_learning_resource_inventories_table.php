<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('learning_resource_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_resource_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('available')->default(0);
            $table->unsignedInteger('issued')->default(0);
            $table->unsignedInteger('borrowed')->default(0);
            $table->unsignedInteger('damaged')->default(0);
            $table->unsignedInteger('lost')->default(0);
            $table->unsignedInteger('condemned')->default(0);
            $table->timestamps();
        });

        $this->backfillInventories();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_resource_inventories');
    }

    /**
     * Seed an inventory row for every existing learning resource, treating
     * copies with issues/defects as damaged and the remainder as available.
     */
    private function backfillInventories(): void
    {
        DB::table('learning_resources')
            ->orderBy('id')
            ->each(function (object $resource): void {
                $delivered = (int) ($resource->quantity_delivered ?? 0);
                $damaged = min((int) ($resource->quantity_with_issue_defect ?? 0), $delivered);

                DB::table('learning_resource_inventories')->insert([
                    'learning_resource_id' => $resource->id,
                    'available' => $delivered - $damaged,
                    'damaged' => $damaged,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
};
