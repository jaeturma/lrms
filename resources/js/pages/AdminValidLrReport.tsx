import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, FileBarChart, Printer } from 'lucide-react';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';

export type ValidLrReportFilters = {
    status: string | null;
    district: string | null;
    from: string | null;
    to: string | null;
};

export type ValidLrReportVisit = {
    id: number;
    beis_school_id: string;
    school_name: string;
    district: string | null;
    visited_at: string | null;
    status: 'draft' | 'completed';
    monitoring_team: string | null;
    monitor: string | null;
    team_leader: string | null;
    personnel: Record<string, string | null>;
    lr_delivery_status: string | null;
    sme_delivery_status: string | null;
    tvl_delivery_status: string | null;
    indicators: Record<string, 'yes' | 'no' | null>;
    items_total: number;
    items_counted: number;
    discrepancies: number;
    key_findings: string | null;
};

export type ValidLrReport = {
    summary: {
        schools: number;
        schools_monitored: number;
        visits: number;
        completed: number;
        drafts: number;
        coverage: number;
    };
    districts: Array<{
        district: string;
        schools: number;
        monitored: number;
        completed: number;
        coverage: number;
    }>;
    indicators: Array<{
        key: string;
        question: string;
        yes: number;
        no: number;
        unanswered: number;
    }>;
    delivery: Array<{
        area: string;
        complete: number;
        incomplete: number;
        unrecorded: number;
    }>;
    visits: ValidLrReportVisit[];
};

type Props = {
    filters: ValidLrReportFilters;
    report: ValidLrReport;
    districts: string[];
    division: string | null;
    indicatorQuestions: Record<string, string>;
    personnelLabels: Record<string, string>;
};

const selectClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground';

const reportQuery = (filters: ValidLrReportFilters) => {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    const query = params.toString();

    return query ? `?${query}` : '';
};

export default function AdminValidLrReport({
    filters,
    report,
    districts,
    division,
    personnelLabels,
}: Props) {
    const query = reportQuery(filters);
    const summaryTiles: Array<[string, string]> = [
        ['Schools', report.summary.schools.toLocaleString()],
        [
            'Schools monitored',
            report.summary.schools_monitored.toLocaleString(),
        ],
        ['Coverage', `${report.summary.coverage}%`],
        ['Visits', report.summary.visits.toLocaleString()],
        ['Completed', report.summary.completed.toLocaleString()],
        ['Drafts', report.summary.drafts.toLocaleString()],
    ];

    return (
        <>
            <Head title="LR Monitoring Report" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={FileBarChart}
                        iconClassName="bg-teal-100 text-teal-600 dark:bg-teal-900/60 dark:text-teal-300"
                        title="LR Monitoring Report"
                        description={`Coverage, indicator answers, delivery status and count discrepancies from monitoring visits${division ? ` in ${division}` : ''}.`}
                        actions={
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/app/admin/valid-lr">
                                        <ArrowLeft className="mr-1 h-4 w-4" />
                                        Monitoring
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <a
                                        href={`/app/admin/valid-lr/report/export${query}`}
                                    >
                                        <Download className="mr-1 h-4 w-4" />
                                        CSV
                                    </a>
                                </Button>
                                <Button asChild>
                                    <a
                                        href={`/app/admin/valid-lr/report/print${query}`}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <Printer className="mr-1 h-4 w-4" />
                                        Print
                                    </a>
                                </Button>
                            </>
                        }
                    >
                        <form
                            method="get"
                            action="/app/admin/valid-lr/report"
                            className="mt-4 flex flex-wrap items-end gap-2"
                        >
                            <select
                                name="status"
                                defaultValue={filters.status ?? ''}
                                className={selectClass}
                                aria-label="Visit status"
                            >
                                <option value="">All visits</option>
                                <option value="completed">Completed</option>
                                <option value="draft">Drafts</option>
                            </select>
                            <select
                                name="district"
                                defaultValue={filters.district ?? ''}
                                className={selectClass}
                                aria-label="District"
                            >
                                <option value="">All Districts</option>
                                {districts.map((district) => (
                                    <option key={district} value={district}>
                                        {district}
                                    </option>
                                ))}
                            </select>
                            <label className="flex items-center gap-1 text-sm text-muted-foreground">
                                From
                                <input
                                    type="date"
                                    name="from"
                                    defaultValue={filters.from ?? ''}
                                    className={selectClass}
                                />
                            </label>
                            <label className="flex items-center gap-1 text-sm text-muted-foreground">
                                To
                                <input
                                    type="date"
                                    name="to"
                                    defaultValue={filters.to ?? ''}
                                    className={selectClass}
                                />
                            </label>
                            <button
                                type="submit"
                                className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground"
                            >
                                Filter
                            </button>
                        </form>
                    </PageHeader>

                    <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        {summaryTiles.map(([label, value]) => (
                            <div
                                key={label}
                                className="rounded-2xl border border-border bg-card px-4 py-3 shadow-sm"
                            >
                                <dt className="text-xs text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="text-2xl font-semibold text-foreground tabular-nums">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>

                    <div className="grid gap-4 xl:grid-cols-2">
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="mb-3 text-lg font-semibold text-foreground">
                                Monitoring Indicators
                            </h2>
                            <div className="overflow-x-auto rounded-xl border border-border">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="px-3 py-2">
                                                Indicator
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Yes
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                No
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Unanswered
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.indicators.map((indicator) => (
                                            <tr
                                                key={indicator.key}
                                                className="border-t border-border"
                                            >
                                                <td className="px-3 py-2 text-foreground">
                                                    {indicator.question}
                                                </td>
                                                <td className="px-3 py-2 text-right text-emerald-600 tabular-nums dark:text-emerald-400">
                                                    {indicator.yes}
                                                </td>
                                                <td className="px-3 py-2 text-right text-red-600 tabular-nums dark:text-red-400">
                                                    {indicator.no}
                                                </td>
                                                <td className="px-3 py-2 text-right text-muted-foreground tabular-nums">
                                                    {indicator.unanswered}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <h2 className="mt-5 mb-3 text-lg font-semibold text-foreground">
                                Delivery Status
                            </h2>
                            <div className="overflow-x-auto rounded-xl border border-border">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="px-3 py-2">Area</th>
                                            <th className="px-3 py-2 text-right">
                                                Complete
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Incomplete
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Not recorded
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.delivery.map((row) => (
                                            <tr
                                                key={row.area}
                                                className="border-t border-border"
                                            >
                                                <td className="px-3 py-2 text-foreground">
                                                    {row.area}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {row.complete}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {row.incomplete}
                                                </td>
                                                <td className="px-3 py-2 text-right text-muted-foreground tabular-nums">
                                                    {row.unrecorded}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="mb-3 text-lg font-semibold text-foreground">
                                Coverage by District
                            </h2>
                            <div className="max-h-[28rem] overflow-auto rounded-xl border border-border">
                                <table className="min-w-full text-sm">
                                    <thead className="sticky top-0 bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="px-3 py-2">
                                                District
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Schools
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Monitored
                                            </th>
                                            <th className="px-3 py-2 text-right">
                                                Completed
                                            </th>
                                            <th className="px-3 py-2">
                                                Coverage
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.districts.length === 0 && (
                                            <EmptyTableRow
                                                colSpan={5}
                                                message="No schools loaded yet."
                                            />
                                        )}
                                        {report.districts.map((row) => (
                                            <tr
                                                key={row.district}
                                                className="border-t border-border"
                                            >
                                                <td className="px-3 py-2 text-foreground">
                                                    {row.district}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {row.schools}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {row.monitored}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {row.completed}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-2">
                                                        <div className="h-2 w-20 overflow-hidden rounded-full bg-muted">
                                                            <div
                                                                className="h-full rounded-full bg-teal-500"
                                                                style={{
                                                                    width: `${row.coverage}%`,
                                                                }}
                                                            />
                                                        </div>
                                                        <span className="text-xs tabular-nums">
                                                            {row.coverage}%
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold text-foreground">
                            Monitoring Visits ({report.visits.length})
                        </h2>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">School</th>
                                        <th className="px-3 py-2">District</th>
                                        <th className="px-3 py-2">Visited</th>
                                        <th className="px-3 py-2">Team</th>
                                        <th className="px-3 py-2">
                                            LR / SME / TVL
                                        </th>
                                        <th className="px-3 py-2 text-right">
                                            Counted
                                        </th>
                                        <th className="px-3 py-2 text-right">
                                            Discrepancies
                                        </th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {report.visits.length === 0 && (
                                        <EmptyTableRow
                                            colSpan={9}
                                            message="No monitoring visits match these filters."
                                        />
                                    )}
                                    {report.visits.map((visit) => (
                                        <tr
                                            key={visit.id}
                                            className="border-t border-border"
                                        >
                                            <td className="px-3 py-2">
                                                <Link
                                                    href={`/app/admin/valid-lr/visits/${visit.id}/edit`}
                                                    className="font-medium text-primary underline-offset-2 hover:underline"
                                                >
                                                    {visit.school_name}
                                                </Link>
                                                <div className="text-xs text-muted-foreground tabular-nums">
                                                    {visit.beis_school_id}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {visit.district ?? '-'}
                                            </td>
                                            <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                                {visit.visited_at ?? '-'}
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {visit.monitoring_team ??
                                                    visit.monitor ??
                                                    '-'}
                                            </td>
                                            <td className="px-3 py-2 text-xs text-muted-foreground">
                                                {[
                                                    visit.lr_delivery_status,
                                                    visit.sme_delivery_status,
                                                    visit.tvl_delivery_status,
                                                ]
                                                    .map(
                                                        (status) =>
                                                            status ?? '—',
                                                    )
                                                    .join(' / ')}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {visit.items_counted} /{' '}
                                                {visit.items_total}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {visit.discrepancies > 0 ? (
                                                    <span className="font-semibold text-amber-600 dark:text-amber-400">
                                                        {visit.discrepancies}
                                                    </span>
                                                ) : (
                                                    0
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <StatusBadge
                                                    tone={
                                                        visit.status ===
                                                        'completed'
                                                            ? 'success'
                                                            : 'warning'
                                                    }
                                                >
                                                    {visit.status}
                                                </StatusBadge>
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                <a
                                                    href={`/app/admin/valid-lr/visits/${visit.id}/print`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                                >
                                                    <Printer className="h-3.5 w-3.5" />
                                                    Print
                                                </a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold text-foreground">
                            School Personnel of Monitored Schools
                        </h2>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">School</th>
                                        <th className="px-3 py-2">
                                            Team Leader
                                        </th>
                                        {Object.values(personnelLabels).map(
                                            (label) => (
                                                <th
                                                    key={label}
                                                    className="px-3 py-2"
                                                >
                                                    {label}
                                                </th>
                                            ),
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {report.visits.length === 0 && (
                                        <EmptyTableRow
                                            colSpan={
                                                Object.keys(personnelLabels)
                                                    .length + 2
                                            }
                                            message="No monitoring visits match these filters."
                                        />
                                    )}
                                    {report.visits.map((visit) => (
                                        <tr
                                            key={visit.id}
                                            className="border-t border-border"
                                        >
                                            <td className="px-3 py-2 text-foreground">
                                                {visit.school_name}
                                                <div className="text-xs text-muted-foreground">
                                                    {visit.beis_school_id}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {visit.team_leader ?? '-'}
                                            </td>
                                            {Object.keys(personnelLabels).map(
                                                (field) => (
                                                    <td
                                                        key={field}
                                                        className="px-3 py-2 text-muted-foreground"
                                                    >
                                                        {visit.personnel[
                                                            field
                                                        ] ?? '-'}
                                                    </td>
                                                ),
                                            )}
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
