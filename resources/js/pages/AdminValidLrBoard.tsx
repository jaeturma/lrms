import { Head, Link, usePoll } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, CircleAlert, Radio } from 'lucide-react';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import type { VisitStage } from '@/components/valid-lr/visit-sections';
import { stageTone } from '@/components/valid-lr/visit-sections';

type BoardRow = {
    id: number;
    stage: VisitStage;
    beis_school_id: string;
    school_name: string;
    district: string | null;
    in_lrms: boolean;
    missing_profile: string[] | null;
    enrolment_complete: boolean;
    team_leader: string | null;
    scheduled_at: string | null;
    school_submitted_at: string | null;
    overridden: boolean;
    updated_at: string | null;
    items_total: number;
    school_counted: number;
    validated: number;
    mismatched: number;
};

type Props = {
    filters: {
        stage?: string;
        district?: string;
        leader?: number;
        search?: string;
    };
    visits: {
        data: BoardRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    summary: {
        schools: number;
        not_started: number;
        stages: Record<VisitStage, number>;
    };
    stages: Record<VisitStage, string>;
    districts: string[];
    leaders: Array<{ id: number; name: string }>;
    currentUserId: number;
    refreshedAt: string;
};

const selectClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground';

function ProgressBar({
    value,
    total,
    className,
}: {
    value: number;
    total: number;
    className: string;
}) {
    const percent = total > 0 ? Math.round((value / total) * 100) : 0;

    return (
        <div className="min-w-28">
            <div className="h-2 overflow-hidden rounded-full bg-muted">
                <div
                    className={`h-full rounded-full ${className}`}
                    style={{ width: `${percent}%` }}
                />
            </div>
            <p className="mt-0.5 text-xs text-muted-foreground tabular-nums">
                {value.toLocaleString()} / {total.toLocaleString()} ({percent}
                %)
            </p>
        </div>
    );
}

export default function AdminValidLrBoard({
    filters,
    visits,
    summary,
    stages,
    districts,
    leaders,
    currentUserId,
    refreshedAt,
}: Props) {
    usePoll(30000);

    const tiles: Array<{ label: string; value: number; stage?: VisitStage }> = [
        { label: 'Schools in VALID-LR', value: summary.schools },
        { label: 'Not started by school', value: summary.not_started },
        ...(Object.keys(stages) as VisitStage[]).map((stage) => ({
            label: stages[stage],
            value: summary.stages[stage] ?? 0,
            stage,
        })),
    ];

    return (
        <>
            <Head title="VALID-LR Live Board" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={Radio}
                        iconClassName="bg-teal-100 text-teal-600 dark:bg-teal-900/60 dark:text-teal-300"
                        title="VALID-LR Live Board"
                        description={`School preparation and on-site validation as they happen. Refreshes every 30 seconds · last updated ${refreshedAt}.`}
                        actions={
                            <Button variant="outline" asChild>
                                <Link href="/app/admin/valid-lr">
                                    <ArrowLeft className="mr-1 h-4 w-4" />
                                    Monitoring
                                </Link>
                            </Button>
                        }
                    >
                        <form
                            method="get"
                            action="/app/admin/valid-lr/board"
                            className="mt-4 flex flex-wrap items-end gap-2"
                        >
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="School ID or name"
                                aria-label="Search schools"
                                className={selectClass}
                            />
                            <select
                                name="stage"
                                defaultValue={filters.stage ?? ''}
                                aria-label="Stage"
                                className={selectClass}
                            >
                                <option value="">All stages</option>
                                {Object.entries(stages).map(([key, label]) => (
                                    <option key={key} value={key}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="district"
                                defaultValue={filters.district ?? ''}
                                aria-label="District"
                                className={selectClass}
                            >
                                <option value="">All Districts</option>
                                {districts.map((district) => (
                                    <option key={district} value={district}>
                                        {district}
                                    </option>
                                ))}
                            </select>
                            <select
                                name="leader"
                                defaultValue={filters.leader ?? ''}
                                aria-label="Team leader"
                                className={selectClass}
                            >
                                <option value="">All team leaders</option>
                                {leaders.map((leader) => (
                                    <option key={leader.id} value={leader.id}>
                                        {leader.id === currentUserId
                                            ? `${leader.name} (me)`
                                            : leader.name}
                                    </option>
                                ))}
                            </select>
                            <button
                                type="submit"
                                className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground"
                            >
                                Filter
                            </button>
                        </form>
                    </PageHeader>

                    <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        {tiles.map((tile) => (
                            <div
                                key={tile.label}
                                className="rounded-2xl border border-border bg-card px-4 py-3 shadow-sm"
                            >
                                <dt className="text-xs text-muted-foreground">
                                    {tile.stage ? (
                                        <StatusBadge
                                            tone={stageTone[tile.stage]}
                                        >
                                            {tile.label}
                                        </StatusBadge>
                                    ) : (
                                        tile.label
                                    )}
                                </dt>
                                <dd className="text-2xl font-semibold text-foreground tabular-nums">
                                    {tile.value.toLocaleString()}
                                </dd>
                            </div>
                        ))}
                    </dl>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold text-foreground">
                            Schools with monitoring activity ({visits.total})
                        </h2>
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">School</th>
                                        <th className="px-3 py-2">Stage</th>
                                        <th className="px-3 py-2">
                                            School preparation
                                        </th>
                                        <th className="px-3 py-2">
                                            School counts
                                        </th>
                                        <th className="px-3 py-2">Validated</th>
                                        <th className="px-3 py-2 text-right">
                                            Count differences
                                        </th>
                                        <th className="px-3 py-2">
                                            Team leader / schedule
                                        </th>
                                        <th className="px-3 py-2">
                                            Last activity
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {visits.data.length === 0 && (
                                        <EmptyTableRow
                                            colSpan={8}
                                            message="No monitoring activity matches these filters yet."
                                        />
                                    )}
                                    {visits.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-t border-border align-top"
                                        >
                                            <td className="px-3 py-2">
                                                <Link
                                                    href={`/app/admin/valid-lr/visits/${row.id}/edit`}
                                                    className="font-medium text-primary underline-offset-2 hover:underline"
                                                >
                                                    {row.school_name}
                                                </Link>
                                                <div className="text-xs text-muted-foreground">
                                                    {row.beis_school_id}
                                                    {row.district
                                                        ? ` · ${row.district}`
                                                        : ''}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2">
                                                <StatusBadge
                                                    tone={stageTone[row.stage]}
                                                >
                                                    {stages[row.stage]}
                                                </StatusBadge>
                                                {row.school_submitted_at && (
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        Submitted{' '}
                                                        {
                                                            row.school_submitted_at
                                                        }
                                                    </div>
                                                )}
                                                {row.overridden && (
                                                    <div className="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                                        Admin override
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-xs">
                                                {!row.in_lrms ? (
                                                    <span className="text-amber-600 dark:text-amber-400">
                                                        No LRMS account
                                                    </span>
                                                ) : (
                                                    <ul className="grid gap-0.5">
                                                        <PrepLine
                                                            done={
                                                                (
                                                                    row.missing_profile ??
                                                                    []
                                                                ).length === 0
                                                            }
                                                        >
                                                            {(
                                                                row.missing_profile ??
                                                                []
                                                            ).length === 0
                                                                ? 'Profile complete'
                                                                : `Missing ${row.missing_profile?.join(', ')}`}
                                                        </PrepLine>
                                                        <PrepLine
                                                            done={
                                                                row.enrolment_complete
                                                            }
                                                        >
                                                            Enrolment
                                                            {row.enrolment_complete
                                                                ? ' encoded'
                                                                : ' missing'}
                                                        </PrepLine>
                                                    </ul>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <ProgressBar
                                                    value={row.school_counted}
                                                    total={row.items_total}
                                                    className="bg-sky-500"
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                <ProgressBar
                                                    value={row.validated}
                                                    total={row.items_total}
                                                    className="bg-teal-500"
                                                />
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {row.mismatched > 0 ? (
                                                    <span className="font-semibold text-amber-600 dark:text-amber-400">
                                                        {row.mismatched}
                                                    </span>
                                                ) : (
                                                    0
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-xs text-muted-foreground">
                                                <div className="font-medium text-foreground">
                                                    {row.team_leader ??
                                                        'Not assigned'}
                                                </div>
                                                {row.scheduled_at ??
                                                    'No schedule'}
                                            </td>
                                            <td className="px-3 py-2 text-xs whitespace-nowrap text-muted-foreground">
                                                {row.updated_at}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={visits.links} className="mt-4" />
                    </section>
                </div>
            </main>
        </>
    );
}

function PrepLine({
    done,
    children,
}: {
    done: boolean;
    children: React.ReactNode;
}) {
    return (
        <li className="flex items-center gap-1">
            {done ? (
                <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" />
            ) : (
                <CircleAlert className="h-3.5 w-3.5 text-amber-600" />
            )}
            <span
                className={done ? 'text-foreground' : 'text-muted-foreground'}
            >
                {children}
            </span>
        </li>
    );
}
