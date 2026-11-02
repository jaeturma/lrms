import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    ClipboardCheck,
    Printer,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { VisitStage } from '@/components/valid-lr/visit-sections';
import {
    Field,
    fieldClass,
    PersonnelList,
    stageTone,
} from '@/components/valid-lr/visit-sections';
import { cn } from '@/lib/utils';

type LearningResourceRow = {
    level: 'elementary' | 'secondary';
    group_label: string;
    label: string;
    title: string | null;
    publisher: string | null;
    allocated: number | null;
    lrmis: number | null;
    lrms: number | null;
    enrolment: number | null;
};

type SmeRow = {
    group_label: string | null;
    item_name: string;
    unit: string | null;
    key_stage: string;
    quantity: number;
    years_delivered: number[];
};

type TvlRow = {
    specialization: string;
    item_name: string;
    procurement_source: string | null;
    quantity: number;
    years_delivered: number[];
};

type Props = {
    school: {
        beis_school_id: string;
        school_name: string;
        region: string | null;
        division: string | null;
        district: string | null;
        municipality: string | null;
        barangay: string | null;
        school_type: string | null;
        curricular_offering: string | null;
        lrms_school: { school_id: string; school_name: string } | null;
        personnel: Record<string, string | null>;
    };
    personnelLabels: Record<string, string>;
    openVisit: {
        id: number;
        stage: VisitStage;
        team_leader_id: number | null;
        team_leader: string | null;
        scheduled_at: string | null;
        monitoring_team: string | null;
    } | null;
    stages: Record<VisitStage, string>;
    canSchedule: boolean;
    teamLeaders: Array<{ id: number; name: string; role: string }>;
    profile: {
        learning_resources: LearningResourceRow[];
        sme: {
            timeline: Record<string, number[]>;
            central_office: SmeRow[];
            downloaded: SmeRow[];
        };
        tvl: {
            programs: Array<{
                specialization: string;
                delivery_years: number[];
                status: string | null;
            }>;
            items: TvlRow[];
        };
    };
    visits: Array<{
        id: number;
        status: string;
        stage: VisitStage;
        visited_at: string | null;
        monitor: string | null;
        monitoring_team: string | null;
    }>;
};

type Tab = 'lr' | 'sme' | 'tvl';

const formatNumber = (value: number | null) =>
    value === null ? '-' : value.toLocaleString();

export default function AdminValidLrSchool({
    school,
    profile,
    visits,
    personnelLabels,
    openVisit,
    stages,
    canSchedule,
    teamLeaders,
}: Props) {
    const [tab, setTab] = useState<Tab>('lr');
    const schedule = useForm({
        team_leader_id: openVisit?.team_leader_id
            ? String(openVisit.team_leader_id)
            : '',
        scheduled_at: openVisit?.scheduled_at ?? '',
        monitoring_team: openVisit?.monitoring_team ?? '',
    });

    const levels = useMemo(
        () =>
            (['elementary', 'secondary'] as const).filter((level) =>
                profile.learning_resources.some((row) => row.level === level),
            ),
        [profile.learning_resources],
    );

    const scheduleMonitoring = () => {
        schedule.transform((payload) => ({
            team_leader_id: payload.team_leader_id
                ? Number(payload.team_leader_id)
                : null,
            scheduled_at: payload.scheduled_at || null,
            monitoring_team: payload.monitoring_team || null,
        }));
        schedule.post(
            `/app/admin/valid-lr/schools/${school.beis_school_id}/visits`,
        );
    };

    const tabs: Array<{ key: Tab; label: string; count: number }> = [
        {
            key: 'lr',
            label: 'Learning Resources',
            count: profile.learning_resources.length,
        },
        {
            key: 'sme',
            label: 'Science & Math Equipment',
            count:
                profile.sme.central_office.length +
                profile.sme.downloaded.length,
        },
        {
            key: 'tvl',
            label: 'TVL Tools & Equipment',
            count: profile.tvl.items.length,
        },
    ];

    return (
        <>
            <Head title={`VALID-LR · ${school.school_name}`} />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={ClipboardCheck}
                        iconClassName="bg-teal-100 text-teal-600 dark:bg-teal-900/60 dark:text-teal-300"
                        title={school.school_name}
                        description={[
                            `School ID ${school.beis_school_id}`,
                            school.region,
                            school.division,
                            school.district,
                            school.curricular_offering,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                        actions={
                            <>
                                <Button variant="outline" asChild>
                                    <Link href="/app/admin/valid-lr">
                                        <ArrowLeft className="mr-1 h-4 w-4" />
                                        Back
                                    </Link>
                                </Button>
                                {openVisit && (
                                    <Button asChild>
                                        <Link
                                            href={`/app/admin/valid-lr/visits/${openVisit.id}/edit`}
                                        >
                                            Open Current Visit
                                        </Link>
                                    </Button>
                                )}
                            </>
                        }
                    >
                        <p className="mt-1 text-xs text-muted-foreground">
                            {school.lrms_school
                                ? `Linked to LRMS school ${school.lrms_school.school_name} — LRMS-recorded quantities and enrolment are shown alongside.`
                                : 'This school is not registered in LRMS yet, so only the imported reference data is shown.'}
                        </p>
                    </PageHeader>

                    <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="mb-3 text-lg font-semibold text-foreground">
                                School Personnel
                            </h2>
                            {school.lrms_school ? (
                                <PersonnelList
                                    personnel={school.personnel}
                                    labels={personnelLabels}
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Not available — the school has no LRMS
                                    account to keep its profile.
                                </p>
                            )}
                        </section>

                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <h2 className="text-lg font-semibold text-foreground">
                                    Monitoring Schedule
                                </h2>
                                {openVisit && (
                                    <StatusBadge
                                        tone={stageTone[openVisit.stage]}
                                    >
                                        {stages[openVisit.stage]}
                                    </StatusBadge>
                                )}
                            </div>
                            {canSchedule ? (
                                <div className="grid gap-3">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <Field
                                            label="Team leader"
                                            error={
                                                schedule.errors.team_leader_id
                                            }
                                        >
                                            <select
                                                className={cn(
                                                    fieldClass,
                                                    'h-9',
                                                )}
                                                value={
                                                    schedule.data.team_leader_id
                                                }
                                                onChange={(event) =>
                                                    schedule.setData(
                                                        'team_leader_id',
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Not assigned
                                                </option>
                                                {teamLeaders.map((leader) => (
                                                    <option
                                                        key={leader.id}
                                                        value={leader.id}
                                                    >
                                                        {leader.name} (
                                                        {leader.role})
                                                    </option>
                                                ))}
                                            </select>
                                        </Field>
                                        <Field
                                            label="Scheduled monitoring"
                                            error={schedule.errors.scheduled_at}
                                        >
                                            <Input
                                                type="datetime-local"
                                                value={
                                                    schedule.data.scheduled_at
                                                }
                                                onChange={(event) =>
                                                    schedule.setData(
                                                        'scheduled_at',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>
                                    <Field
                                        label="Monitoring team members"
                                        error={schedule.errors.monitoring_team}
                                    >
                                        <Input
                                            value={
                                                schedule.data.monitoring_team
                                            }
                                            onChange={(event) =>
                                                schedule.setData(
                                                    'monitoring_team',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <div>
                                        <Button
                                            type="button"
                                            disabled={schedule.processing}
                                            onClick={scheduleMonitoring}
                                        >
                                            <CalendarClock className="mr-1 h-4 w-4" />
                                            {openVisit
                                                ? 'Update Schedule'
                                                : 'Schedule Monitoring'}
                                        </Button>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        The school completes its profile,
                                        enrolment and counts first; the team
                                        leader validates once the school
                                        submits.
                                    </p>
                                </div>
                            ) : openVisit ? (
                                <p className="text-sm text-muted-foreground">
                                    Team leader:{' '}
                                    {openVisit.team_leader ?? 'not assigned'}
                                    {openVisit.scheduled_at
                                        ? ` · scheduled ${openVisit.scheduled_at.replace('T', ' ')}`
                                        : ''}
                                </p>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Monitoring has not been scheduled for this
                                    school yet.
                                </p>
                            )}
                        </section>
                    </div>

                    {visits.length > 0 && (
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="mb-2 text-lg font-semibold text-foreground">
                                Monitoring Visits
                            </h2>
                            <ul className="divide-y divide-border text-sm">
                                {visits.map((visit) => (
                                    <li
                                        key={visit.id}
                                        className="flex flex-wrap items-center gap-3 py-2"
                                    >
                                        <Link
                                            href={`/app/admin/valid-lr/visits/${visit.id}/edit`}
                                            className="font-medium text-primary hover:underline"
                                        >
                                            {visit.visited_at ??
                                                `Visit #${visit.id}`}
                                        </Link>
                                        <span className="text-muted-foreground">
                                            {visit.monitoring_team ??
                                                visit.monitor ??
                                                '-'}
                                        </span>
                                        <StatusBadge
                                            tone={stageTone[visit.stage]}
                                        >
                                            {stages[visit.stage]}
                                        </StatusBadge>
                                        <a
                                            href={`/app/admin/valid-lr/visits/${visit.id}/print`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="ml-auto inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                        >
                                            <Printer className="h-3.5 w-3.5" />
                                            Print
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div
                            role="tablist"
                            className="mb-4 flex flex-wrap gap-2"
                        >
                            {tabs.map((item) => (
                                <button
                                    key={item.key}
                                    type="button"
                                    role="tab"
                                    aria-selected={tab === item.key}
                                    onClick={() => setTab(item.key)}
                                    className={cn(
                                        'rounded-full border px-3 py-1.5 text-sm font-medium',
                                        tab === item.key
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {item.label} ({item.count})
                                </button>
                            ))}
                        </div>

                        {tab === 'lr' &&
                            (levels.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No learning resource allocation found for
                                    this school.
                                </p>
                            ) : (
                                levels.map((level) => (
                                    <div key={level} className="mb-6">
                                        <h3 className="mb-2 font-semibold text-foreground">
                                            {level === 'elementary'
                                                ? 'Elementary (ES)'
                                                : 'Secondary (JHS / SHS)'}
                                        </h3>
                                        {level === 'elementary' && (
                                            <p className="mb-2 text-xs text-muted-foreground">
                                                Elementary allocations are
                                                division-level totals, as in the
                                                monitoring tool.
                                            </p>
                                        )}
                                        <div className="overflow-x-auto rounded-xl border border-border">
                                            <table className="min-w-full text-sm">
                                                <thead className="bg-muted text-left text-foreground">
                                                    <tr>
                                                        <th className="px-3 py-2">
                                                            Grade
                                                        </th>
                                                        <th className="px-3 py-2">
                                                            Learning Area
                                                        </th>
                                                        <th className="px-3 py-2">
                                                            Title
                                                        </th>
                                                        <th className="px-3 py-2">
                                                            Publisher
                                                        </th>
                                                        <th className="px-3 py-2 text-right">
                                                            Allocated
                                                        </th>
                                                        <th className="px-3 py-2 text-right">
                                                            LRMIS Encoded
                                                        </th>
                                                        <th className="px-3 py-2 text-right">
                                                            LRMS Recorded
                                                        </th>
                                                        <th className="px-3 py-2 text-right">
                                                            Enrolment
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {profile.learning_resources
                                                        .filter(
                                                            (row) =>
                                                                row.level ===
                                                                level,
                                                        )
                                                        .map((row) => (
                                                            <tr
                                                                key={`${row.group_label}-${row.label}`}
                                                                className="border-t border-border"
                                                            >
                                                                <td className="px-3 py-1.5 whitespace-nowrap text-muted-foreground">
                                                                    {
                                                                        row.group_label
                                                                    }
                                                                </td>
                                                                <td className="px-3 py-1.5 text-foreground">
                                                                    {row.label}
                                                                </td>
                                                                <td className="px-3 py-1.5 text-foreground">
                                                                    {row.title ??
                                                                        '-'}
                                                                </td>
                                                                <td className="px-3 py-1.5 text-muted-foreground">
                                                                    {row.publisher ??
                                                                        '-'}
                                                                </td>
                                                                <td className="px-3 py-1.5 text-right tabular-nums">
                                                                    {formatNumber(
                                                                        row.allocated,
                                                                    )}
                                                                </td>
                                                                <td
                                                                    className={cn(
                                                                        'px-3 py-1.5 text-right tabular-nums',
                                                                        row.allocated !==
                                                                            null &&
                                                                            (row.lrmis ??
                                                                                0) <
                                                                                row.allocated &&
                                                                            'text-amber-600 dark:text-amber-400',
                                                                    )}
                                                                >
                                                                    {formatNumber(
                                                                        row.lrmis,
                                                                    )}
                                                                </td>
                                                                <td className="px-3 py-1.5 text-right tabular-nums">
                                                                    {formatNumber(
                                                                        row.lrms,
                                                                    )}
                                                                </td>
                                                                <td className="px-3 py-1.5 text-right tabular-nums">
                                                                    {formatNumber(
                                                                        row.enrolment,
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ))
                            ))}

                        {tab === 'sme' && (
                            <div className="space-y-5">
                                <div>
                                    <h3 className="mb-2 font-semibold text-foreground">
                                        Delivered Resources Timeline
                                    </h3>
                                    <dl className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                        {Object.entries(
                                            profile.sme.timeline,
                                        ).map(([keyStage, years]) => (
                                            <div
                                                key={keyStage}
                                                className="rounded-xl border border-border bg-muted/40 px-3 py-2"
                                            >
                                                <dt className="text-xs text-muted-foreground">
                                                    {keyStage}
                                                </dt>
                                                <dd className="font-semibold text-foreground">
                                                    {years.length > 0
                                                        ? years.join(', ')
                                                        : 'Not delivered'}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                </div>
                                <SmeTable
                                    title="Central Office Items"
                                    rows={profile.sme.central_office}
                                />
                                <SmeTable
                                    title="Downloaded Items (RO/SDO)"
                                    rows={profile.sme.downloaded}
                                />
                            </div>
                        )}

                        {tab === 'tvl' && (
                            <div className="space-y-5">
                                <div className="overflow-x-auto rounded-xl border border-border">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-muted text-left text-foreground">
                                            <tr>
                                                <th className="px-3 py-2">
                                                    Program / Specialization
                                                </th>
                                                <th className="px-3 py-2">
                                                    Years Delivered
                                                </th>
                                                <th className="px-3 py-2">
                                                    Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {profile.tvl.programs.length ===
                                                0 && (
                                                <EmptyTableRow
                                                    colSpan={3}
                                                    message="No TVL specialization on record for this school."
                                                />
                                            )}
                                            {profile.tvl.programs.map(
                                                (program) => (
                                                    <tr
                                                        key={
                                                            program.specialization
                                                        }
                                                        className="border-t border-border"
                                                    >
                                                        <td className="px-3 py-1.5 text-foreground">
                                                            {
                                                                program.specialization
                                                            }
                                                        </td>
                                                        <td className="px-3 py-1.5 tabular-nums">
                                                            {program
                                                                .delivery_years
                                                                .length > 0
                                                                ? program.delivery_years.join(
                                                                      ', ',
                                                                  )
                                                                : '-'}
                                                        </td>
                                                        <td className="px-3 py-1.5">
                                                            <StatusBadge
                                                                tone={
                                                                    program.status ===
                                                                    'Delivered'
                                                                        ? 'success'
                                                                        : 'warning'
                                                                }
                                                            >
                                                                {program.status ??
                                                                    'Unknown'}
                                                            </StatusBadge>
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="overflow-x-auto rounded-xl border border-border">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-muted text-left text-foreground">
                                            <tr>
                                                <th className="px-3 py-2">
                                                    Specialization
                                                </th>
                                                <th className="px-3 py-2">
                                                    Item
                                                </th>
                                                <th className="px-3 py-2">
                                                    Procured By
                                                </th>
                                                <th className="px-3 py-2 text-right">
                                                    Qty per Specialization
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {profile.tvl.items.length === 0 && (
                                                <EmptyTableRow
                                                    colSpan={4}
                                                    message="No TVL package items for this school's specializations."
                                                />
                                            )}
                                            {profile.tvl.items.map(
                                                (item, index) => (
                                                    <tr
                                                        key={index}
                                                        className="border-t border-border"
                                                    >
                                                        <td className="px-3 py-1.5 text-muted-foreground">
                                                            {
                                                                item.specialization
                                                            }
                                                        </td>
                                                        <td className="px-3 py-1.5 text-foreground">
                                                            {item.item_name}
                                                        </td>
                                                        <td className="px-3 py-1.5 text-muted-foreground">
                                                            {sourceLabel(
                                                                item.procurement_source,
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-1.5 text-right tabular-nums">
                                                            {item.quantity.toLocaleString()}
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </section>
                </div>
            </main>
        </>
    );
}

function sourceLabel(source: string | null) {
    if (source === 'central_office') {
        return 'Central Office';
    }

    if (source === 'downloaded') {
        return 'RO/SDO (Downloaded)';
    }

    return '-';
}

function SmeTable({ title, rows }: { title: string; rows: SmeRow[] }) {
    return (
        <div>
            <h3 className="mb-2 font-semibold text-foreground">
                {title} ({rows.length})
            </h3>
            <div className="overflow-x-auto rounded-xl border border-border">
                <table className="min-w-full text-sm">
                    <thead className="bg-muted text-left text-foreground">
                        <tr>
                            <th className="px-3 py-2">Lot / Learning Area</th>
                            <th className="px-3 py-2">Item</th>
                            <th className="px-3 py-2">Unit</th>
                            <th className="px-3 py-2">Key Stage</th>
                            <th className="px-3 py-2 text-right">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && (
                            <EmptyTableRow
                                colSpan={5}
                                message="No SME package delivered for this school's key stages."
                            />
                        )}
                        {rows.map((row, index) => (
                            <tr key={index} className="border-t border-border">
                                <td className="px-3 py-1.5 text-muted-foreground">
                                    {row.group_label ?? '-'}
                                </td>
                                <td className="px-3 py-1.5 text-foreground">
                                    {row.item_name}
                                </td>
                                <td className="px-3 py-1.5 text-muted-foreground">
                                    {row.unit ?? '-'}
                                </td>
                                <td className="px-3 py-1.5 whitespace-nowrap text-muted-foreground">
                                    {row.key_stage}
                                </td>
                                <td className="px-3 py-1.5 text-right tabular-nums">
                                    {row.quantity.toLocaleString()}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
