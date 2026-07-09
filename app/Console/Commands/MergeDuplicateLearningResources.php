<?php

namespace App\Console\Commands;

use App\Models\LearningResource;
use App\Services\LearningResourceDuplicateMerger;
use Illuminate\Console\Command;

class MergeDuplicateLearningResources extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'learning-resources:merge-duplicates
        {--dry-run : Report what would be merged without writing any changes}';

    /**
     * The console command description.
     */
    protected $description = 'Merge duplicate LearningResource rows (same school + catalog title) into a single row, preserving quantities and movement history.';

    public function handle(LearningResourceDuplicateMerger $merger): int
    {
        $groups = LearningResource::duplicateGroups();

        if ($groups->isEmpty()) {
            $this->info('No duplicate learning resource rows found. Nothing to merge.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $prefix = $dryRun ? '[DRY RUN] ' : '';

        $this->info("{$prefix}Found {$groups->count()} school/title combination(s) with duplicate rows.");

        foreach ($groups as $group) {
            $survivorId = $group['resource_ids'][0];
            $duplicateIds = array_slice($group['resource_ids'], 1);

            $this->line(
                "{$prefix}School #{$group['school_id']}, title #{$group['resource_title_id']}: ".
                'merging ['.implode(', ', $duplicateIds)."] into #{$survivorId}",
            );

            if (! $dryRun) {
                $merger->mergeGroup($group['resource_ids']);
            }
        }

        $this->info($dryRun
            ? 'Dry run complete — no changes were written. Re-run without --dry-run to merge.'
            : 'Merge complete.');

        return self::SUCCESS;
    }
}
