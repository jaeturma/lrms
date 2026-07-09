<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The shared `equipment` table has been split into `ict_equipment` and
     * `other_equipment` (see the migrations created alongside this one), so
     * the old tables are no longer needed.
     */
    public function up(): void
    {
        Schema::dropIfExists('equipment_movements');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_catalog_items');
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally a no-op: this is a deprecated-table removal, not
     * recoverable via rollback. Restore from backup if needed.
     */
    public function down(): void
    {
        //
    }
};
