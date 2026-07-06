<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolActivationRequest;
use App\Http\Requests\SchoolLookupRequest;
use App\Http\Requests\StoreLearningResourcesRequest;
use App\Http\Resources\LearningResourceResource;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use App\Services\SchoolActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function find(SchoolLookupRequest $request): JsonResponse
    {
        $school = School::with(['district', 'municipality', 'barangay'])
            ->where('school_id', $request->validated('school_id'))
            ->firstOrFail();

        return response()->json([
            'next_url' => route('school.activate.edit', $school),
            'message' => $school->is_activated
                ? 'School found. Update your school details before proceeding to learning resources.'
                : null,
        ]);
    }

    public function edit(School $school): RedirectResponse|Response
    {
        $school->load(['district', 'municipality', 'barangay']);

        return Inertia::render('SchoolActivationPage', [
            'school' => SchoolResource::make($school),
            'showCredentials' => false,
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
        SchoolActivationService $activationService,
    ): RedirectResponse {
        if ($school->is_activated) {
            $validated = $request->validated();

            $school->update([
                'school_head' => $validated['school_head'],
                'librarian' => $validated['librarian'] ?? null,
                'property_custodian' => $validated['property_custodian'] ?? null,
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

        $result = $activationService->activate($school, $request->validated());

        return redirect()
            ->route('school.activate.credentials', $school)
            ->with('generatedEmail', $result['user']->email)
            ->with('generatedPassword', $result['password']);
    }

    public function storeLearningResources(StoreLearningResourcesRequest $request): RedirectResponse|JsonResponse
    {
        $school = $request->user()?->school;

        abort_if(! $school, 403);

        DB::transaction(function () use ($request, $school): void {
            $school->learningResources()->delete();

            $school->learningResources()->createMany($request->validated('resources'));
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Learning resources saved successfully.',
                'resources' => LearningResourceResource::collection($school->learningResources()->latest()->get()),
            ]);
        }

        return back()->with('status', 'Learning resources saved successfully.');
    }
}
