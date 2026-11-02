<?php

namespace App\Rules;

use App\Services\RecaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidRecaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(RecaptchaService::class)->verify(is_string($value) ? $value : null, request()->ip())) {
            $fail('Please confirm you are not a robot.');
        }
    }

    /**
     * Validation rules for the reCAPTCHA response field: required only while
     * reCAPTCHA is enabled.
     *
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return app(RecaptchaService::class)->active()
            ? ['required', 'string', new self]
            : ['nullable'];
    }
}
