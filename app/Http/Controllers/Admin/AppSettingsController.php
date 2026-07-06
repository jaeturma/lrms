<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppSettingsRequest;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingsController extends Controller
{
    public function edit(AppSettingsService $settingsService): Response
    {
        return Inertia::render('AdminAppSettings', [
            'settings' => [
                ...$settingsService->branding(),
                ...$settingsService->smtp(),
            ],
        ]);
    }

    public function update(UpdateAppSettingsRequest $request, AppSettingsService $settingsService): RedirectResponse
    {
        $payload = $request->validated();
        $branding = $settingsService->branding();

        if ($request->hasFile('login_logo_file')) {
            $payload['login_logo_url'] = $this->storeLogo(
                $request->file('login_logo_file'),
                $branding['login_logo_url'] ?? null,
            );
        } elseif (! array_key_exists('login_logo_url', $payload)) {
            $payload['login_logo_url'] = $branding['login_logo_url'] ?? null;
        }

        if ($request->hasFile('app_logo_file')) {
            $payload['app_logo_url'] = $this->storeLogo(
                $request->file('app_logo_file'),
                $branding['app_logo_url'] ?? null,
            );
        } elseif (! array_key_exists('app_logo_url', $payload)) {
            $payload['app_logo_url'] = $branding['app_logo_url'] ?? null;
        }

        unset($payload['login_logo_file'], $payload['app_logo_file']);

        $settingsService->update($payload);

        return back()->with('status', 'Application settings updated successfully.');
    }

    private function storeLogo(?UploadedFile $file, ?string $oldUrl): ?string
    {
        if (! $file) {
            return $oldUrl;
        }

        $this->deleteManagedLogo($oldUrl);

        $path = $file->store('branding', 'public');

        return Storage::disk('public')->url($path);
    }

    private function deleteManagedLogo(?string $url): void
    {
        if (! $url) {
            return;
        }

        $prefix = Storage::disk('public')->url('');
        if (! str_starts_with($url, $prefix)) {
            return;
        }

        $relativePath = ltrim(substr($url, strlen($prefix)), '/');
        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
