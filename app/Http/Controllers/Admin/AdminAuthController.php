<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('AdminLoginPage');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:50'],
            'password' => ['required', 'string', 'max:30'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $attempted = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false));

        $allowedRoles = [
            'admin',
            'superadmin',
            'sysadmin',
            'ito',
            'manager',
            'librarian',
            'supply',
            'cidchief',
            'asds',
            'sds',
        ];

        if ($attempted && ! in_array($request->user()?->role, $allowedRoles, true)) {
            Auth::logout();
            $attempted = false;
        }

        if (! $attempted) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are invalid.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
