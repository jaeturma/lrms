import { Head } from '@inertiajs/react';

type Option = {
    id: number;
    name: string;
};

type AdequacyRow = {
    school_id: string;
    school_name: string;
    district: string | null;
    municipality: string | null;
    learners: number;
    available_copies: number;
    copies_per_learner: number | null;
    shortage: number;
};

type CategoryRow = {
    category: string;
    conditions: Record<string, number>;
    total: number;
};

type Props = {
    filters: {
        school_year_id: number | null;
        district_id: number | null;
        municipality_id: number | null;
    };
    schoolYears: Option[];
    districts: Option[];
    municipalities: Option[];
    resourceAdequacy: AdequacyRow[];
    resourceSummary: {
        total_learners: number;
        total_available: number;
        schools_in_shortage: number;
    };
    equipmentByCategory: CategoryRow[];
    equipmentByStatus: Record<string, number>;
    equipmentConditions: string[];
};

function exportUrl(path: string, filters: Props['filters']): string {
    const params = new URLSearchParams();

    if (filters.school_year_id) params.set('school_year_id', String(filters.school_year_id));
    if (filters.district_id) params.set('district_id', String(filters.district_id));
    if (filters.municipality_id) params.set('municipality_id', String(filters.municipality_id));

    const query = params.toString();

    return query ? `${path}?${query}` : path;
}

export default function AdminReports({
    filters,
    schoolYears,
    districts,
    municipalities,
    resourceAdequacy,
    resourceSummary,
    equipmentByCategory,
    equipmentByStatus,
    equipmentConditions,
}: Props) {
    const selectedSchoolYear = schoolYears.find((year) => year.id === filters.school_year_id);

    return (
        <>
            <Head title="Reports" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h1 className="text-2xl font-bold text-foreground">Division Reports</h1>
                        <p className="text-sm text-muted-foreground">
                            Learning resource adequacy and equipment condition summaries across the division
                            {selectedSchoolYear ? ` for SY ${selectedSchoolYear.name}` : ''}.
                        </p>

                        <form method="get" action="/app/admin/reports" className="mt-4 flex flex-wrap gap-2">
                            <select
                                name="school_year_id"
                                defaultValue={filters.school_year_id ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">Active School Year</option>
                                {schoolYears.map((year) => (
                                    <option key={year.id} value={year.id}>
                                        SY {year.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="district_id"
                                defaultValue={filters.district_id ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">All Districts</option>
                                {districts.map((district) => (
                                    <option key={district.id} value={district.id}>
                                        {district.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="municipality_id"
                                defaultValue={filters.municipality_id ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">All Municipalities</option>
                                {municipalities.map((municipality) => (
                                    <option key={municipality.id} value={municipality.id}>
                                        {municipality.name}
                                    </option>
                                ))}
                            </select>
                            <button type="submit" className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                                Filter
                            </button>
                        </form>
                    </header>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 className="text-lg font-semibold text-foreground">Learning Resource Adequacy</h2>
                                <p className="text-sm text-muted-foreground">
                                    Copies available in school inventories compared against enrollment.
                                </p>
                            </div>
                            <a
                                href={exportUrl('/app/admin/reports/learning-resources/export', filters)}
                                className="h-9 rounded-md border border-border bg-background px-4 text-sm leading-9 text-foreground hover:bg-muted"
                            >
                                Export CSV
                            </a>
                        </div>

                        <div className="mb-4 flex flex-wrap gap-2 text-xs">
                            <span className="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2.5 py-1 text-muted-foreground">
                                <span className="font-semibold text-foreground">{resourceSummary.total_learners.toLocaleString()}</span>
                                learners
                            </span>
                            <span className="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2.5 py-1 text-muted-foreground">
                                <span className="font-semibold text-foreground">{resourceSummary.total_available.toLocaleString()}</span>
                                available copies
                            </span>
                            <span className="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2.5 py-1 text-muted-foreground">
                                <span className="font-semibold text-foreground">{resourceSummary.schools_in_shortage.toLocaleString()}</span>
                                schools in shortage
                            </span>
                        </div>

                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">School</th>
                                        <th className="px-3 py-2">District</th>
                                        <th className="px-3 py-2">Municipality</th>
                                        <th className="px-3 py-2 text-right">Learners</th>
                                        <th className="px-3 py-2 text-right">Available Copies</th>
                                        <th className="px-3 py-2 text-right">Copies / Learner</th>
                                        <th className="px-3 py-2 text-right">Shortage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {resourceAdequacy.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-center text-muted-foreground" colSpan={7}>
                                                No schools found for the selected filters.
                                            </td>
                                        </tr>
                                    )}
                                    {resourceAdequacy.map((row) => (
                                        <tr key={row.school_id} className="border-t border-border">
                                            <td className="px-3 py-2 font-medium text-foreground">{row.school_name}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{row.district ?? '-'}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{row.municipality ?? '-'}</td>
                                            <td className="px-3 py-2 text-right text-muted-foreground">{row.learners.toLocaleString()}</td>
                                            <td className="px-3 py-2 text-right text-muted-foreground">
                                                {row.available_copies.toLocaleString()}
                                            </td>
                                            <td className="px-3 py-2 text-right text-muted-foreground">
                                                {row.copies_per_learner ?? '-'}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                {row.shortage > 0 ? (
                                                    <span className="font-semibold text-destructive">{row.shortage.toLocaleString()}</span>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 className="text-lg font-semibold text-foreground">Equipment Condition Summary</h2>
                                <p className="text-sm text-muted-foreground">
                                    Registered equipment grouped by category and physical condition.
                                </p>
                            </div>
                            <a
                                href={exportUrl('/app/admin/reports/equipment/export', filters)}
                                className="h-9 rounded-md border border-border bg-background px-4 text-sm leading-9 text-foreground hover:bg-muted"
                            >
                                Export CSV
                            </a>
                        </div>

                        {Object.keys(equipmentByStatus).length > 0 && (
                            <div className="mb-4 flex flex-wrap gap-2 text-xs">
                                {Object.entries(equipmentByStatus).map(([status, total]) => (
                                    <span
                                        key={status}
                                        className="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2.5 py-1 text-muted-foreground"
                                    >
                                        <span className="font-semibold text-foreground">{total.toLocaleString()}</span>
                                        {status}
                                    </span>
                                ))}
                            </div>
                        )}

                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">Category</th>
                                        {equipmentConditions.map((condition) => (
                                            <th key={condition} className="px-3 py-2 text-right">
                                                {condition}
                                            </th>
                                        ))}
                                        <th className="px-3 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {equipmentByCategory.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-center text-muted-foreground" colSpan={equipmentConditions.length + 2}>
                                                No equipment found for the selected filters.
                                            </td>
                                        </tr>
                                    )}
                                    {equipmentByCategory.map((row) => (
                                        <tr key={row.category} className="border-t border-border">
                                            <td className="px-3 py-2 font-medium text-foreground">{row.category}</td>
                                            {equipmentConditions.map((condition) => (
                                                <td key={condition} className="px-3 py-2 text-right text-muted-foreground">
                                                    {(row.conditions[condition] ?? 0).toLocaleString()}
                                                </td>
                                            ))}
                                            <td className="px-3 py-2 text-right font-semibold text-foreground">
                                                {row.total.toLocaleString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
