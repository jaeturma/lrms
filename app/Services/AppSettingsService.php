<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;

class AppSettingsService
{
    /** @var array<int, string> */
    private const BRANDING_KEYS = [
        'login_title',
        'login_logo_url',
        'app_title',
        'app_logo_url',
    ];

    /** @var array<int, string> */
    private const SMTP_KEYS = [
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password_encrypted',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
    ];

    /**
     * @return array<string, string|null>
     */
    public function branding(): array
    {
        $values = AppSetting::valuesFor(self::BRANDING_KEYS);

        return [
            'login_title' => $values->get('login_title') ?: 'Log in to your account',
            'login_logo_url' => $values->get('login_logo_url'),
            'app_title' => $values->get('app_title') ?: config('app.name', 'LRMS'),
            'app_logo_url' => $values->get('app_logo_url'),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function smtp(): array
    {
        $values = AppSetting::valuesFor(self::SMTP_KEYS);

        return [
            'smtp_host' => $values->get('smtp_host'),
            'smtp_port' => $values->get('smtp_port'),
            'smtp_username' => $values->get('smtp_username'),
            'smtp_password' => $this->decryptNullable($values->get('smtp_password_encrypted')),
            'smtp_encryption' => $values->get('smtp_encryption'),
            'smtp_from_address' => $values->get('smtp_from_address'),
            'smtp_from_name' => $values->get('smtp_from_name'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        $values = [
            'login_title' => (string) ($data['login_title'] ?? ''),
            'login_logo_url' => $this->toNullableString($data['login_logo_url'] ?? null),
            'app_title' => (string) ($data['app_title'] ?? ''),
            'app_logo_url' => $this->toNullableString($data['app_logo_url'] ?? null),
            'smtp_host' => $this->toNullableString($data['smtp_host'] ?? null),
            'smtp_port' => $this->toNullableString($data['smtp_port'] ?? null),
            'smtp_username' => $this->toNullableString($data['smtp_username'] ?? null),
            'smtp_encryption' => $this->toNullableString($data['smtp_encryption'] ?? null),
            'smtp_from_address' => $this->toNullableString($data['smtp_from_address'] ?? null),
            'smtp_from_name' => $this->toNullableString($data['smtp_from_name'] ?? null),
        ];

        foreach ($values as $key => $value) {
            AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $smtpPassword = $this->toNullableString($data['smtp_password'] ?? null);
        if ($smtpPassword !== null) {
            AppSetting::query()->updateOrCreate(
                ['key' => 'smtp_password_encrypted'],
                ['value' => Crypt::encryptString($smtpPassword)],
            );
        }
    }

    private function decryptNullable(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
