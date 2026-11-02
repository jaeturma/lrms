<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies Google reCAPTCHA v2 responses when an admin has enabled it in
 * App Settings. When it is off (or keys are missing) every check passes.
 */
class RecaptchaService
{
    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(private AppSettingsService $settingsService) {}

    public function active(): bool
    {
        return $this->settingsService->recaptchaActive();
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->active()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => $this->settingsService->recaptcha()['recaptcha_secret_key'],
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $exception) {
            Log::warning('reCAPTCHA verification request failed.', ['message' => $exception->getMessage()]);

            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
