<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestPasswordResetOtpRequest;
use App\Http\Requests\VerifyPasswordResetOtpRequest;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PasswordResetOtpController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function create(): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => session('status'),
        ]);
    }

    public function store(RequestPasswordResetOtpRequest $request, AppSettingsService $settingsService): RedirectResponse
    {
        $email = $request->validated('email');
        $user = User::query()->where('email', $email)->firstOrFail();

        if (! $settingsService->applyToMailer()) {
            return redirect()
                ->route('password.request')
                ->with('status', 'Password reset OTP is unavailable because SMTP is not configured. Please contact your admin.');
        }

        $otpCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        Cache::put(
            $this->otpCacheKey($email),
            [
                'otp_hash' => Hash::make($otpCode),
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            $expiresAt,
        );

        try {
            Mail::to($email)->send(new PasswordResetOtpMail(
                name: $user->name,
                otp: $otpCode,
                expiryMinutes: self::OTP_EXPIRY_MINUTES,
            ));
        } catch (Throwable $exception) {
            report($exception);
            Cache::forget($this->otpCacheKey($email));

            return redirect()
                ->route('password.request')
                ->with('status', 'OTP email could not be delivered. Please try again later.');
        }

        return redirect()
            ->route('password.otp.verify')
            ->with('otpEmail', $email)
            ->with('otpExpiresAt', $expiresAt->toIso8601String())
            ->with('status', 'A 6-digit OTP has been sent to your email. Enter it within 10 minutes to reset your password.');
    }

    public function showVerify(): RedirectResponse|Response
    {
        $email = session('otpEmail');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/reset-password', [
            'email' => $email,
            'otpExpiresAt' => session('otpExpiresAt'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function update(VerifyPasswordResetOtpRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $otpData = Cache::get($this->otpCacheKey($email));

        if (! is_array($otpData) || ! isset($otpData['otp_hash'])) {
            throw ValidationException::withMessages([
                'otp' => 'OTP expired. Please request a new code.',
            ]);
        }

        if (! Hash::check($request->validated('otp'), (string) $otpData['otp_hash'])) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid OTP. Please check and try again.',
            ]);
        }

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        Cache::forget($this->otpCacheKey($email));

        return redirect()
            ->route('login')
            ->with('status', 'Password reset successfully. Please sign in.');
    }

    private function otpCacheKey(string $email): string
    {
        return 'password_reset_otp:'.$email;
    }
}
