<?php

namespace App\Http\Middleware;

use App\Services\AppSettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'branding' => app(AppSettingsService::class)->branding(),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'status' => $request->session()->get('status'),
                'generatedPassword' => $request->session()->get('generatedPassword'),
                'generatedEmail' => $request->session()->get('generatedEmail'),
                'otpPending' => $request->session()->get('otpPending'),
                'otpExpiresAt' => $request->session()->get('otpExpiresAt'),
                'importSummary' => $request->session()->get('importSummary'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
