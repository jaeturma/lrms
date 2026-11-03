import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import {
    BookOpen,
    Boxes,
    Building2,
    CalendarRange,
    ClipboardCheck,
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
import { Heatmap } from '@/components/charts/heatmap';
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

type LrMonitoring = {
    schools: number;
    schools_monitored: number;
    schools_completed: number;
    not_started: number;
    stages: Array<{ stage: string; label: string; count: number }>;
    districts: Array<{
        district: string;
        schools: number;
        monitored: number;
        completed: number;
    }>;
    items_validated: number;
    items_with_discrepancy: number;
    ongoing: Array<{
        id: number;
        school_name: string;
        beis_school_id: string;
        district: string | null;
        team_leader: string | null;
        items_total: number;
        validated: number;
        updated_at: string | null;
    }>;
};

const lrStageTone: Record<string, string> = {
    awaiting_school:
        'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-200',
    ready: 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200',
    validating:
        'bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-200',
    completed:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200',
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
    activationByMunicipality: Array<{
        municipality: string;
        activated: number;
        total: number;
    }>;
    equipmentCondition: Array<{
        type: string;
        good: number;
        fair: number;
        needs_attention: number;
    }>;
    defectRateByMunicipality: Array<{
        municipality: string;
        delivered: number;
        defective: number;
        rate: number;
    }>;
    pendingActivations: Array<{
        school_id: string;
        school_name: string;
        district: string | null;
        requested_at: string | null;
    }>;
    learnerEquipmentRatio: Array<{
        municipality: string;
        learners: number;
        equipment: number;
        ratio: number | null;
    }>;
    smeLabAdequacy: Array<{
        municipality: string;
        adequate: number;
        total: number;
    }>;
    equipmentFunctionalStatus: Array<{
        type: string;
        functional: number;
        non_functional: number;
    }>;
    districtResourceHeatmap: {
        districts: string[];
        resourceTypes: string[];
        values: number[][];
    };
    lrMonitoring: LrMonitoring;
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
    learnerEquipmentRatio,
    smeLabAdequacy,
    equipmentFunctionalStatus,
    districtResourceHeatmap,
    lrMonitoring,
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
            accessorFn: (row) =>
                `${row.municipality ?? '-'} - ${row.barangay ?? '-'}`,
            cell: ({ row }) =>
                `${row.original.municipality ?? '-'} - ${row.original.barangay ?? '-'}`,
        },
        {
            accessorKey: 'is_activated',
            header: 'Status',
            cell: ({ row }) => (
                <span
                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                        row.original.is_activated
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
                    }`}
                >
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
                        <Link
                            href={`/app/admin/schools/${row.original.school_id}/edit`}
                        >
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
        if (
            !window.confirm('Delete this school record? This cannot be undone.')
        ) {
            return;
        }

        router.delete(`/app/admin/schools/${schoolId}`, {
            preserveScroll: true,
        });
    };

    const activationRate =
        stats.total_schools > 0
            ? Math.round((stats.activated_schools / stats.total_schools) * 100)
            : 0;

    const asOf = new Date().toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    const scopeParts = [
        filters.district_id
            ? districts.find((district) => district.id === filters.district_id)
                  ?.name
            : null,
        filters.school_type ?? null,
        filters.grade_level_id
            ? gradeLevels.find((grade) => grade.id === filters.grade_level_id)
                  ?.name
            : null,
    ].filter(Boolean);
    const scopeLabel =
        scopeParts.length > 0 ? scopeParts.join(' · ') : 'Entire Division';
    const hasScope = scopeParts.length > 0;

    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <header className="flex flex-wrap items-center justify-between gap-3 px-1">
                    <div className="flex items-center gap-3">
                        <PageHeaderIcon
                            icon={LayoutGrid}
                            className="bg-slate-100 text-slate-600 dark:bg-slate-900/60 dark:text-slate-300"
                        />
                        <div>
                            <h1 className="text-xl font-bold text-foreground">
                                iLRMS Dashboard
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {scopeLabel} · learning resources, equipment,
                                and school activation at a glance · as of {asOf}
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

                <section className="px-1">
                    <form
                        method="get"
                        action="/app/admin/dashboard"
                        className="flex flex-wrap items-center gap-2"
                    >
                        <span className="mr-1 flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            <SlidersHorizontal className="size-3.5" />
                            Scope
                        </span>
                        <input
                            type="hidden"
                            name="search"
                            value={filters.search ?? ''}
                        />
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
                        <button
                            type="submit"
                            className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground"
                        >
                            Apply
                        </button>
                        {hasScope && (
                            <Link
                                href="/app/admin/dashboard"
                                className="text-sm text-primary underline underline-offset-4"
                            >
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
                        colorClassName="bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-200"
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
                        colorClassName="bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-200"
                    />
                    <StatTile
                        label="Printed copies"
                        value={stats.copies_delivered}
                        context={`${stats.defect_rate}% with defects`}
                        icon={BookOpen}
                        colorClassName="bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-200"
                    />
                    <StatTile
                        label="Equipment units"
                        value={stats.total_equipment}
                        context={`${stats.equipment_needing_repair.toLocaleString()} need repair`}
                        icon={Boxes}
                        colorClassName="bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-200"
                    />
                    <StatTile
                        label="Digital LMs"
                        value={stats.digital_lms}
                        context={`${stats.digital_lms_quality_assured.toLocaleString()} quality-assured`}
                        icon={MonitorPlay}
                        colorClassName="bg-cyan-100 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-200"
                    />
                    <StatTile
                        label="Pending deliveries"
                        value={stats.pending_distributions}
                        context={`of ${stats.total_distributions.toLocaleString()} distributions`}
                        icon={Truck}
                        colorClassName="bg-lime-100 text-lime-700 dark:bg-lime-950/60 dark:text-lime-200"
                    />
                </section>

                <section className="space-y-3">
                    <div className="flex flex-wrap items-end justify-between gap-2 px-1">
                        <div>
                            <h2 className="flex items-center gap-2 text-base font-semibold text-foreground">
                                <ClipboardCheck className="size-4 text-teal-600" />
                                LR Monitoring
                            </h2>
                            <p className="text-xs text-muted-foreground">
                                Division-wide validation of learning resources ·{' '}
                                {lrMonitoring.schools_completed.toLocaleString()}{' '}
                                of {lrMonitoring.schools.toLocaleString()}{' '}
                                schools completed
                            </p>
                        </div>
                        <div className="flex gap-3 text-sm">
                            <Link
                                href="/app/admin/valid-lr/board"
                                className="text-primary underline underline-offset-4"
                            >
                                Live board
                            </Link>
                            <Link
                                href="/app/admin/valid-lr/report"
                                className="text-primary underline underline-offset-4"
                            >
                                Report
                            </Link>
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <article className="rounded-xl bg-muted p-3 text-foreground shadow-sm">
                            <p className="text-xs font-medium tracking-wide uppercase opacity-80">
                                Not started
                            </p>
                            <p className="mt-1 text-2xl font-bold">
                                {lrMonitoring.not_started.toLocaleString()}
                            </p>
                            <p className="mt-0.5 text-xs opacity-75">
                                Schools without a visit yet
                            </p>
                        </article>
                        {lrMonitoring.stages.map((stage) => (
                            <article
                                key={stage.stage}
                                className={`rounded-xl p-3 shadow-sm ${lrStageTone[stage.stage] ?? 'bg-muted text-foreground'}`}
                            >
                                <p className="text-xs font-medium tracking-wide uppercase opacity-80">
                                    {stage.label}
                                </p>
                                <p className="mt-1 text-2xl font-bold">
                                    {stage.count.toLocaleString()}
                                </p>
                                <p className="mt-0.5 text-xs opacity-75">
                                    {stage.count === 1 ? 'visit' : 'visits'}
                                </p>
                            </article>
                        ))}
                    </div>

                    <div className="grid gap-3 lg:grid-cols-2">
                        <ChartCard
                            title="Validation Coverage by District"
                            subtitle="Schools with a completed validation against schools in the monitoring list"
                            table={{
                                headers: [
                                    'District',
                                    'Schools',
                                    'Monitored',
                                    'Completed',
                                ],
                                rows: lrMonitoring.districts.map((row) => [
                                    row.district,
                                    row.schools,
                                    row.monitored,
                                    row.completed,
                                ]),
                            }}
                        >
                            {lrMonitoring.districts.length > 0 ? (
                                <div className="max-h-72 overflow-y-auto pr-1">
                                    <HProgressBars
                                        rows={lrMonitoring.districts.map(
                                            (row) => ({
                                                label: row.district,
                                                value: row.completed,
                                                max: row.schools,
                                                valueText: `${row.completed.toLocaleString()}/${row.schools.toLocaleString()}`,
                                            }),
                                        )}
                                    />
                                </div>
                            ) : (
                                <EmptyChart message="No schools imported into LR Monitoring yet." />
                            )}
                        </ChartCard>

                        <ChartCard
                            title="Validation Ongoing"
                            subtitle={`${lrMonitoring.items_validated.toLocaleString()} items counted · ${lrMonitoring.items_with_discrepancy.toLocaleString()} differ from LRMIS or the package quantity`}
                        >
                            {lrMonitoring.ongoing.length > 0 ? (
                                <ul className="divide-y divide-border">
                                    {lrMonitoring.ongoing.map((visit) => (
                                        <li key={visit.id} className="py-2">
                                            <Link
                                                href={`/app/admin/valid-lr/visits/${visit.id}/edit`}
                                                className="flex items-center justify-between gap-3 hover:underline"
                                            >
                                                <span className="min-w-0">
                                                    <span className="block truncate text-sm font-medium text-foreground">
                                                        {visit.school_name}
                                                    </span>
                                                    <span className="block truncate text-xs text-muted-foreground">
                                                        {visit.beis_school_id}
                                                        {visit.district
                                                            ? ` · ${visit.district}`
                                                            : ''}
                                                        {visit.team_leader
                                                            ? ` · Leader: ${visit.team_leader}`
                                                            : ''}
                                                    </span>
                                                </span>
                                                <span className="shrink-0 text-right text-xs text-muted-foreground tabular-nums">
                                                    {visit.validated}/
                                                    {visit.items_total} items
                                                    <span className="block">
                                                        {visit.updated_at}
                                                    </span>
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyChart message="No validation is in progress right now." />
                            )}
                        </ChartCard>
                    </div>
                </section>

                <section className="grid gap-3 lg:grid-cols-2">
                    <ChartCard
                        title="Enrollment by Grade Level"
                        subtitle={
                            activeSchoolYear
                                ? `Learners encoded for SY ${activeSchoolYear.name}`
                                : 'No active school year set'
                        }
                        legend={[
                            { label: 'Male', color: 'var(--viz-series-1)' },
                            { label: 'Female', color: 'var(--viz-series-2)' },
                        ]}
                        table={{
                            headers: ['Grade level', 'Male', 'Female', 'Total'],
                            rows: enrollmentByGrade.map((row) => [
                                row.grade,
                                row.male,
                                row.female,
                                row.male + row.female,
                            ]),
                        }}
                    >
                        {enrollmentByGrade.length > 0 ? (
                            <GroupedColumnChart
                                data={enrollmentByGrade.map((row) => ({
                                    label: row.grade,
                                    values: [row.male, row.female],
                                }))}
                                series={[
                                    {
                                        name: 'Male',
                                        color: 'var(--viz-series-1)',
                                    },
                                    {
                                        name: 'Female',
                                        color: 'var(--viz-series-2)',
                                    },
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
                            headers: [
                                'Municipality',
                                'Activated',
                                'Total',
                                'Rate',
                            ],
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
                            {
                                label: 'Needs attention',
                                color: 'var(--viz-bad)',
                            },
                            { label: 'Fair', color: 'var(--viz-neutral)' },
                            { label: 'Good', color: 'var(--viz-series-1)' },
                        ]}
                        table={{
                            headers: [
                                'Equipment',
                                'Good',
                                'Fair',
                                'Needs attention',
                                'Total',
                            ],
                            rows: equipmentCondition.map((row) => [
                                row.type,
                                row.good,
                                row.fair,
                                row.needs_attention,
                                row.good + row.fair + row.needs_attention,
                            ]),
                        }}
                    >
                        {equipmentCondition.some(
                            (row) =>
                                row.good + row.fair + row.needs_attention > 0,
                        ) ? (
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
                            headers: [
                                'Municipality',
                                'Delivered',
                                'Defective',
                                'Rate',
                            ],
                            rows: defectRateByMunicipality.map((row) => [
                                row.municipality,
                                row.delivered,
                                row.defective,
                                `${row.rate}%`,
                            ]),
                        }}
                    >
                        {defectRateByMunicipality.length > 0 ? (
                            <HProgressBars
                                rows={defectRateByMunicipality.map((row) => ({
                                    label: row.municipality,
                                    value: row.rate,
                                    max: Math.max(
                                        ...defectRateByMunicipality.map(
                                            (r) => r.rate,
                                        ),
                                        1,
                                    ),
                                    valueText: `${row.rate}%`,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No learning resource deliveries encoded yet." />
                        )}
                    </ChartCard>
                </section>

                <section className="grid gap-3 lg:grid-cols-2">
                    <ChartCard
                        title="Learner-to-Equipment Ratio"
                        subtitle="Combined ICT, Science & Math, and Other Equipment units against enrolled learners, by municipality"
                        table={{
                            headers: [
                                'Municipality',
                                'Learners',
                                'Equipment Units',
                                'Ratio',
                            ],
                            rows: learnerEquipmentRatio.map((row) => [
                                row.municipality,
                                row.learners,
                                row.equipment,
                                row.ratio !== null ? `1 : ${row.ratio}` : '-',
                            ]),
                        }}
                    >
                        {learnerEquipmentRatio.length > 0 ? (
                            <HProgressBars
                                rows={learnerEquipmentRatio.map((row) => ({
                                    label: row.municipality,
                                    value: row.equipment,
                                    max: row.learners,
                                    valueText:
                                        row.ratio !== null
                                            ? `${row.equipment.toLocaleString()} · 1:${row.ratio}`
                                            : `${row.equipment.toLocaleString()}`,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No equipment or enrollment on record yet." />
                        )}
                    </ChartCard>

                    <ChartCard
                        title="Science & Math Laboratory Adequacy"
                        subtitle="Schools with at least one functional Science AND Mathematics equipment item, by municipality"
                        table={{
                            headers: [
                                'Municipality',
                                'Adequate Schools',
                                'Total Schools',
                                'Rate',
                            ],
                            rows: smeLabAdequacy.map((row) => [
                                row.municipality,
                                row.adequate,
                                row.total,
                                `${row.total > 0 ? Math.round((row.adequate / row.total) * 100) : 0}%`,
                            ]),
                        }}
                    >
                        {smeLabAdequacy.length > 0 ? (
                            <HProgressBars
                                rows={smeLabAdequacy.map((row) => ({
                                    label: row.municipality,
                                    value: row.adequate,
                                    max: row.total,
                                    valueText: `${row.adequate.toLocaleString()}/${row.total.toLocaleString()}`,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No schools on record yet." />
                        )}
                    </ChartCard>

                    <ChartCard
                        title="Equipment Functional Status"
                        subtitle="Non-functional (missing/lost/condemned/disposed) to the left, functional (available/in-use/borrowed) to the right"
                        legend={[
                            {
                                label: 'Non-functional',
                                color: 'var(--viz-bad)',
                            },
                            {
                                label: 'Functional',
                                color: 'var(--viz-series-1)',
                            },
                        ]}
                        table={{
                            headers: [
                                'Equipment',
                                'Functional',
                                'Non-functional',
                                'Total',
                            ],
                            rows: equipmentFunctionalStatus.map((row) => [
                                row.type,
                                row.functional,
                                row.non_functional,
                                row.functional + row.non_functional,
                            ]),
                        }}
                    >
                        {equipmentFunctionalStatus.some(
                            (row) => row.functional + row.non_functional > 0,
                        ) ? (
                            <DivergingStackedBar
                                rows={equipmentFunctionalStatus.map((row) => ({
                                    label: row.type,
                                    negative: row.non_functional,
                                    neutral: 0,
                                    positive: row.functional,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No equipment registered by schools yet." />
                        )}
                    </ChartCard>

                    <ChartCard
                        title="District-by-District Distribution Heatmap"
                        subtitle="Printed resource copies delivered and equipment units on record, by district"
                        table={{
                            headers: [
                                'District',
                                ...districtResourceHeatmap.resourceTypes,
                            ],
                            rows: districtResourceHeatmap.districts.map(
                                (district, index) => [
                                    district,
                                    ...(districtResourceHeatmap.values[index] ??
                                        []),
                                ],
                            ),
                        }}
                    >
                        {districtResourceHeatmap.districts.length > 0 ? (
                            <Heatmap
                                rowLabels={districtResourceHeatmap.districts}
                                columnLabels={
                                    districtResourceHeatmap.resourceTypes
                                }
                                values={districtResourceHeatmap.values}
                            />
                        ) : (
                            <EmptyChart message="No districts on record yet." />
                        )}
                    </ChartCard>
                </section>

                {pendingActivations.length > 0 && (
                    <section className="rounded-2xl border border-amber-300/70 bg-amber-50/60 p-4 shadow-sm dark:border-amber-800 dark:bg-amber-950/30">
                        <div className="mb-2 flex items-center gap-2">
                            <ClipboardList className="size-4 text-amber-700 dark:text-amber-300" />
                            <h2 className="text-base font-semibold text-foreground">
                                Pending Activation Requests
                            </h2>
                        </div>
                        <ul className="divide-y divide-amber-200/70 dark:divide-amber-900/50">
                            {pendingActivations.map((request) => (
                                <li
                                    key={request.school_id}
                                    className="flex flex-wrap items-center justify-between gap-2 py-2 text-sm"
                                >
                                    <div>
                                        <p className="font-medium text-foreground">
                                            {request.school_name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {request.district ?? 'No district'}{' '}
                                            · requested{' '}
                                            {request.requested_at
                                                ? new Date(
                                                      request.requested_at,
                                                  ).toLocaleDateString()
                                                : '-'}
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
                        <h2 className="text-lg font-semibold text-foreground">
                            School Directory
                        </h2>
                        <form
                            method="get"
                            action="/app/admin/dashboard"
                            className="flex flex-wrap gap-2"
                        >
                            <input
                                type="hidden"
                                name="district_id"
                                value={filters.district_id ?? ''}
                            />
                            <input
                                type="hidden"
                                name="school_type"
                                value={filters.school_type ?? ''}
                            />
                            <input
                                type="hidden"
                                name="grade_level_id"
                                value={filters.grade_level_id ?? ''}
                            />
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search school name or ID"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            />
                            <button
                                type="submit"
                                className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground"
                            >
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
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ) : (
                                        <span
                                            className="rounded border border-border bg-card px-3 py-1 text-muted-foreground"
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
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
