import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    CircleAlert,
    ClipboardCheck,
    Lock,
    Printer,
    Save,
    Trash2,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { LrmisAdjustPanel } from '@/components/valid-lr/lrmis-adjust-panel';
import type {
    Checklist,
    VisitItem,
    VisitStage,
} from '@/components/valid-lr/visit-sections';
import {
    AralTable,
    changedItems,
    EquipmentTable,
    Field,
    fieldClass,
    LearnersWithDisabilitiesTable,
    LearningResourceTable,
    PersonnelList,
    stageTone,
    textareaClass,
} from '@/components/valid-lr/visit-sections';
import { cn } from '@/lib/utils';

type Indicator = { answer: 'yes' | 'no' | null; remarks: string | null };

type Visit = {
    id: number;
    status: 'draft' | 'completed';
    stage: VisitStage;
    visited_at: string | null;
    scheduled_at: string | null;
    monitoring_team: string | null;
    school_representative: string | null;
    indicators: Record<string, Indicator>;
    lr_delivery_status: string | null;
    sme_delivery_status: string | null;
    tvl_delivery_status: string | null;
    key_findings: string | null;
    recommended_actions: string | null;
    issues_concerns: string | null;
    actions_taken: string | null;
    prepared_by: string | null;
    acknowledged_by: string | null;
    monitor: string | null;
    team_leader: { id: number; name: string } | null;
    school_submitted_at: string | null;
    school_submitted_by: string | null;
    override_reason: string | null;
    overridden_by: string | null;
    overridden_at: string | null;
    validation_started_at: string | null;
    updated_at: string | null;
    personnel: Record<string, string | null>;
    items: VisitItem[];
};

type Props = {
    visit: Visit;
    school: {
        beis_school_id: string;
        school_name: string;
        region: string | null;
        division: string | null;
        district: string | null;
        curricular_offering: string | null;
    };
    checklist: Checklist;
    liveSchoolCounts: Record<number, number | null>;
    liveStage: {
        stage: VisitStage;
        school_submitted_at: string | null;
        school_submitted_by: string | null;
    };
    canEdit: boolean;
    canSchedule: boolean;
    canOverride: boolean;
    canDelete: boolean;
    canAdjustLrmis: boolean;
    teamLeaders: Array<{ id: number; name: string; role: string }>;
    indicatorQuestions: Record<string, string>;
    movOptions: string[];
    aralPrograms: Record<string, string>;
    deliveryStatuses: string[];
    personnelLabels: Record<string, string>;
    stages: Record<VisitStage, string>;
};

type Tab =
    | 'profile'
    | 'lr'
    | 'sme_co'
    | 'sme_dl'
    | 'tvl'
    | 'im_lwd'
    | 'aral'
    | 'findings';

export default function AdminValidLrVisit({
    visit,
    school,
    checklist,
    liveSchoolCounts,
    liveStage,
    canEdit,
    canSchedule,
    canOverride,
    canDelete,
    canAdjustLrmis,
    teamLeaders,
    indicatorQuestions,
    movOptions,
    aralPrograms,
    deliveryStatuses,
    personnelLabels,
    stages,
}: Props) {
    const [tab, setTab] = useState<Tab>('profile');
    const stage = liveStage?.stage ?? visit.stage;
    const awaitingSchool = stage === 'awaiting_school';
    const editable = canEdit && !awaitingSchool;
    const savedItems = useRef<VisitItem[]>(visit.items);

    // Keep the school's counts and submission status current while the team
    // works, without reloading the whole visit.
    usePoll(
        20000,
        { only: ['liveSchoolCounts', 'liveStage', 'checklist'] },
        { autoStart: visit.status !== 'completed' },
    );

    const {
        data,
        setData,
        setDefaults,
        put,
        transform,
        processing,
        errors,
        isDirty,
    } = useForm({
        status: visit.status,
        visited_at: visit.visited_at ?? '',
        monitoring_team: visit.monitoring_team ?? '',
        school_representative: visit.school_representative ?? '',
        indicators: Object.fromEntries(
            Object.keys(indicatorQuestions).map((key) => [
                key,
                visit.indicators[key] ?? { answer: null, remarks: null },
            ]),
        ) as Record<string, Indicator>,
        lr_delivery_status: visit.lr_delivery_status ?? '',
        sme_delivery_status: visit.sme_delivery_status ?? '',
        tvl_delivery_status: visit.tvl_delivery_status ?? '',
        key_findings: visit.key_findings ?? '',
        recommended_actions: visit.recommended_actions ?? '',
        issues_concerns: visit.issues_concerns ?? '',
        actions_taken: visit.actions_taken ?? '',
        prepared_by: visit.prepared_by ?? '',
        acknowledged_by: visit.acknowledged_by ?? '',
        items: visit.items,
    });

    // Keep monitors from losing counts: ask before leaving with unsaved
    // changes, whether by an in-app link or by closing the tab.
    useEffect(() => {
        if (!isDirty || !editable) {
            return;
        }

        const warning =
            'This monitoring visit has unsaved changes. Leave without saving?';

        const removeRouterGuard = router.on('before', (event) => {
            if (event.detail.visit.method !== 'get') {
                return;
            }

            if (event.detail.visit.only.length > 0) {
                return;
            }

            if (!window.confirm(warning)) {
                event.preventDefault();
            }
        });

        const handleBeforeUnload = (event: BeforeUnloadEvent) => {
            event.preventDefault();
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            removeRouterGuard();
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, [isDirty, editable]);

    const sectionCounts = useMemo(() => {
        const counts: Record<string, number> = {};

        data.items.forEach((item) => {
            counts[item.section] = (counts[item.section] ?? 0) + 1;
        });

        return counts;
    }, [data.items]);

    const updateItem = <K extends keyof VisitItem>(
        id: number,
        field: K,
        value: VisitItem[K],
    ) => {
        setData(
            'items',
            data.items.map((item) =>
                item.id === id ? { ...item, [field]: value } : item,
            ),
        );
    };

    const updateIndicator = (key: string, change: Partial<Indicator>) => {
        setData('indicators', {
            ...data.indicators,
            [key]: { ...data.indicators[key], ...change },
        });
    };

    const save = (status?: 'draft' | 'completed') => {
        if (status) {
            setData('status', status);
        }

        transform((payload) => ({
            ...payload,
            status: status ?? payload.status,
            visited_at: payload.visited_at || null,
            items: changedItems(payload.items, savedItems.current).map(
                (item) => ({
                    id: item.id,
                    reference_quantity: item.reference_quantity,
                    distributed_quantity: item.distributed_quantity,
                    actual_quantity: item.actual_quantity,
                    enrolment: item.enrolment,
                    year_received: item.year_received,
                    mov: item.mov,
                    remarks_repairs: item.remarks_repairs,
                    remarks_missing: item.remarks_missing,
                    remarks_others: item.remarks_others,
                    ...(item.section === 'aral' ? { extra: item.extra } : {}),
                }),
            ),
        }));

        put(`/app/admin/valid-lr/visits/${visit.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                savedItems.current = data.items;
                setDefaults();
                toast.success(
                    status === 'completed'
                        ? 'Monitoring visit completed and saved.'
                        : 'Monitoring visit saved.',
                );
            },
            onError: (validationErrors) => {
                if (status === 'completed') {
                    setData('status', visit.status);
                }

                toast.error(
                    Object.values(validationErrors)[0] ??
                        'Some fields are invalid. Please review them.',
                );
            },
        });
    };

    const printVisit = () => {
        if (
            isDirty &&
            editable &&
            !window.confirm(
                'The printout shows the last saved version. Print without your unsaved changes?',
            )
        ) {
            return;
        }

        window.open(`/app/admin/valid-lr/visits/${visit.id}/print`, '_blank');
    };

    const deleteVisit = () => {
        if (
            !window.confirm(
                'Delete this monitoring visit and everything recorded in it?',
            )
        ) {
            return;
        }

        router.delete(`/app/admin/valid-lr/visits/${visit.id}`);
    };

    const tabs: Array<{ key: Tab; label: string; count?: number }> = [
        { key: 'profile', label: 'Visit & Indicators' },
        { key: 'lr', label: 'Learning Resources', count: sectionCounts.lr },
        {
            key: 'sme_co',
            label: 'SME Central Office',
            count: sectionCounts.sme_co,
        },
        {
            key: 'sme_dl',
            label: 'SME Downloaded',
            count: sectionCounts.sme_dl,
        },
        { key: 'tvl', label: 'TVL', count: sectionCounts.tvl },
        { key: 'im_lwd', label: 'IMs for LWD', count: sectionCounts.im_lwd },
        { key: 'aral', label: 'ARAL', count: sectionCounts.aral },
        { key: 'findings', label: 'Findings & Sign-off' },
    ];

    const itemsFor = (section: VisitItem['section']) =>
        data.items.filter((item) => item.section === section);

    // LRMIS counts can be corrected separately; show the latest saved values.
    const savedById = new Map(visit.items.map((item) => [item.id, item]));
    const learningResourceItems = itemsFor('lr').map((item) => {
        const saved = savedById.get(item.id);

        return saved
            ? {
                  ...item,
                  lrmis_quantity: saved.lrmis_quantity,
                  lrmis_original_quantity: saved.lrmis_original_quantity,
                  lrmis_adjusted_by: saved.lrmis_adjusted_by,
                  lrmis_adjusted_at: saved.lrmis_adjusted_at,
              }
            : item;
    });

    return (
        <>
            <Head title={`VALID-LR Visit · ${school.school_name}`} />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={ClipboardCheck}
                        iconClassName="bg-teal-100 text-teal-600 dark:bg-teal-900/60 dark:text-teal-300"
                        title={`Monitoring Visit — ${school.school_name}`}
                        description={[
                            `School ID ${school.beis_school_id}`,
                            school.region,
                            school.division,
                            school.curricular_offering,
                            visit.team_leader
                                ? `Team leader: ${visit.team_leader.name}`
                                : 'No team leader assigned',
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                        actions={
                            <>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/app/admin/valid-lr/schools/${school.beis_school_id}`}
                                    >
                                        <ArrowLeft className="mr-1 h-4 w-4" />
                                        School
                                    </Link>
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={printVisit}
                                >
                                    <Printer className="mr-1 h-4 w-4" />
                                    Print
                                </Button>
                                {canDelete && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={deleteVisit}
                                    >
                                        <Trash2 className="mr-1 h-4 w-4" />
                                        Delete
                                    </Button>
                                )}
                                {editable && (
                                    <>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={processing}
                                            onClick={() => save('draft')}
                                        >
                                            <Save className="mr-1 h-4 w-4" />
                                            Save Draft
                                        </Button>
                                        <Button
                                            type="button"
                                            disabled={processing}
                                            onClick={() => save('completed')}
                                        >
                                            Mark Completed
                                        </Button>
                                    </>
                                )}
                            </>
                        }
                    >
                        <div className="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <StatusBadge tone={stageTone[stage]}>
                                {stages[stage]}
                            </StatusBadge>
                            {!canEdit && (
                                <span className="inline-flex items-center gap-1 text-muted-foreground">
                                    <Lock className="h-3.5 w-3.5" />
                                    View only — the assigned team leader and
                                    system admins can edit this visit.
                                </span>
                            )}
                            {editable && isDirty && (
                                <span className="font-medium text-amber-600 dark:text-amber-400">
                                    You have unsaved changes.
                                </span>
                            )}
                            {editable && !isDirty && visit.updated_at && (
                                <span className="text-muted-foreground">
                                    All changes saved · last saved{' '}
                                    {visit.updated_at}
                                </span>
                            )}
                        </div>
                    </PageHeader>

                    <PreparationPanel
                        visitId={visit.id}
                        stage={stage}
                        checklist={checklist}
                        submittedAt={
                            liveStage?.school_submitted_at ??
                            visit.school_submitted_at
                        }
                        submittedBy={
                            liveStage?.school_submitted_by ??
                            visit.school_submitted_by
                        }
                        overrideReason={visit.override_reason}
                        overriddenBy={visit.overridden_by}
                        canOverride={canOverride}
                    />

                    {canSchedule && visit.status !== 'completed' && (
                        <AssignmentCard
                            visit={visit}
                            teamLeaders={teamLeaders}
                        />
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
                                    {item.label}
                                    {item.count !== undefined &&
                                        ` (${item.count})`}
                                </button>
                            ))}
                        </div>

                        {tab === 'lr' && canAdjustLrmis && (
                            <LrmisAdjustPanel
                                key={visit.updated_at ?? 'lrmis'}
                                visitId={visit.id}
                                items={visit.items.filter(
                                    (item) => item.section === 'lr',
                                )}
                            />
                        )}

                        <fieldset disabled={!editable} className="min-w-0">
                            {tab === 'profile' && (
                                <div className="space-y-5">
                                    <div className="grid gap-3 md:grid-cols-3">
                                        <Field
                                            label="Date and Time"
                                            error={errors.visited_at}
                                        >
                                            <Input
                                                type="datetime-local"
                                                value={data.visited_at}
                                                onChange={(event) =>
                                                    setData(
                                                        'visited_at',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                        <Field
                                            label="Monitoring Team"
                                            error={errors.monitoring_team}
                                        >
                                            <Input
                                                value={data.monitoring_team}
                                                onChange={(event) =>
                                                    setData(
                                                        'monitoring_team',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                        <Field
                                            label="Name of School Representative"
                                            error={errors.school_representative}
                                        >
                                            <Input
                                                value={
                                                    data.school_representative
                                                }
                                                onChange={(event) =>
                                                    setData(
                                                        'school_representative',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <div className="rounded-xl border border-border p-4">
                                        <h3 className="mb-2 text-sm font-semibold text-foreground">
                                            School Personnel
                                        </h3>
                                        <PersonnelList
                                            personnel={visit.personnel}
                                            labels={personnelLabels}
                                        />
                                    </div>

                                    <div className="overflow-x-auto rounded-xl border border-border">
                                        <table className="min-w-full text-sm">
                                            <thead className="bg-muted text-left text-foreground">
                                                <tr>
                                                    <th className="px-3 py-2">
                                                        Indicator
                                                    </th>
                                                    <th className="px-3 py-2">
                                                        Yes
                                                    </th>
                                                    <th className="px-3 py-2">
                                                        No
                                                    </th>
                                                    <th className="px-3 py-2">
                                                        Remarks
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {Object.entries(
                                                    indicatorQuestions,
                                                ).map(([key, question]) => (
                                                    <tr
                                                        key={key}
                                                        className="border-t border-border"
                                                    >
                                                        <td className="px-3 py-2 text-foreground">
                                                            {question}
                                                        </td>
                                                        {(
                                                            [
                                                                'yes',
                                                                'no',
                                                            ] as const
                                                        ).map((answer) => (
                                                            <td
                                                                key={answer}
                                                                className="px-3 py-2"
                                                            >
                                                                <input
                                                                    type="radio"
                                                                    name={`indicator-${key}`}
                                                                    aria-label={`${question} ${answer}`}
                                                                    checked={
                                                                        data
                                                                            .indicators[
                                                                            key
                                                                        ]
                                                                            ?.answer ===
                                                                        answer
                                                                    }
                                                                    onChange={() =>
                                                                        updateIndicator(
                                                                            key,
                                                                            {
                                                                                answer,
                                                                            },
                                                                        )
                                                                    }
                                                                />
                                                            </td>
                                                        ))}
                                                        <td className="px-3 py-2">
                                                            <input
                                                                className={
                                                                    fieldClass
                                                                }
                                                                aria-label={`${question} remarks`}
                                                                value={
                                                                    data
                                                                        .indicators[
                                                                        key
                                                                    ]
                                                                        ?.remarks ??
                                                                    ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    updateIndicator(
                                                                        key,
                                                                        {
                                                                            remarks:
                                                                                event
                                                                                    .target
                                                                                    .value,
                                                                        },
                                                                    )
                                                                }
                                                            />
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    <div className="grid gap-3 md:grid-cols-3">
                                        {(
                                            [
                                                [
                                                    'lr_delivery_status',
                                                    'LR delivery status',
                                                ],
                                                [
                                                    'sme_delivery_status',
                                                    'SME delivery status',
                                                ],
                                                [
                                                    'tvl_delivery_status',
                                                    'TVL delivery status',
                                                ],
                                            ] as const
                                        ).map(([field, label]) => (
                                            <Field
                                                key={field}
                                                label={label}
                                                error={errors[field]}
                                            >
                                                <select
                                                    className={cn(
                                                        fieldClass,
                                                        'h-9',
                                                    )}
                                                    value={data[field]}
                                                    onChange={(event) =>
                                                        setData(
                                                            field,
                                                            event.target.value,
                                                        )
                                                    }
                                                >
                                                    <option value="">
                                                        Not assessed
                                                    </option>
                                                    {deliveryStatuses.map(
                                                        (status) => (
                                                            <option
                                                                key={status}
                                                                value={status}
                                                            >
                                                                {status}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            </Field>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {tab === 'lr' && (
                                <LearningResourceTable
                                    items={learningResourceItems}
                                    movOptions={movOptions}
                                    mode="team"
                                    schoolCounts={liveSchoolCounts}
                                    onChange={updateItem}
                                />
                            )}

                            {(tab === 'sme_co' ||
                                tab === 'sme_dl' ||
                                tab === 'tvl') && (
                                <EquipmentTable
                                    items={itemsFor(tab)}
                                    groupHeading={
                                        tab === 'tvl'
                                            ? 'Program / Specialization'
                                            : tab === 'sme_co'
                                              ? 'Lot Description'
                                              : 'Learning Area'
                                    }
                                    isTvl={tab === 'tvl'}
                                    mode="team"
                                    schoolCounts={liveSchoolCounts}
                                    onChange={updateItem}
                                />
                            )}

                            {tab === 'im_lwd' && (
                                <LearnersWithDisabilitiesTable
                                    items={itemsFor('im_lwd')}
                                    mode="team"
                                    onChange={updateItem}
                                />
                            )}

                            {tab === 'aral' && (
                                <AralTable
                                    items={itemsFor('aral')}
                                    aralPrograms={aralPrograms}
                                    onChange={updateItem}
                                />
                            )}

                            {tab === 'findings' && (
                                <div className="grid gap-3 md:grid-cols-2">
                                    {(
                                        [
                                            [
                                                'key_findings',
                                                'Immediate concerns / issues',
                                            ],
                                            [
                                                'recommended_actions',
                                                'Recommended actions',
                                            ],
                                            [
                                                'issues_concerns',
                                                'ARAL issues and concerns (delivery, quantity, quality, utilization)',
                                            ],
                                            [
                                                'actions_taken',
                                                'ARAL actions taken or needed',
                                            ],
                                        ] as const
                                    ).map(([field, label]) => (
                                        <Field
                                            key={field}
                                            label={label}
                                            error={errors[field]}
                                        >
                                            <textarea
                                                className={textareaClass}
                                                value={data[field]}
                                                onChange={(event) =>
                                                    setData(
                                                        field,
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    ))}
                                    <Field
                                        label="Prepared by (name)"
                                        error={errors.prepared_by}
                                    >
                                        <Input
                                            value={data.prepared_by}
                                            onChange={(event) =>
                                                setData(
                                                    'prepared_by',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label="Acknowledged by (School/SDO representative)"
                                        error={errors.acknowledged_by}
                                    >
                                        <Input
                                            value={data.acknowledged_by}
                                            onChange={(event) =>
                                                setData(
                                                    'acknowledged_by',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            )}
                        </fieldset>
                    </section>
                </div>
            </main>
        </>
    );
}

function ChecklistRow({
    done,
    children,
}: {
    done: boolean;
    children: React.ReactNode;
}) {
    return (
        <li className="flex items-start gap-2">
            {done ? (
                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
            ) : (
                <CircleAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
            )}
            <span
                className={done ? 'text-foreground' : 'text-muted-foreground'}
            >
                {children}
            </span>
        </li>
    );
}

function PreparationPanel({
    visitId,
    stage,
    checklist,
    submittedAt,
    submittedBy,
    overrideReason,
    overriddenBy,
    canOverride,
}: {
    visitId: number;
    stage: VisitStage;
    checklist: Checklist;
    submittedAt: string | null;
    submittedBy: string | null;
    overrideReason: string | null;
    overriddenBy: string | null;
    canOverride: boolean;
}) {
    const overrideForm = useForm({ override_reason: '' });

    const submitOverride = () => {
        overrideForm.post(`/app/admin/valid-lr/visits/${visitId}/override`, {
            preserveScroll: true,
            onSuccess: () =>
                toast.success('Validation can now proceed for this school.'),
        });
    };

    return (
        <section
            className={cn(
                'rounded-2xl border p-5 shadow-sm',
                stage === 'awaiting_school'
                    ? 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30'
                    : 'border-border bg-card',
            )}
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-foreground">
                        School Preparation
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {stage === 'awaiting_school'
                            ? 'Validation opens once the school submits its profile, enrolment and counts. Counts below update automatically.'
                            : submittedAt
                              ? `Submitted by ${submittedBy ?? 'the school'} on ${submittedAt}. The school can still correct counts until the visit is completed.`
                              : `Validation allowed without the school submission by ${overriddenBy ?? 'an admin'}: ${overrideReason ?? ''}`}
                    </p>
                </div>
                <p className="text-sm font-medium text-foreground tabular-nums">
                    {checklist.items_counted.toLocaleString()} /{' '}
                    {checklist.items_total.toLocaleString()} items counted by
                    the school
                </p>
            </div>

            <ul className="mt-3 grid gap-1.5 text-sm md:grid-cols-3">
                <ChecklistRow done={checklist.profile_complete}>
                    School profile
                    {!checklist.in_lrms
                        ? ' — school has no LRMS account'
                        : checklist.missing_profile.length > 0
                          ? ` — missing ${checklist.missing_profile.join(', ')}`
                          : ' complete'}
                </ChecklistRow>
                <ChecklistRow done={checklist.enrolment_complete}>
                    Enrolment
                    {checklist.school_year
                        ? ` for SY ${checklist.school_year}`
                        : ''}
                    {checklist.enrolment_complete ? ' encoded' : ' not encoded'}
                </ChecklistRow>
                <ChecklistRow done={checklist.counts_complete}>
                    Counts for all LR, SME and TVL items
                </ChecklistRow>
            </ul>

            {stage === 'awaiting_school' && canOverride && (
                <div className="mt-4 flex flex-wrap items-start gap-2 border-t border-amber-200 pt-4 dark:border-amber-900">
                    <div className="grid min-w-72 flex-1 gap-1">
                        <label
                            htmlFor="override-reason"
                            className="text-sm font-medium text-foreground"
                        >
                            Allow validation without the school submission
                        </label>
                        <Input
                            id="override-reason"
                            value={overrideForm.data.override_reason}
                            placeholder="Reason (e.g. school has no LRMS account yet)"
                            onChange={(event) =>
                                overrideForm.setData(
                                    'override_reason',
                                    event.target.value,
                                )
                            }
                        />
                        {overrideForm.errors.override_reason && (
                            <p className="text-sm text-red-600">
                                {overrideForm.errors.override_reason}
                            </p>
                        )}
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        className="mt-6"
                        disabled={overrideForm.processing}
                        onClick={submitOverride}
                    >
                        Override
                    </Button>
                </div>
            )}
        </section>
    );
}

function AssignmentCard({
    visit,
    teamLeaders,
}: {
    visit: Visit;
    teamLeaders: Array<{ id: number; name: string; role: string }>;
}) {
    const form = useForm({
        team_leader_id: visit.team_leader?.id
            ? String(visit.team_leader.id)
            : '',
        scheduled_at: visit.scheduled_at ?? '',
        monitoring_team: visit.monitoring_team ?? '',
    });

    const submit = () => {
        form.transform((payload) => ({
            team_leader_id: payload.team_leader_id
                ? Number(payload.team_leader_id)
                : null,
            scheduled_at: payload.scheduled_at || null,
            monitoring_team: payload.monitoring_team || null,
        }));
        form.put(`/app/admin/valid-lr/visits/${visit.id}/assignment`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Monitoring schedule updated.'),
        });
    };

    return (
        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <h2 className="mb-3 text-base font-semibold text-foreground">
                Schedule and Team Leader
            </h2>
            <div className="grid items-end gap-3 md:grid-cols-[1fr_1fr_1.5fr_auto]">
                <Field label="Team leader" error={form.errors.team_leader_id}>
                    <select
                        className={cn(fieldClass, 'h-9')}
                        value={form.data.team_leader_id}
                        onChange={(event) =>
                            form.setData('team_leader_id', event.target.value)
                        }
                    >
                        <option value="">Not assigned</option>
                        {teamLeaders.map((leader) => (
                            <option key={leader.id} value={leader.id}>
                                {leader.name} ({leader.role})
                            </option>
                        ))}
                    </select>
                </Field>
                <Field
                    label="Scheduled monitoring"
                    error={form.errors.scheduled_at}
                >
                    <Input
                        type="datetime-local"
                        value={form.data.scheduled_at}
                        onChange={(event) =>
                            form.setData('scheduled_at', event.target.value)
                        }
                    />
                </Field>
                <Field
                    label="Monitoring team members"
                    error={form.errors.monitoring_team}
                >
                    <Input
                        value={form.data.monitoring_team}
                        onChange={(event) =>
                            form.setData('monitoring_team', event.target.value)
                        }
                    />
                </Field>
                <Button
                    type="button"
                    variant="outline"
                    disabled={form.processing || !form.isDirty}
                    onClick={submit}
                >
                    Save Schedule
                </Button>
            </div>
        </section>
    );
}
