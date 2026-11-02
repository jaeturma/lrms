import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    CircleAlert,
    ClipboardCheck,
    Save,
    Send,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type {
    Checklist,
    VisitItem,
    VisitStage,
} from '@/components/valid-lr/visit-sections';
import {
    AralTable,
    changedItems,
    EquipmentTable,
    LearnersWithDisabilitiesTable,
    LearningResourceTable,
    PersonnelList,
    stageTone,
} from '@/components/valid-lr/visit-sections';
import { cn } from '@/lib/utils';

type Visit = {
    id: number;
    status: 'draft' | 'completed';
    stage: VisitStage;
    scheduled_at: string | null;
    school_representative: string | null;
    team_leader: { id: number; name: string } | null;
    school_submitted_at: string | null;
    school_submitted_by: string | null;
    updated_at: string | null;
    personnel: Record<string, string | null>;
    items: VisitItem[];
};

type Props = {
    available: boolean;
    visit: Visit | null;
    school: { beis_school_id: string; school_name: string } | null;
    checklist: Checklist | null;
    canEdit: boolean;
    profileHref: string;
    enrolmentHref: string;
    movOptions: string[];
    aralPrograms: Record<string, string>;
    personnelLabels: Record<string, string>;
    stages: Record<VisitStage, string>;
};

type Tab = 'lr' | 'sme_co' | 'sme_dl' | 'tvl' | 'im_lwd' | 'aral';

const formatSchedule = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('en-PH', {
              dateStyle: 'long',
              timeStyle: 'short',
          })
        : null;

export default function SchoolValidLr(props: Props) {
    if (!props.available || !props.visit || !props.checklist) {
        return (
            <>
                <Head title="VALID-LR Self-Assessment" />
                <main className="bg-background/40 p-3 md:p-4">
                    <PageHeader
                        icon={ClipboardCheck}
                        iconClassName="bg-teal-100 text-teal-600 dark:bg-teal-900/60 dark:text-teal-300"
                        title="VALID-LR Self-Assessment"
                        description="Your school is not in the VALID-LR monitoring list yet. Please coordinate with the Division LR team."
                    />
                </main>
            </>
        );
    }

    return (
        <SelfAssessment
            {...props}
            visit={props.visit}
            checklist={props.checklist}
        />
    );
}

function SelfAssessment({
    visit,
    school,
    checklist,
    canEdit,
    profileHref,
    enrolmentHref,
    movOptions,
    aralPrograms,
    personnelLabels,
    stages,
}: Props & { visit: Visit; checklist: Checklist }) {
    const [tab, setTab] = useState<Tab>('lr');
    const pageErrors = usePage().props.errors as Record<string, string>;
    const savedItems = useRef<VisitItem[]>(visit.items);

    const {
        data,
        setData,
        setDefaults,
        put,
        post,
        transform,
        processing,
        isDirty,
    } = useForm({
        school_representative: visit.school_representative ?? '',
        items: visit.items,
    });

    useEffect(() => {
        if (!isDirty || !canEdit) {
            return;
        }

        const removeRouterGuard = router.on('before', (event) => {
            if (
                event.detail.visit.method === 'get' &&
                !window.confirm(
                    'You have unsaved counts. Leave without saving?',
                )
            ) {
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
    }, [isDirty, canEdit]);

    const sectionCounts = useMemo(() => {
        const counts: Record<string, number> = {};

        data.items.forEach((item) => {
            counts[item.section] = (counts[item.section] ?? 0) + 1;
        });

        return counts;
    }, [data.items]);

    const countedNow = useMemo(
        () =>
            data.items.filter(
                (item) =>
                    ['lr', 'sme_co', 'sme_dl', 'tvl'].includes(item.section) &&
                    item.school_quantity !== null,
            ).length,
        [data.items],
    );

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

    const preparePayload = () =>
        transform((payload) => ({
            school_representative: payload.school_representative || null,
            items: changedItems(payload.items, savedItems.current).map(
                (item) => ({
                    id: item.id,
                    school_quantity: item.school_quantity,
                    reference_quantity: item.reference_quantity,
                    distributed_quantity: item.distributed_quantity,
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

    const save = () => {
        preparePayload();
        put('/school/valid-lr', {
            preserveScroll: true,
            onSuccess: () => {
                savedItems.current = data.items;
                setDefaults();
                toast.success('Your counts were saved.');
            },
            onError: (validationErrors) =>
                toast.error(
                    Object.values(validationErrors)[0] ??
                        'Some entries are invalid. Please review them.',
                ),
        });
    };

    const submit = () => {
        preparePayload();
        post('/school/valid-lr/submit', {
            preserveScroll: true,
            onSuccess: () => {
                savedItems.current = data.items;
                setDefaults();
                toast.success(
                    'Submitted for validation. You can still update your counts until the visit is completed.',
                );
            },
            onError: (validationErrors) => {
                // Counts were saved even when the checklist is incomplete.
                savedItems.current = data.items;
                setDefaults();
                toast.error(
                    (validationErrors as Record<string, string>).submit ??
                        Object.values(validationErrors)[0] ??
                        'Complete the checklist before submitting.',
                );
            },
        });
    };

    const tabs: Array<{ key: Tab; label: string }> = [
        { key: 'lr', label: 'Learning Resources' },
        { key: 'sme_co', label: 'SME Central Office' },
        { key: 'sme_dl', label: 'SME Downloaded' },
        { key: 'tvl', label: 'TVL' },
        { key: 'im_lwd', label: 'IMs for LWD' },
        { key: 'aral', label: 'ARAL' },
    ];

    const itemsFor = (section: VisitItem['section']) =>
        data.items.filter((item) => item.section === section);

    const schedule = formatSchedule(visit.scheduled_at);

    return (
        <>
            <Head title="VALID-LR Self-Assessment" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={ClipboardCheck}
                        iconClassName="bg-teal-100 text-teal-600 dark:bg-teal-900/60 dark:text-teal-300"
                        title="VALID-LR Self-Assessment"
                        description={`${school?.school_name ?? ''} · School ID ${school?.beis_school_id ?? ''}. Count what your school actually has before the monitoring team arrives; the team will validate your counts on site.`}
                        actions={
                            canEdit && (
                                <>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing || !isDirty}
                                        onClick={save}
                                    >
                                        <Save className="mr-1 h-4 w-4" />
                                        Save
                                    </Button>
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={submit}
                                    >
                                        <Send className="mr-1 h-4 w-4" />
                                        {visit.school_submitted_at
                                            ? 'Resubmit'
                                            : 'Submit for Validation'}
                                    </Button>
                                </>
                            )
                        }
                    >
                        <div className="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <StatusBadge tone={stageTone[visit.stage]}>
                                {stages[visit.stage]}
                            </StatusBadge>
                            {schedule && (
                                <span className="text-muted-foreground">
                                    Monitoring scheduled {schedule}
                                </span>
                            )}
                            {visit.team_leader && (
                                <span className="text-muted-foreground">
                                    · Team leader: {visit.team_leader.name}
                                </span>
                            )}
                            {canEdit && isDirty && (
                                <span className="font-medium text-amber-600 dark:text-amber-400">
                                    You have unsaved changes.
                                </span>
                            )}
                        </div>
                    </PageHeader>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-foreground">
                            Before the monitoring visit
                        </h2>
                        {visit.school_submitted_at ? (
                            <p className="text-sm text-muted-foreground">
                                Submitted on {visit.school_submitted_at}
                                {visit.school_submitted_by
                                    ? ` by ${visit.school_submitted_by}`
                                    : ''}
                                .{' '}
                                {canEdit
                                    ? 'You can still correct your counts until the team completes the visit.'
                                    : 'The monitoring visit is completed.'}
                            </p>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Complete all three, then submit. The team cannot
                                start validating until you do.
                            </p>
                        )}

                        <ul className="mt-3 grid gap-2 text-sm md:grid-cols-3">
                            <ChecklistItem done={checklist.profile_complete}>
                                School profile:{' '}
                                {checklist.missing_profile.length > 0
                                    ? `add ${checklist.missing_profile.join(', ')}`
                                    : 'complete'}
                                {' · '}
                                <Link
                                    href={profileHref}
                                    className="text-primary hover:underline"
                                >
                                    Update School Details
                                </Link>
                            </ChecklistItem>
                            <ChecklistItem done={checklist.enrolment_complete}>
                                Enrolment
                                {checklist.school_year
                                    ? ` for SY ${checklist.school_year}`
                                    : ''}
                                :{' '}
                                {checklist.enrolment_complete
                                    ? 'encoded'
                                    : 'not encoded yet'}
                                {' · '}
                                <Link
                                    href={enrolmentHref}
                                    className="text-primary hover:underline"
                                >
                                    Enrollment
                                </Link>
                            </ChecklistItem>
                            <ChecklistItem
                                done={countedNow === checklist.items_total}
                            >
                                Your counts: {countedNow.toLocaleString()} of{' '}
                                {checklist.items_total.toLocaleString()} LR, SME
                                and TVL items (enter 0 if none on hand)
                            </ChecklistItem>
                        </ul>
                        <InputError message={pageErrors.submit} />

                        <div className="mt-4 grid gap-4 border-t border-border pt-4 lg:grid-cols-[2fr_1fr]">
                            <div>
                                <h3 className="mb-2 text-sm font-semibold text-foreground">
                                    School Personnel
                                </h3>
                                <PersonnelList
                                    personnel={visit.personnel}
                                    labels={personnelLabels}
                                />
                            </div>
                            <label className="grid content-start gap-1 text-sm">
                                <span className="font-medium text-foreground">
                                    School representative during the visit
                                </span>
                                <Input
                                    value={data.school_representative}
                                    disabled={!canEdit}
                                    onChange={(event) =>
                                        setData(
                                            'school_representative',
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                        </div>
                    </section>

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
                                    {item.label} ({sectionCounts[item.key] ?? 0}
                                    )
                                </button>
                            ))}
                        </div>

                        <fieldset disabled={!canEdit} className="min-w-0">
                            {tab === 'lr' && (
                                <LearningResourceTable
                                    items={itemsFor('lr')}
                                    movOptions={movOptions}
                                    mode="school"
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
                                    mode="school"
                                    onChange={updateItem}
                                />
                            )}
                            {tab === 'im_lwd' && (
                                <LearnersWithDisabilitiesTable
                                    items={itemsFor('im_lwd')}
                                    mode="school"
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
                        </fieldset>
                    </section>
                </div>
            </main>
        </>
    );
}

function ChecklistItem({
    done,
    children,
}: {
    done: boolean;
    children: React.ReactNode;
}) {
    return (
        <li
            className={cn(
                'flex items-start gap-2 rounded-xl border p-3',
                done
                    ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30'
                    : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30',
            )}
        >
            {done ? (
                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
            ) : (
                <CircleAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
            )}
            <span className="text-foreground">{children}</span>
        </li>
    );
}
