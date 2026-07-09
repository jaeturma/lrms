import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    BookOpen,
    Boxes,
    Building2,
    CalendarRange,
    ClipboardList,
    GraduationCap,
    LayoutGrid,
    MonitorPlay,
    Pencil,
    SlidersHorizontal,
    Trash2,
    Truck,
} from 'lucide-react';
import { ChartCard } from '@/components/charts/chart-card';
import { DivergingStackedBar } from '@/components/charts/diverging-stacked-bar';
import { EmptyChart } from '@/components/charts/empty-chart';
import { GroupedColumnChart } from '@/components/charts/grouped-column-chart';
import { HProgressBars } from '@/components/charts/h-progress-bars';
import { DataTable } from '@/components/data-table';
import { PageHeaderIcon } from '@/components/page-header-icon';
import { StatTile } from '@/components/stat-tile';
import { Button } from '@/components/ui/button';

type District = {
    id: number;
    name: string;
};

type SchoolRow = {
    school_id: string;
    school_name: string;
    district: string;
    municipality: string;
    barangay: string;
    is_activated: boolean;
    learning_resources_count: number;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    stats: {
        total_schools: number;
        activated_schools: number;
        pending_requests: number;
        total_learners: number;
        male_learners: number;
        female_learners: number;
        copies_delivered: number;
        copies_with_defects: number;
        defect_rate: number;
        total_equipment: number;
        equipment_needing_repair: number;
        digital_lms: number;
        digital_lms_quality_assured: number;
        pending_distributions: number;
        total_distributions: number;
    };
    enrollmentByGrade: Array<{ grade: string; male: number; female: number }>;
    activationByMunicipality: Array<{ municipality: string; activated: number; total: number }>;
    equipmentCondition: Array<{ type: string; good: number; fair: number; needs_attention: number }>;
    defectRateByMunicipality: Array<{ municipality: string; delivered: number; defective: number; rate: number }>;
    pendingActivations: Array<{ school_id: string; school_name: string; district: string | null; requested_at: string | null }>;
    activeSchoolYear: { id: number; name: string } | null;
    districts: District[];
    schoolTypes: string[];
    gradeLevels: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        district_id?: number | null;
        school_type?: string | null;
        grade_level_id?: number | null;
    };
    schools: Paginator<SchoolRow>;
};

export default function AdminDashboard({
    stats,
    enrollmentByGrade,
    activationByMunicipality,
    equipmentCondition,
    defectRateByMunicipality,
    pendingActivations,
    activeSchoolYear,
    districts,
    schoolTypes,
    gradeLevels,
    filters,
    schools,
}: Props) {
    const schoolColumns: ColumnDef<SchoolRow>[] = [
        { accessorKey: 'school_id', header: 'School ID' },
        { accessorKey: 'school_name', header: 'School Name' },
        { accessorKey: 'district', header: 'District' },
        {
            id: 'municipality_barangay',
            header: 'Municipality/Barangay',
            accessorFn: (row) => `${row.municipality ?? '-'} - ${row.barangay ?? '-'}`,
            cell: ({ row }) => `${row.original.municipality ?? '-'} - ${row.original.barangay ?? '-'}`,
        },
        {
            accessorKey: 'is_activated',
            header: 'Status',
            cell: ({ row }) => (
                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                    row.original.is_activated
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
                }`}>
                    {row.original.is_activated ? 'Activated' : 'Pending'}
                </span>
            ),
        },
        { accessorKey: 'learning_resources_count', header: 'Resources' },
        {
            id: 'actions',
            header: 'Actions',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/app/admin/schools/${row.original.school_id}/edit`}>
                            <Pencil className="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40"
                        onClick={() => deleteSchool(row.original.school_id)}
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            ),
        },
    ];

    const deleteSchool = (schoolId: string) => {
        if (!window.confirm('Delete this school record? This cannot be undone.')) {
            return;
        }

        router.delete(`/app/admin/schools/${schoolId}`, {
            preserveScroll: true,
        });
    };

    const activationRate = stats.total_schools > 0 ? Math.round((stats.activated_schools / stats.total_schools) * 100) : 0;

    const asOf = new Date().toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });

    const scopeParts = [
        filters.district_id ? districts.find((district) => district.id === filters.district_id)?.name : null,
        filters.school_type ?? null,
        filters.grade_level_id ? gradeLevels.find((grade) => grade.id === filters.grade_level_id)?.name : null,
    ].filter(Boolean);
    const scopeLabel = scopeParts.length > 0 ? scopeParts.join(' · ') : 'Entire Division';
    const hasScope = scopeParts.length > 0;

    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <header className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm">
                    <div className="flex items-center gap-3">
                        <PageHeaderIcon
                            icon={LayoutGrid}
                            className="bg-slate-950 text-slate-400 dark:bg-slate-900/60 dark:text-slate-300"
                        />
                        <div>
                            <h1 className="text-xl font-bold text-foreground">Division Dashboard</h1>
                            <p className="text-sm text-muted-foreground">
                                {scopeLabel} · learning resources, equipment, and school activation at a glance · as of {asOf}
                            </p>
                        </div>
                    </div>
                    {activeSchoolYear && (
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-3 py-1 text-xs font-medium text-foreground">
                            <CalendarRange className="size-3.5 text-muted-foreground" />
                            SY {activeSchoolYear.name}
                        </span>
                    )}
                </header>

                <section className="rounded-2xl border border-border bg-card p-3 shadow-sm">
                    <form method="get" action="/app/admin/dashboard" className="flex flex-wrap items-center gap-2">
                        <span className="mr-1 flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            <SlidersHorizontal className="size-3.5" />
                            Scope
                        </span>
                        <input type="hidden" name="search" value={filters.search ?? ''} />
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
                            name="school_type"
                            defaultValue={filters.school_type ?? ''}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                        >
                            <option value="">All School Levels</option>
                            {schoolTypes.map((type) => (
                                <option key={type} value={type}>
                                    {type}
                                </option>
                            ))}
                        </select>
                        <select
                            name="grade_level_id"
                            defaultValue={filters.grade_level_id ?? ''}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                        >
                            <option value="">All Grade Levels</option>
                            {gradeLevels.map((grade) => (
                                <option key={grade.id} value={grade.id}>
                                    {grade.name}
                                </option>
                            ))}
                        </select>
                        <button type="submit" className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                            Apply
                        </button>
                        {hasScope && (
                            <Link href="/app/admin/dashboard" className="text-sm text-primary underline underline-offset-4">
                                Entire Division
                            </Link>
                        )}
                    </form>
                </section>

                <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <StatTile
                        label="Schools"
                        value={stats.total_schools}
                        context={`${stats.activated_schools.toLocaleString()} activated (${activationRate}%)`}
                        icon={Building2}
                        colorClassName="bg-blue-950 text-blue-100"
                    />
                    <StatTile
                        label="Learners"
                        value={stats.total_learners}
                        context={
                            activeSchoolYear
                                ? `${stats.male_learners.toLocaleString()} male · ${stats.female_learners.toLocaleString()} female`
                                : 'No active school year'
                        }
                        icon={GraduationCap}
                        colorClassName="bg-violet-950 text-violet-100"
                    />
                    <StatTile
                        label="Printed copies"
                        value={stats.copies_delivered}
                        context={`${stats.defect_rate}% with defects`}
                        icon={BookOpen}
                        colorClassName="bg-indigo-950 text-indigo-100"
                    />
                    <StatTile
                        label="Equipment units"
                        value={stats.total_equipment}
                        context={`${stats.equipment_needing_repair.toLocaleString()} need repair`}
                        icon={Boxes}
                        colorClassName="bg-teal-950 text-teal-100"
                    />
                    <StatTile
                        label="Digital LMs"
                        value={stats.digital_lms}
                        context={`${stats.digital_lms_quality_assured.toLocaleString()} quality-assured`}
                        icon={MonitorPlay}
                        colorClassName="bg-cyan-950 text-cyan-100"
                    />
                    <StatTile
                        label="Pending deliveries"
                        value={stats.pending_distributions}
                        context={`of ${stats.total_distributions.toLocaleString()} distributions`}
                        icon={Truck}
                        colorClassName="bg-lime-950 text-lime-100"
                    />
                </section>

                <section className="grid gap-3 lg:grid-cols-2">
                    <ChartCard
                        title="Enrollment by Grade Level"
                        subtitle={activeSchoolYear ? `Learners encoded for SY ${activeSchoolYear.name}` : 'No active school year set'}
                        legend={[
                            { label: 'Male', color: 'var(--viz-series-1)' },
                            { label: 'Female', color: 'var(--viz-series-2)' },
                        ]}
                        table={{
                            headers: ['Grade level', 'Male', 'Female', 'Total'],
                            rows: enrollmentByGrade.map((row) => [row.grade, row.male, row.female, row.male + row.female]),
                        }}
                    >
                        {enrollmentByGrade.length > 0 ? (
                            <GroupedColumnChart
                                data={enrollmentByGrade.map((row) => ({ label: row.grade, values: [row.male, row.female] }))}
                                series={[
                                    { name: 'Male', color: 'var(--viz-series-1)' },
                                    { name: 'Female', color: 'var(--viz-series-2)' },
                                ]}
                            />
                        ) : (
                            <EmptyChart message="No enrollment encoded for the active school year yet." />
                        )}
                    </ChartCard>

                    <ChartCard
                        title="School Activation by Municipality"
                        subtitle="Activated school accounts against total schools on record"
                        table={{
                            headers: ['Municipality', 'Activated', 'Total', 'Rate'],
                            rows: activationByMunicipality.map((row) => [
                                row.municipality,
                                row.activated,
                                row.total,
                                `${row.total > 0 ? Math.round((row.activated / row.total) * 100) : 0}%`,
                            ]),
                        }}
                    >
                        {activationByMunicipality.length > 0 ? (
                            <HProgressBars
                                rows={activationByMunicipality.map((row) => ({
                                    label: row.municipality,
                                    value: row.activated,
                                    max: row.total,
                                    valueText: `${row.activated.toLocaleString()}/${row.total.toLocaleString()}`,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No municipalities on record yet." />
                        )}
                    </ChartCard>

                    <ChartCard
                        title="Equipment Condition"
                        subtitle="Serviceable units to the right, units needing repair to the left"
                        legend={[
                            { label: 'Needs attention', color: 'var(--viz-bad)' },
                            { label: 'Fair', color: 'var(--viz-neutral)' },
                            { label: 'Good', color: 'var(--viz-series-1)' },
                        ]}
                        table={{
                            headers: ['Equipment', 'Good', 'Fair', 'Needs attention', 'Total'],
                            rows: equipmentCondition.map((row) => [
                                row.type,
                                row.good,
                                row.fair,
                                row.needs_attention,
                                row.good + row.fair + row.needs_attention,
                            ]),
                        }}
                    >
                        {equipmentCondition.some((row) => row.good + row.fair + row.needs_attention > 0) ? (
                            <DivergingStackedBar
                                rows={equipmentCondition.map((row) => ({
                                    label: row.type,
                                    negative: row.needs_attention,
                                    neutral: row.fair,
                                    positive: row.good,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No equipment registered by schools yet." />
                        )}
                    </ChartCard>

                    <ChartCard
                        title="Learning Resource Defect Rate"
                        subtitle="Share of delivered copies reported with issues or defects, by municipality"
                        table={{
                            headers: ['Municipality', 'Delivered', 'Defective', 'Rate'],
                            rows: defectRateByMunicipality.map((row) => [row.municipality, row.delivered, row.defective, `${row.rate}%`]),
                        }}
                    >
                        {defectRateByMunicipality.length > 0 ? (
                            <HProgressBars
                                rows={defectRateByMunicipality.map((row) => ({
                                    label: row.municipality,
                                    value: row.rate,
                                    max: Math.max(...defectRateByMunicipality.map((r) => r.rate), 1),
                                    valueText: `${row.rate}%`,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No learning resource deliveries encoded yet." />
                        )}
                    </ChartCard>
                </section>

                {pendingActivations.length > 0 && (
                    <section className="rounded-2xl border border-amber-300/70 bg-amber-50/60 p-4 shadow-sm dark:border-amber-800 dark:bg-amber-950/30">
                        <div className="mb-2 flex items-center gap-2">
                            <ClipboardList className="size-4 text-amber-700 dark:text-amber-300" />
                            <h2 className="text-base font-semibold text-foreground">Pending Activation Requests</h2>
                        </div>
                        <ul className="divide-y divide-amber-200/70 dark:divide-amber-900/50">
                            {pendingActivations.map((request) => (
                                <li key={request.school_id} className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                    <div>
                                        <p className="font-medium text-foreground">{request.school_name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {request.district ?? 'No district'} · requested{' '}
                                            {request.requested_at ? new Date(request.requested_at).toLocaleDateString() : '-'}
                                        </p>
                                    </div>
                                    <Link
                                        href={`/app/admin/schools/${request.school_id}`}
                                        className="text-xs font-semibold text-amber-800 underline-offset-2 hover:underline dark:text-amber-300"
                                    >
                                        Review →
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="rounded-2xl border border-border bg-card p-4 shadow-sm">
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-foreground">School Directory</h2>
                        <form method="get" action="/app/admin/dashboard" className="flex flex-wrap gap-2">
                            <input type="hidden" name="district_id" value={filters.district_id ?? ''} />
                            <input type="hidden" name="school_type" value={filters.school_type ?? ''} />
                            <input type="hidden" name="grade_level_id" value={filters.grade_level_id ?? ''} />
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search school name or ID"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            />
                            <button type="submit" className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                                Search
                            </button>
                        </form>
                    </div>

                    <DataTable
                        columns={schoolColumns}
                        data={schools.data}
                        searchPlaceholder="Search in results…"
                        searchColumn="school_name"
                    />

                    {schools.links.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-2 text-sm">
                            {schools.links.map((link, index) => (
                                <span key={index}>
                                    {link.url ? (
                                        <Link
                                            href={link.url}
                                            className={`rounded border px-3 py-1 ${link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-foreground'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span
                                            className="rounded border border-border bg-card px-3 py-1 text-muted-foreground"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

