<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppSettingsRequest;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingsController extends Controller
{
    public function edit(AppSettingsService $settingsService): Response
    {
        $recaptcha = $settingsService->recaptcha();

        return Inertia::render('AdminAppSettings', [
            'settings' => [
                ...$settingsService->branding(),
                ...$settingsService->smtp(),
                'recaptcha_enabled' => $recaptcha['recaptcha_enabled'],
                'recaptcha_site_key' => $recaptcha['recaptcha_site_key'],
                'recaptcha_secret_set' => filled($recaptcha['recaptcha_secret_key']),
            ],
            'colorThemes' => AppSettingsService::COLOR_THEMES,
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

        if ($request->hasFile('website_logo_file')) {
            $payload['website_logo_url'] = $this->storeLogo(
                $request->file('website_logo_file'),
                $branding['website_logo_url'] ?? null,
            );
        } elseif (! array_key_exists('website_logo_url', $payload)) {
            $payload['website_logo_url'] = $branding['website_logo_url'] ?? null;
        }

        unset($payload['login_logo_file'], $payload['app_logo_file'], $payload['website_logo_file']);

        $settingsService->update($payload);

        return back()->with('status', 'Application settings updated successfully.');
    }

    /**
     * Sends a short test message with the saved SMTP settings so admins can
     * confirm OTP and credential emails will go out.
     */
    public function sendTestMail(Request $request, AppSettingsService $settingsService): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        if (! $settingsService->applyToMailer()) {
            return back()->withErrors([
                'test_email' => 'SMTP is turned off or incomplete. Enable it and save the host and port first.',
            ]);
        }

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').'. If you received it, SMTP email sending works.',
                fn (Message $message) => $message->to($validated['test_email'])->subject(config('app.name').' SMTP test'),
            );
        } catch (\Throwable $exception) {
            Log::warning('SMTP test email failed.', ['message' => $exception->getMessage()]);

            return back()->withErrors([
                'test_email' => 'Sending failed: '.$exception->getMessage(),
            ]);
        }

        return back()->with('status', "Test email sent to {$validated['test_email']}.");
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
