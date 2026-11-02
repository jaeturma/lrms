<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Lets a school upload its own logo, shown on its dashboard and on VALID-LR
 * printouts.
 */
class SchoolLogoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $school = $this->resolveSchool($request);

        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $this->deleteLogo($school);

        $school->update([
            'logo_path' => $validated['logo']->store('school-logos', 'public'),
        ]);

        return back()->with('status', 'School logo updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $school = $this->resolveSchool($request);

        $this->deleteLogo($school);
        $school->update(['logo_path' => null]);

        return back()->with('status', 'School logo removed.');
    }

    private function deleteLogo(School $school): void
    {
        if ($school->logo_path) {
            Storage::disk('public')->delete($school->logo_path);
        }
    }

    private function resolveSchool(Request $request): School
    {
        $school = $request->user()?->school;

        abort_if(! $school, 403);

        return $school;
    }
}
