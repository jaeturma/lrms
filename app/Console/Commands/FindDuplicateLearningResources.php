<?php

namespace App\Console\Commands;

use App\Models\LearningResource;
use Illuminate\Console\Command;

class FindDuplicateLearningResources extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'learning-resources:find-duplicates {--json : Output as JSON instead of a table}';

    /**
     * The console command description.
     */
    protected $description = 'Report schools that have more than one LearningResource row for the same catalog title.';

    public function handle(): int
    {
        $duplicates = LearningResource::duplicateGroups();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate learning resource rows found.');

            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->line($duplicates->values()->toJson(JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->table(
            ['School ID', 'Resource Title ID', 'Duplicate Rows', 'Resource IDs (oldest first)'],
            $duplicates->map(fn (array $row): array => [
                $row['school_id'],
                $row['resource_title_id'],
                $row['total'],
                implode(', ', $row['resource_ids']),
            ]),
        );

        $this->warn("{$duplicates->count()} school/title combination(s) have duplicate rows.");
        $this->line('Run `php artisan learning-resources:merge-duplicates --dry-run` to preview a merge, or without --dry-run to merge them.');

        return self::SUCCESS;
    }
}
