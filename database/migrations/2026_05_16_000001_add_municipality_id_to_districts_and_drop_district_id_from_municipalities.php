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
        if (! Schema::hasColumn('districts', 'municipality_id')) {
            Schema::table('districts', function (Blueprint $table): void {
                $table->foreignId('municipality_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        // Backfill district -> municipality using the existing municipalities.district_id values.
        if (
            DB::getDriverName() === 'mysql'
            && Schema::hasColumn('municipalities', 'district_id')
            && Schema::hasColumn('districts', 'municipality_id')
        ) {
            DB::statement('UPDATE districts d
                LEFT JOIN (
                    SELECT district_id, MIN(id) AS municipality_id
                    FROM municipalities
                    WHERE district_id IS NOT NULL
                    GROUP BY district_id
                ) m ON m.district_id = d.id
                SET d.municipality_id = COALESCE(d.municipality_id, m.municipality_id)');
        }

        Schema::table('districts', function (Blueprint $table): void {
            if ($this->indexExists('districts', 'districts_name_unique')) {
                $table->dropUnique('districts_name_unique');
            }

            if (! $this->indexExists('districts', 'districts_municipality_id_name_unique')) {
                $table->unique(['municipality_id', 'name']);
            }
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('municipalities', 'district_id')) {
            // Drop FK regardless of generated constraint name. This must happen
            // before dropping any unique index on district_id, since MySQL
            // refuses to drop an index that a foreign key still relies on.
            $foreignKeys = DB::select(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL',
                ['municipalities', 'district_id']
            );

            foreach ($foreignKeys as $foreignKey) {
                DB::statement(sprintf(
                    'ALTER TABLE `municipalities` DROP FOREIGN KEY `%s`',
                    $foreignKey->CONSTRAINT_NAME,
                ));
            }

            Schema::table('municipalities', function (Blueprint $table): void {
                foreach ($this->uniqueIndexesForColumns('municipalities', ['district_id', 'name']) as $indexName) {
                    $table->dropUnique($indexName);
                }
            });

            Schema::table('municipalities', function (Blueprint $table): void {
                $table->dropColumn('district_id');
            });
        }

        if (DB::getDriverName() === 'sqlite' && Schema::hasColumn('municipalities', 'district_id')) {
            Schema::table('municipalities', function (Blueprint $table): void {
                $table->foreignId('district_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && ! Schema::hasColumn('municipalities', 'district_id')) {
            Schema::table('municipalities', function (Blueprint $table): void {
                $table->foreignId('district_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (DB::getDriverName() === 'sqlite' && Schema::hasColumn('municipalities', 'district_id')) {
            Schema::table('municipalities', function (Blueprint $table): void {
                $table->foreignId('district_id')->nullable(false)->change();
            });
        }

        if (DB::getDriverName() === 'mysql' && ! $this->indexExists('municipalities', 'municipalities_district_id_name_unique')) {
            Schema::table('municipalities', function (Blueprint $table): void {
                $table->unique(['district_id', 'name']);
            });
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('districts', 'municipality_id')) {
            DB::statement('UPDATE municipalities m
                LEFT JOIN districts d ON d.municipality_id = m.id
                SET m.district_id = COALESCE(m.district_id, d.id)');
        }

        if (Schema::hasColumn('districts', 'municipality_id')) {
            Schema::table('districts', function (Blueprint $table): void {
                if ($this->indexExists('districts', 'districts_municipality_id_name_unique')) {
                    $table->dropUnique(['municipality_id', 'name']);
                }

                $table->dropConstrainedForeignId('municipality_id');
            });
        }

        if (! $this->indexExists('districts', 'districts_name_unique')) {
            Schema::table('districts', function (Blueprint $table): void {
                $table->unique('name');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::selectOne(
            'SELECT COUNT(1) AS aggregate
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?',
            [$table, $index],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private function uniqueIndexesForColumns(string $table, array $columns): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [];
        }

        $target = implode(',', $columns);

        $indexes = DB::select(
            'SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_list
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             GROUP BY INDEX_NAME, NON_UNIQUE',
            [$table],
        );

        return collect($indexes)
            ->filter(fn ($index): bool => (int) ($index->NON_UNIQUE ?? 1) === 0)
            ->filter(fn ($index): bool => (string) ($index->columns_list ?? '') === $target)
            ->map(fn ($index): string => (string) $index->INDEX_NAME)
            ->values()
            ->all();
    }
};
