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
        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->foreignId('learning_resource_type_id')
                ->nullable()
                ->after('school_id')
                ->constrained()
                ->restrictOnDelete();
        });

        $this->backfillResourceTypes();
        $this->backfillLegacyQuantities();

        // The new index must exist before the old one is dropped because the
        // school_id foreign key constraint relies on an index led by school_id.
        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->index(['school_id', 'learning_resource_type_id']);
        });

        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->dropIndex(['school_id', 'resource_type']);
            $table->dropColumn(['resource_type', 'quantity', 'issue_defect']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->string('resource_type')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('issue_defect')->nullable();
            $table->index(['school_id', 'resource_type']);
        });

        DB::table('learning_resources')
            ->join('learning_resource_types', 'learning_resource_types.id', '=', 'learning_resources.learning_resource_type_id')
            ->update([
                'resource_type' => DB::raw('learning_resource_types.name'),
                'quantity' => DB::raw('learning_resources.quantity_delivered'),
            ]);

        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->dropIndex(['school_id', 'learning_resource_type_id']);
            $table->dropConstrainedForeignId('learning_resource_type_id');
        });
    }

    /**
     * Link every learning resource to a learning resource type record,
     * creating types for legacy free-text values that have no match.
     */
    private function backfillResourceTypes(): void
    {
        $typeNames = DB::table('learning_resources')->distinct()->pluck('resource_type');

        foreach ($typeNames as $typeName) {
            $typeId = DB::table('learning_resource_types')->where('name', $typeName)->value('id');

            if ($typeId === null) {
                $typeId = DB::table('learning_resource_types')->insertGetId([
                    'name' => $typeName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('learning_resources')
                ->where('resource_type', $typeName)
                ->update(['learning_resource_type_id' => $typeId]);
        }
    }

    /**
     * Rows created before the delivered/issue columns existed only hold the
     * legacy quantity and issue_defect values, so copy them over before dropping.
     */
    private function backfillLegacyQuantities(): void
    {
        DB::table('learning_resources')
            ->whereNull('quantity_delivered')
            ->update(['quantity_delivered' => DB::raw('quantity')]);

        DB::table('learning_resources')
            ->whereNull('remarks')
            ->whereNotIn('issue_defect', ['', 'No remarks'])
            ->update(['remarks' => DB::raw('issue_defect')]);
    }
};
