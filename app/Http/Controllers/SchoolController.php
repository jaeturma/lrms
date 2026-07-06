<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolActivationRequest;
use App\Http\Requests\SchoolLookupRequest;
use App\Http\Requests\StoreLearningResourcesRequest;
use App\Http\Requests\VerifySchoolActivationOtpRequest;
use App\Http\Resources\LearningResourceResource;
use App\Http\Resources\SchoolResource;
use App\Mail\SchoolActivationOtpMail;
use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\ResourceTitle;
use App\Models\School;
use App\Services\AppSettingsService;
use App\Services\LearningResourceInventoryService;
use App\Services\SchoolActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SchoolController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 5;

    public function find(SchoolLookupRequest $request): JsonResponse
    {
        $school = School::with(['district', 'municipality', 'barangay'])
            ->where('school_id', $request->validated('school_id'))
            ->firstOrFail();

        if ($school->is_activated) {
            return response()->json([
                'is_activated' => true,
                'message' => 'School is already Activated, please login.',
                'redirect_url' => route('login'),
            ]);
        }

        return response()->json([
            'next_url' => route('school.activate.edit', $school),
            'message' => null,
        ]);
    }

    public function edit(School $school, AppSettingsService $settingsService): RedirectResponse|Response
    {
        $school->load(['district', 'municipality', 'barangay']);
        $smtp = $settingsService->smtp();
        $otpEnabled = (bool) ($smtp['smtp_host'] && $smtp['smtp_port']);

        return Inertia::render('SchoolActivationPage', [
            'school' => SchoolResource::make($school),
            'showCredentials' => false,
            'otpPending' => (bool) session('otpPending', false),
            'otpExpiresAt' => session('otpExpiresAt'),
            'otpEnabled' => $otpEnabled,
            'municipalities' => Municipality::query()->orderBy('name')->get(['id', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'municipality_id', 'name']),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'municipality_id', 'name']),
            'schoolTypes' => School::SCHOOL_TYPES,
        ]);
    }

    public function credentials(School $school, Request $request): RedirectResponse|Response
    {
        $generatedPassword = $request->session()->get('generatedPassword');
        $generatedEmail = $request->session()->get('generatedEmail');

        if (! $generatedPassword || ! $generatedEmail) {
            return redirect()->route('login');
        }

        return Inertia::render('SchoolActivationPage', [
            'school' => SchoolResource::make($school->load(['district', 'municipality', 'barangay'])),
            'showCredentials' => true,
            'generatedPassword' => $generatedPassword,
            'generatedEmail' => $generatedEmail,
        ]);
    }

    public function activate(
        SchoolActivationRequest $request,
        School $school,
        AppSettingsService $settingsService,
    ): RedirectResponse {
        $validated = $request->validated();

        if ($school->is_activated) {
            $school->update([
                'municipality_id' => $validated['municipality_id'] ?? $school->municipality_id,
                'district_id' => $validated['district_id'] ?? $school->district_id,
                'barangay_id' => $validated['barangay_id'] ?? $school->barangay_id,
                'school_head' => $validated['school_head'],
                'librarian' => $validated['librarian'] ?? null,
                'property_custodian' => $validated['property_custodian'] ?? null,
                'primary_mobile_no' => $validated['primary_mobile_no'] ?? null,
                'secondary_mobile_no' => $validated['secondary_mobile_no'] ?? null,
                'email' => $validated['email'],
            ]);

            if ($school->user) {
                $school->user->update([
                    'name' => $school->school_name,
                    'email' => $validated['email'],
                ]);
            }

            $authenticatedUser = $request->user();

            if ($authenticatedUser && (int) $authenticatedUser->school_id === (int) $school->id) {
                return redirect()
                    ->route('dashboard')
                    ->with('status', 'School details updated. You can now manage learning resources.');
            }

            return redirect()
                ->route('login')
                ->with('status', 'School details updated. Sign in to encode learning resources.');
        }

        $school->update([
            'municipality_id' => $validated['municipality_id'] ?? $school->municipality_id,
            'district_id' => $validated['district_id'] ?? $school->district_id,
            'barangay_id' => $validated['barangay_id'] ?? $school->barangay_id,
            'school_head' => $validated['school_head'],
            'librarian' => $validated['librarian'] ?? null,
            'property_custodian' => $validated['property_custodian'] ?? null,
            'primary_mobile_no' => $validated['primary_mobile_no'] ?? null,
            'secondary_mobile_no' => $validated['secondary_mobile_no'] ?? null,
            'email' => $validated['email'],
            'activation_requested_at' => now(),
        ]);

        $otpCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        Cache::put(
            $this->otpCacheKey($school),
            [
                'otp_hash' => Hash::make($otpCode),
                'payload' => $validated,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            $expiresAt,
        );

        $smtp = $settingsService->smtp();

        if (! $smtp['smtp_host'] || ! $smtp['smtp_port']) {
            Cache::forget($this->otpCacheKey($school));

            return redirect()
                ->route('school.activate.edit', $school)
                ->with('status', 'Activation request submitted. OTP email is unavailable because SMTP is not configured. Please coordinate with your admin for manual activation approval.');
        }

        try {
            $this->applySmtpSettings($smtp);

            Mail::to($validated['email'])->send(new SchoolActivationOtpMail(
                schoolName: $school->school_name,
                otp: $otpCode,
                expiryMinutes: self::OTP_EXPIRY_MINUTES,
            ));
        } catch (Throwable $exception) {
            report($exception);
            Cache::forget($this->otpCacheKey($school));

            return redirect()
                ->route('school.activate.edit', $school)
                ->with('status', 'Activation request submitted. OTP email could not be delivered. Please coordinate with your admin for manual activation approval.');
        }

        return redirect()
            ->route('school.activate.edit', $school)
            ->with('status', 'A 6-digit OTP has been sent to your email. Enter it within 5 minutes to continue activation.')
            ->with('otpPending', true)
            ->with('otpExpiresAt', $expiresAt->toIso8601String());
    }

    public function verifyActivationOtp(
        VerifySchoolActivationOtpRequest $request,
        School $school,
        SchoolActivationService $activationService,
    ): RedirectResponse {
        if ($school->is_activated) {
            return redirect()->route('login');
        }

        $otpData = Cache::get($this->otpCacheKey($school));

        if (! is_array($otpData) || ! isset($otpData['otp_hash'], $otpData['payload'])) {
            throw ValidationException::withMessages([
                'otp' => 'OTP expired. Please request a new code.',
            ]);
        }

        if (! Hash::check($request->validated('otp'), (string) $otpData['otp_hash'])) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid OTP. Please check and try again.',
            ]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $otpData['payload'];

        $result = $activationService->activate($school, [
            'school_head' => (string) $payload['school_head'],
            'librarian' => $payload['librarian'] ?? null,
            'property_custodian' => $payload['property_custodian'] ?? null,
            'primary_mobile_no' => $payload['primary_mobile_no'] ?? null,
            'secondary_mobile_no' => $payload['secondary_mobile_no'] ?? null,
            'email' => (string) $payload['email'],
            'municipality_id' => $payload['municipality_id'] ?? null,
            'district_id' => $payload['district_id'] ?? null,
            'barangay_id' => $payload['barangay_id'] ?? null,
        ]);

        Cache::forget($this->otpCacheKey($school));

        return redirect()
            ->route('school.activate.credentials', $school)
            ->with('generatedEmail', $result['user']->email)
            ->with('generatedPassword', $result['password']);
    }

    private function otpCacheKey(School $school): string
    {
        return 'school_activation_otp:'.$school->id;
    }

    /**
     * @param  array<string, string|null>  $smtp
     */
    private function applySmtpSettings(array $smtp): void
    {
        if (! $smtp['smtp_host'] || ! $smtp['smtp_port']) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $smtp['smtp_host']);
        Config::set('mail.mailers.smtp.port', (int) $smtp['smtp_port']);
        Config::set('mail.mailers.smtp.username', $smtp['smtp_username']);
        Config::set('mail.mailers.smtp.password', $smtp['smtp_password']);
        Config::set('mail.mailers.smtp.encryption', $smtp['smtp_encryption'] ?: null);
        Config::set('mail.from.address', $smtp['smtp_from_address'] ?: config('mail.from.address'));
        Config::set('mail.from.name', $smtp['smtp_from_name'] ?: config('mail.from.name'));
    }

    public function storeLearningResources(
        StoreLearningResourcesRequest $request,
        LearningResourceInventoryService $inventoryService,
    ): RedirectResponse|JsonResponse {
        $school = $request->user()?->school;

        abort_if(! $school, 403);

        $user = $request->user();
        $resources = collect($request->validated('resources'));
        $existingIds = $school->learningResources()->pluck('id');
        $submittedIds = $resources->pluck('id')->filter()->map(fn (mixed $id): int => (int) $id);

        if ($submittedIds->diff($existingIds)->isNotEmpty()) {
            abort(403);
        }

        $catalogTitles = ResourceTitle::query()
            ->whereIn('id', $resources->pluck('resource_title_id')->filter())
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($school, $resources, $existingIds, $submittedIds, $user, $inventoryService, $catalogTitles): void {
            $school->learningResources()
                ->whereIn('id', $existingIds->diff($submittedIds))
                ->get()
                ->each(fn ($resource) => $resource->delete());

            foreach ($resources as $payload) {
                $catalogTitle = $catalogTitles->get((int) ($payload['resource_title_id'] ?? 0));

                // Catalog-backed entries take every descriptive detail from the
                // division catalog; the school only supplies the quantities.
                $attributes = $catalogTitle
                    ? [
                        'resource_title_id' => $catalogTitle->id,
                        'learning_resource_type_id' => $catalogTitle->learning_resource_type_id,
                        'grade_level_id' => $catalogTitle->grade_level_id,
                        'title' => $catalogTitle->title,
                        'author' => $catalogTitle->author,
                        'publisher' => $catalogTitle->publisher ?? '',
                        'language' => $catalogTitle->language,
                        'subject' => $catalogTitle->subject,
                        'volume' => $catalogTitle->volume,
                        'edition' => $catalogTitle->edition,
                        'copyright_year' => $catalogTitle->copyright_year,
                        'pages' => $catalogTitle->pages,
                        'isbn' => $catalogTitle->isbn,
                        'quantity_delivered' => $payload['quantity_delivered'],
                        'quantity_with_issue_defect' => $payload['quantity_with_issue_defect'],
                        'remarks' => $payload['remarks'] ?? null,
                    ]
                    : [
                        'resource_title_id' => null,
                        'learning_resource_type_id' => $payload['learning_resource_type_id'],
                        'title' => $payload['title'],
                        'publisher' => $payload['publisher'],
                        'quantity_delivered' => $payload['quantity_delivered'],
                        'quantity_with_issue_defect' => $payload['quantity_with_issue_defect'],
                        'remarks' => $payload['remarks'] ?? null,
                    ];

                if (! empty($payload['id'])) {
                    $resource = $school->learningResources()->with('inventory')->findOrFail((int) $payload['id']);
                    $previousDelivered = (int) $resource->quantity_delivered;
                    $previousDamaged = min((int) $resource->quantity_with_issue_defect, $previousDelivered);

                    $resource->update($attributes);
                    $inventoryService->applyEncodingUpdate($resource, $previousDelivered, $previousDamaged, $user);

                    continue;
                }

                $resource = $school->learningResources()->create($attributes);
                $inventoryService->initialize($resource, $user);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Learning resources saved successfully.',
                'resources' => LearningResourceResource::collection(
                    $school->learningResources()->with(['learningResourceType', 'inventory'])->latest()->get(),
                ),
            ]);
        }

        return back()->with('status', 'Learning resources saved successfully.');
    }
}
