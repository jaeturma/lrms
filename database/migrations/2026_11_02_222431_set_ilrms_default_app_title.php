<?php

use App\Services\AppSettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace a blank or framework-default app title with the iLRMS branding.
     * Titles a system admin chose themselves are left alone.
     */
    public function up(): void
    {
        DB::table('app_settings')
            ->where('key', 'app_title')
            ->where(fn ($query) => $query->whereNull('value')->orWhereIn('value', ['', 'Laravel', 'LRMS']))
            ->update(['value' => AppSettingsService::DEFAULT_APP_TITLE, 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
