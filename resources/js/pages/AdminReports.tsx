import { Head } from '@inertiajs/react';
import { ChartColumn } from 'lucide-react';
import { ChartCard } from '@/components/charts/chart-card';
import { EmptyChart } from '@/components/charts/empty-chart';
import { GroupedColumnChart } from '@/components/charts/grouped-column-chart';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';

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
    ictEquipmentByCategory: CategoryRow[];
    ictEquipmentByStatus: Record<string, number>;
    otherEquipmentByCategory: CategoryRow[];
    otherEquipmentByStatus: Record<string, number>;
    equipmentConditions: string[];
};

const CHART_SERIES_COLORS = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];

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
    ictEquipmentByCategory,
    ictEquipmentByStatus,
    otherEquipmentByCategory,
    otherEquipmentByStatus,
    equipmentConditions,
}: Props) {
    const selectedSchoolYear = schoolYears.find((year) => year.id === filters.school_year_id);

    return (
        <>
            <Head title="Reports" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={ChartColumn}
                        iconClassName="bg-orange-100 text-orange-600 dark:bg-orange-900/60 dark:text-orange-300"
                        title="Division Reports"
                        description={`Learning resource adequacy and equipment condition summaries across the division${selectedSchoolYear ? ` for SY ${selectedSchoolYear.name}` : ''}.`}
                        align="start"
                    >
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
                    </PageHeader>

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
                                        <EmptyTableRow colSpan={7} message="No schools found for the selected filters." />
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

                    <EquipmentConditionSummary
                        title="ICT Equipment Condition Summary"
                        description="Registered ICT equipment grouped by category and physical condition."
                        exportHref={exportUrl('/app/admin/reports/ict-equipment/export', filters)}
                        byStatus={ictEquipmentByStatus}
                        byCategory={ictEquipmentByCategory}
                        conditions={equipmentConditions}
                    />

                    <EquipmentConditionSummary
                        title="Other Equipment Condition Summary"
                        description="Registered TVL, ALS, Library, SPED, Sports, and other equipment grouped by category and physical condition."
                        exportHref={exportUrl('/app/admin/reports/other-equipment/export', filters)}
                        byStatus={otherEquipmentByStatus}
                        byCategory={otherEquipmentByCategory}
                        conditions={equipmentConditions}
                    />
                </div>
            </main>
        </>
    );
}

function EquipmentConditionSummary({
    title,
    description,
    exportHref,
    byStatus,
    byCategory,
    conditions,
}: {
    title: string;
    description: string;
    exportHref: string;
    byStatus: Record<string, number>;
    byCategory: CategoryRow[];
    conditions: string[];
}) {
    return (
        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 className="text-lg font-semibold text-foreground">{title}</h2>
                    <p className="text-sm text-muted-foreground">{description}</p>
                </div>
                <a
                    href={exportHref}
                    className="h-9 rounded-md border border-border bg-background px-4 text-sm leading-9 text-foreground hover:bg-muted"
                >
                    Export CSV
                </a>
            </div>

            {Object.keys(byStatus).length > 0 && (
                <div className="mb-4 flex flex-wrap gap-2 text-xs">
                    {Object.entries(byStatus).map(([status, total]) => (
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

            <ChartCard
                title="By category and condition"
                legend={conditions.map((condition, index) => ({
                    label: condition,
                    color: CHART_SERIES_COLORS[index % CHART_SERIES_COLORS.length],
                }))}
                table={{
                    headers: ['Category', ...conditions, 'Total'],
                    rows: byCategory.map((row) => [
                        row.category,
                        ...conditions.map((condition) => row.conditions[condition] ?? 0),
                        row.total,
                    ]),
                }}
            >
                {byCategory.length > 0 ? (
                    <GroupedColumnChart
                        data={byCategory.map((row) => ({
                            label: row.category,
                            values: conditions.map((condition) => row.conditions[condition] ?? 0),
                        }))}
                        series={conditions.map((condition, index) => ({
                            name: condition,
                            color: CHART_SERIES_COLORS[index % CHART_SERIES_COLORS.length],
                        }))}
                    />
                ) : (
                    <EmptyChart message="No equipment found for the selected filters." />
                )}
            </ChartCard>
        </section>
    );
}
