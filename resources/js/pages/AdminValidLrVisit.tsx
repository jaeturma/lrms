import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ClipboardCheck, Save, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type VisitItem = {
    id: number;
    section: 'lr' | 'sme_co' | 'sme_dl' | 'tvl' | 'im_lwd' | 'aral';
    group_label: string | null;
    label: string;
    title: string | null;
    publisher: string | null;
    unit: string | null;
    procurement_source: string | null;
    reference_quantity: number | null;
    lrmis_quantity: number | null;
    lrms_quantity: number | null;
    distributed_quantity: number | null;
    actual_quantity: number | null;
    enrolment: number | null;
    year_received: string | null;
    mov: string[];
    extra: Record<string, string | number | null>;
    remarks_repairs: string | null;
    remarks_missing: string | null;
    remarks_others: string | null;
};

type Indicator = { answer: 'yes' | 'no' | null; remarks: string | null };

type Visit = {
    id: number;
    status: 'draft' | 'completed';
    visited_at: string | null;
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
    indicatorQuestions: Record<string, string>;
    movOptions: string[];
    aralPrograms: Record<string, string>;
    deliveryStatuses: string[];
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

const fieldClass =
    'h-8 w-full rounded-md border border-input bg-background px-2 text-sm text-foreground';

const textareaClass =
    'min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground';

const toNumber = (value: string): number | null =>
    value === '' ? null : Math.max(0, Math.floor(Number(value)));

export default function AdminValidLrVisit({
    visit,
    school,
    indicatorQuestions,
    movOptions,
    aralPrograms,
    deliveryStatuses,
}: Props) {
    const [tab, setTab] = useState<Tab>('profile');

    const { data, setData, put, transform, processing, errors, isDirty } =
        useForm({
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
            items: payload.items.map((item) => ({
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
            })),
        }));

        put(`/app/admin/valid-lr/visits/${visit.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Monitoring visit saved.'),
            onError: () =>
                toast.error('Some fields are invalid. Please review them.'),
        });
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
                            visit.status === 'completed'
                                ? 'Completed'
                                : 'Draft',
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
                                    onClick={deleteVisit}
                                >
                                    <Trash2 className="mr-1 h-4 w-4" />
                                    Delete
                                </Button>
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
                        }
                    >
                        {isDirty && (
                            <p className="mt-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                You have unsaved changes.
                            </p>
                        )}
                    </PageHeader>

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
                                            value={data.school_representative}
                                            onChange={(event) =>
                                                setData(
                                                    'school_representative',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>
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
                                                        ['yes', 'no'] as const
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
                                                                data.indicators[
                                                                    key
                                                                ]?.remarks ?? ''
                                                            }
                                                            onChange={(event) =>
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
                                items={itemsFor('lr')}
                                movOptions={movOptions}
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
                                onChange={updateItem}
                            />
                        )}

                        {tab === 'im_lwd' && (
                            <div className="overflow-x-auto rounded-xl border border-border">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="px-3 py-2">
                                                Learning Resource
                                            </th>
                                            <th className="px-3 py-2">
                                                Allocated Quantity
                                            </th>
                                            <th className="px-3 py-2">
                                                Delivered Quantity
                                            </th>
                                            <th className="px-3 py-2">
                                                Remarks (usability, access,
                                                storage, loss and damage)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {itemsFor('im_lwd').map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-t border-border"
                                            >
                                                <td className="px-3 py-1.5 text-foreground">
                                                    {item.label}
                                                </td>
                                                <td className="w-32 px-3 py-1.5">
                                                    <QuantityInput
                                                        label={`${item.label} allocated`}
                                                        value={
                                                            item.reference_quantity
                                                        }
                                                        onChange={(value) =>
                                                            updateItem(
                                                                item.id,
                                                                'reference_quantity',
                                                                value,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="w-32 px-3 py-1.5">
                                                    <QuantityInput
                                                        label={`${item.label} delivered`}
                                                        value={
                                                            item.actual_quantity
                                                        }
                                                        onChange={(value) =>
                                                            updateItem(
                                                                item.id,
                                                                'actual_quantity',
                                                                value,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-1.5">
                                                    <input
                                                        className={fieldClass}
                                                        aria-label={`${item.label} remarks`}
                                                        value={
                                                            item.remarks_others ??
                                                            ''
                                                        }
                                                        onChange={(event) =>
                                                            updateItem(
                                                                item.id,
                                                                'remarks_others',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {tab === 'aral' && (
                            <div className="overflow-x-auto rounded-xl border border-border">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="px-3 py-2">
                                                Indicator
                                            </th>
                                            <th className="px-3 py-2">
                                                ARAL Learning Resource
                                            </th>
                                            {Object.values(aralPrograms).map(
                                                (program) => (
                                                    <th
                                                        key={program}
                                                        className="px-3 py-2"
                                                    >
                                                        {program}
                                                    </th>
                                                ),
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {itemsFor('aral').map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-t border-border"
                                            >
                                                <td className="px-3 py-1.5 text-muted-foreground">
                                                    {item.group_label}
                                                </td>
                                                <td className="px-3 py-1.5 text-foreground">
                                                    {item.label}
                                                </td>
                                                {Object.keys(aralPrograms).map(
                                                    (programKey) => (
                                                        <td
                                                            key={programKey}
                                                            className="w-36 px-3 py-1.5"
                                                        >
                                                            <QuantityInput
                                                                label={`${item.group_label} ${item.label} ${aralPrograms[programKey]}`}
                                                                value={
                                                                    (item.extra[
                                                                        programKey
                                                                    ] as
                                                                        | number
                                                                        | null) ??
                                                                    null
                                                                }
                                                                onChange={(
                                                                    value,
                                                                ) =>
                                                                    updateItem(
                                                                        item.id,
                                                                        'extra',
                                                                        {
                                                                            ...item.extra,
                                                                            [programKey]:
                                                                                value,
                                                                        },
                                                                    )
                                                                }
                                                            />
                                                        </td>
                                                    ),
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
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
                    </section>
                </div>
            </main>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="grid gap-1 text-sm">
            <span className="font-medium text-foreground">{label}</span>
            {children}
            <InputError message={error} />
        </label>
    );
}

function QuantityInput({
    label,
    value,
    onChange,
}: {
    label: string;
    value: number | null;
    onChange: (value: number | null) => void;
}) {
    return (
        <input
            type="number"
            min={0}
            inputMode="numeric"
            aria-label={label}
            className={cn(fieldClass, 'text-right tabular-nums')}
            value={value ?? ''}
            onChange={(event) => onChange(toNumber(event.target.value))}
        />
    );
}

type ItemChange = <K extends keyof VisitItem>(
    id: number,
    field: K,
    value: VisitItem[K],
) => void;

function LearningResourceTable({
    items,
    movOptions,
    onChange,
}: {
    items: VisitItem[];
    movOptions: string[];
    onChange: ItemChange;
}) {
    if (items.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No learning resource allocation was found for this school when
                the visit started.
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-xl border border-border">
            <table className="min-w-full text-sm">
                <thead className="bg-muted text-left align-bottom text-foreground">
                    <tr>
                        <th className="px-2 py-2">Grade</th>
                        <th className="px-2 py-2">Learning Area / Title</th>
                        <th className="px-2 py-2 text-right">
                            Allocated (TX + TM)
                        </th>
                        <th className="px-2 py-2">Distributed to School</th>
                        <th className="px-2 py-2 text-right">LRMIS Encoded</th>
                        <th className="px-2 py-2 text-right">LRMS Recorded</th>
                        <th className="px-2 py-2">Actual Counted</th>
                        <th className="px-2 py-2">Enrolment SY 2026–2027</th>
                        <th className="px-2 py-2">MOV Presented</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item) => {
                        const mismatch =
                            item.actual_quantity !== null &&
                            item.lrmis_quantity !== null &&
                            item.actual_quantity !== item.lrmis_quantity;

                        return (
                            <tr
                                key={item.id}
                                className="border-t border-border align-top"
                            >
                                <td className="px-2 py-1.5 whitespace-nowrap text-muted-foreground">
                                    {item.group_label}
                                </td>
                                <td className="min-w-56 px-2 py-1.5">
                                    <div className="font-medium text-foreground">
                                        {item.label}
                                    </div>
                                    {(item.title || item.publisher) && (
                                        <div className="text-xs text-muted-foreground">
                                            {[item.title, item.publisher]
                                                .filter(Boolean)
                                                .join(' — ')}
                                        </div>
                                    )}
                                </td>
                                <td className="px-2 py-1.5 text-right tabular-nums">
                                    {item.reference_quantity?.toLocaleString() ??
                                        '-'}
                                </td>
                                <td className="w-28 px-2 py-1.5">
                                    <QuantityInput
                                        label={`${item.group_label} ${item.label} distributed`}
                                        value={item.distributed_quantity}
                                        onChange={(value) =>
                                            onChange(
                                                item.id,
                                                'distributed_quantity',
                                                value,
                                            )
                                        }
                                    />
                                </td>
                                <td className="px-2 py-1.5 text-right tabular-nums">
                                    {item.lrmis_quantity?.toLocaleString() ??
                                        '-'}
                                </td>
                                <td className="px-2 py-1.5 text-right tabular-nums">
                                    {item.lrms_quantity?.toLocaleString() ??
                                        '-'}
                                </td>
                                <td className="w-28 px-2 py-1.5">
                                    <QuantityInput
                                        label={`${item.group_label} ${item.label} actual count`}
                                        value={item.actual_quantity}
                                        onChange={(value) =>
                                            onChange(
                                                item.id,
                                                'actual_quantity',
                                                value,
                                            )
                                        }
                                    />
                                    {mismatch && (
                                        <span className="text-xs text-amber-600 dark:text-amber-400">
                                            Differs from LRMIS
                                        </span>
                                    )}
                                </td>
                                <td className="w-28 px-2 py-1.5">
                                    <QuantityInput
                                        label={`${item.group_label} ${item.label} enrolment`}
                                        value={item.enrolment}
                                        onChange={(value) =>
                                            onChange(
                                                item.id,
                                                'enrolment',
                                                value,
                                            )
                                        }
                                    />
                                </td>
                                <td className="min-w-52 px-2 py-1.5">
                                    <div className="grid gap-0.5">
                                        {movOptions.map((option) => (
                                            <label
                                                key={option}
                                                className="flex items-center gap-1.5 text-xs text-muted-foreground"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={item.mov.includes(
                                                        option,
                                                    )}
                                                    onChange={(event) =>
                                                        onChange(
                                                            item.id,
                                                            'mov',
                                                            event.target.checked
                                                                ? [
                                                                      ...item.mov,
                                                                      option,
                                                                  ]
                                                                : item.mov.filter(
                                                                      (value) =>
                                                                          value !==
                                                                          option,
                                                                  ),
                                                        )
                                                    }
                                                />
                                                {option}
                                            </label>
                                        ))}
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

function EquipmentTable({
    items,
    groupHeading,
    isTvl,
    onChange,
}: {
    items: VisitItem[];
    groupHeading: string;
    isTvl: boolean;
    onChange: ItemChange;
}) {
    if (items.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No delivered package was on record for this school when the
                visit started.
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-xl border border-border">
            <table className="min-w-full text-sm">
                <thead className="bg-muted text-left align-bottom text-foreground">
                    <tr>
                        <th className="px-2 py-2">{groupHeading}</th>
                        <th className="px-2 py-2">Item</th>
                        <th className="px-2 py-2">
                            {isTvl ? 'Procured By' : 'Unit / Key Stage'}
                        </th>
                        <th className="px-2 py-2 text-right">
                            {isTvl ? 'Qty per Specialization' : 'Qty'}
                        </th>
                        <th className="px-2 py-2">Actual Count</th>
                        <th className="px-2 py-2">Year Received</th>
                        <th className="px-2 py-2">Repairs</th>
                        <th className="px-2 py-2">Missing</th>
                        <th className="px-2 py-2">Others</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item) => (
                        <tr
                            key={item.id}
                            className="border-t border-border align-top"
                        >
                            <td className="max-w-56 px-2 py-1.5 text-xs text-muted-foreground">
                                {item.group_label ?? '-'}
                            </td>
                            <td className="min-w-56 px-2 py-1.5 text-foreground">
                                {item.label}
                            </td>
                            <td className="px-2 py-1.5 text-xs whitespace-nowrap text-muted-foreground">
                                {isTvl
                                    ? item.procurement_source ===
                                      'central_office'
                                        ? 'Central Office'
                                        : item.procurement_source ===
                                            'downloaded'
                                          ? 'RO/SDO'
                                          : '-'
                                    : [item.unit, item.extra.key_stage]
                                          .filter(Boolean)
                                          .join(' · ')}
                            </td>
                            <td className="px-2 py-1.5 text-right tabular-nums">
                                {item.reference_quantity?.toLocaleString() ??
                                    '-'}
                            </td>
                            <td className="w-24 px-2 py-1.5">
                                <QuantityInput
                                    label={`${item.label} actual count`}
                                    value={item.actual_quantity}
                                    onChange={(value) =>
                                        onChange(
                                            item.id,
                                            'actual_quantity',
                                            value,
                                        )
                                    }
                                />
                            </td>
                            <td className="w-28 px-2 py-1.5">
                                <input
                                    className={fieldClass}
                                    aria-label={`${item.label} year received`}
                                    value={item.year_received ?? ''}
                                    onChange={(event) =>
                                        onChange(
                                            item.id,
                                            'year_received',
                                            event.target.value,
                                        )
                                    }
                                />
                            </td>
                            {(
                                [
                                    'remarks_repairs',
                                    'remarks_missing',
                                    'remarks_others',
                                ] as const
                            ).map((field) => (
                                <td key={field} className="w-36 px-2 py-1.5">
                                    <input
                                        className={fieldClass}
                                        aria-label={`${item.label} ${field.replace('remarks_', '')}`}
                                        value={item[field] ?? ''}
                                        onChange={(event) =>
                                            onChange(
                                                item.id,
                                                field,
                                                event.target.value,
                                            )
                                        }
                                    />
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
