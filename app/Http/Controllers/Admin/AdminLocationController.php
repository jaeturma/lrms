<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Services\LocationImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLocationController extends Controller
{
    public function index(Request $request): Response
    {
        $module = $request->string('module')->toString();

        return $this->renderPage($request, $module);
    }

    public function districts(Request $request): Response
    {
        return $this->renderPage($request, 'districts');
    }

    public function municipalities(Request $request): Response
    {
        return $this->renderPage($request, 'municipalities');
    }

    public function barangays(Request $request): Response
    {
        return $this->renderPage($request, 'barangays');
    }

    private function renderPage(Request $request, string $module): Response
    {
        $activeModule = in_array($module, ['districts', 'municipalities', 'barangays'], true)
            ? $module
            : 'all';

        $breadcrumbs = [
            ['title' => 'Dashboard', 'href' => route('admin.dashboard')],
        ];

        match ($activeModule) {
            'districts' => $breadcrumbs[] = ['title' => 'Districts', 'href' => route('admin.districts.index')],
            'municipalities' => $breadcrumbs[] = ['title' => 'Municipalities', 'href' => route('admin.municipalities.index')],
            'barangays' => $breadcrumbs[] = ['title' => 'Barangays', 'href' => route('admin.barangays.index')],
            default => $breadcrumbs[] = ['title' => 'Location Management', 'href' => route('admin.locations.index')],
        };

        return Inertia::render('AdminLocationsPage', [
            'activeModule' => $activeModule,
            'breadcrumbs' => $breadcrumbs,
            'summary' => $request->session()->get('importSummary'),
            'districts' => District::query()
                ->with('municipality:id,name')
                ->withCount(['schools'])
                ->orderBy('name')
                ->get()
                ->map(fn (District $district): array => [
                    'id' => $district->id,
                    'municipality_id' => $district->municipality_id,
                    'municipality' => $district->municipality?->name,
                    'name' => $district->name,
                    'schools_count' => $district->schools_count,
                ]),
            'municipalities' => Municipality::query()
                ->withCount(['districts', 'barangays', 'schools'])
                ->orderBy('name')
                ->get()
                ->map(fn (Municipality $municipality): array => [
                    'id' => $municipality->id,
                    'name' => $municipality->name,
                    'districts_count' => $municipality->districts_count,
                    'barangays_count' => $municipality->barangays_count,
                    'schools_count' => $municipality->schools_count,
                ]),
            'barangays' => Barangay::query()
                ->with('municipality:id,name')
                ->withCount('schools')
                ->orderBy('name')
                ->get()
                ->map(fn (Barangay $barangay): array => [
                    'id' => $barangay->id,
                    'name' => $barangay->name,
                    'municipality_id' => $barangay->municipality_id,
                    'municipality' => $barangay->municipality?->name,
                    'schools_count' => $barangay->schools_count,
                ]),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        match ($type) {
            'districts' => District::query()->create($request->validate([
                'municipality_id' => ['required', 'exists:municipalities,id'],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('districts', 'name')->where(fn ($query) => $query->where('municipality_id', $request->integer('municipality_id'))),
                ],
            ])),
            'municipalities' => Municipality::query()->create($request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('municipalities', 'name')],
            ])),
            'barangays' => Barangay::query()->create($request->validate([
                'municipality_id' => ['required', 'exists:municipalities,id'],
                'name' => ['required', 'string', 'max:255', Rule::unique('barangays', 'name')->where(fn ($query) => $query->where('municipality_id', $request->integer('municipality_id')))],
            ])),
            default => abort(404),
        };

        return back()->with('status', 'Location saved successfully.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        match ($type) {
            'districts' => $this->updateDistrict($request, $id),
            'municipalities' => $this->updateMunicipality($request, $id),
            'barangays' => $this->updateBarangay($request, $id),
            default => abort(404),
        };

        return back()->with('status', 'Location updated successfully.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        match ($type) {
            'districts' => $this->destroyDistrict($id),
            'municipalities' => $this->destroyMunicipality($id),
            'barangays' => $this->destroyBarangay($id),
            default => abort(404),
        };

        return back()->with('status', 'Location deleted successfully.');
    }

    public function import(Request $request, LocationImportService $locationImportService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $summary = $locationImportService->import($validated['csv']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Location CSV imported successfully.',
                'summary' => $summary,
            ]);
        }

        return redirect()
            ->route('admin.locations.index')
            ->with('importSummary', $summary)
            ->with('status', 'Location CSV imported successfully.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            ['district', 'municipality', 'barangay'],
            ['District I', 'San Isidro', 'Poblacion'],
            ['District I', 'San Isidro', 'North Baybay'],
            ['District II', 'Santa Maria', ''],
            ['District III', '', ''],
        ];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'location-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function updateDistrict(Request $request, int $id): void
    {
        $district = District::query()->findOrFail($id);

        $validated = $request->validate([
            'municipality_id' => ['required', 'exists:municipalities,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('districts', 'name')
                    ->ignore($district->id)
                    ->where(fn ($query) => $query->where('municipality_id', $request->integer('municipality_id'))),
            ],
        ]);

        $district->update($validated);
    }

    private function updateMunicipality(Request $request, int $id): void
    {
        $municipality = Municipality::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('municipalities', 'name')->ignore($municipality->id)],
        ]);

        $municipality->update($validated);
    }

    private function updateBarangay(Request $request, int $id): void
    {
        $barangay = Barangay::query()->findOrFail($id);

        $validated = $request->validate([
            'municipality_id' => ['required', 'exists:municipalities,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('barangays', 'name')
                ->ignore($barangay->id)
                ->where(fn ($query) => $query->where('municipality_id', $request->integer('municipality_id')))],
        ]);

        $barangay->update($validated);
    }

    private function destroyDistrict(int $id): void
    {
        $district = District::query()->withCount('schools')->findOrFail($id);

        if ($district->schools_count > 0) {
            abort(422, 'District cannot be deleted while it has schools.');
        }

        $district->delete();
    }

    private function destroyMunicipality(int $id): void
    {
        $municipality = Municipality::query()->withCount(['districts', 'schools', 'barangays'])->findOrFail($id);

        if ($municipality->districts_count > 0 || $municipality->schools_count > 0 || $municipality->barangays_count > 0) {
            abort(422, 'Municipality cannot be deleted while it has districts, barangays, or schools.');
        }

        $municipality->delete();
    }

    private function destroyBarangay(int $id): void
    {
        $barangay = Barangay::query()->withCount('schools')->findOrFail($id);

        if ($barangay->schools_count > 0) {
            abort(422, 'Barangay cannot be deleted while it is assigned to a school.');
        }

        DB::transaction(function () use ($barangay): void {
            $barangay->delete();
        });
    }
}
