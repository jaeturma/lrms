<?php

namespace App\Console\Commands;

use App\Services\ValidLrReferenceImportService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ImportValidLrReferenceData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'valid-lr:import
        {type : reference_data, lrmis_encoded, sme_masterlist, or monitoring_tool}
        {path : Path to the .xlsx workbook}
        {--division= : Division to keep (required for reference_data), e.g. "Davao De Oro"}';

    /**
     * The console command description.
     */
    protected $description = 'Import a VALID-LR reference workbook (_reference_data, LRMIS_ENCODED, masterlist lte, or the monitoring tool).';

    public function handle(ValidLrReferenceImportService $importService): int
    {
        $type = (string) $this->argument('type');
        $path = (string) $this->argument('path');

        if (! in_array($type, ValidLrReferenceImportService::TYPES, true)) {
            $this->error('Type must be one of: '.implode(', ', ValidLrReferenceImportService::TYPES));

            return self::INVALID;
        }

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::INVALID;
        }

        try {
            $summary = $importService->import($type, $path, $this->option('division'));
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        $this->table(['Metric', 'Value'], collect($summary)->map(fn (int|string $value, string $metric): array => [$metric, $value])->values());
        $this->info('VALID-LR import complete.');

        return self::SUCCESS;
    }
}
