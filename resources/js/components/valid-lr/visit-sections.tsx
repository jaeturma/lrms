import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { cn } from '@/lib/utils';

export type VisitSection =
    | 'lr'
    | 'sme_co'
    | 'sme_dl'
    | 'tvl'
    | 'im_lwd'
    | 'aral';

export type VisitItem = {
    id: number;
    section: VisitSection;
    group_label: string | null;
    label: string;
    title: string | null;
    publisher: string | null;
    unit: string | null;
    procurement_source: string | null;
    reference_quantity: number | null;
    lrmis_quantity: number | null;
    lrmis_original_quantity?: number | null;
    lrmis_adjusted_by?: string | null;
    lrmis_adjusted_at?: string | null;
    lrms_quantity: number | null;
    distributed_quantity: number | null;
    school_quantity: number | null;
    actual_quantity: number | null;
    enrolment: number | null;
    year_received: string | null;
    mov: string[];
    extra: Record<string, string | number | null>;
    remarks_repairs: string | null;
    remarks_missing: string | null;
    remarks_others: string | null;
};

export type VisitStage =
    | 'awaiting_school'
    | 'ready'
    | 'validating'
    | 'completed';

export type Checklist = {
    in_lrms: boolean;
    profile_complete: boolean;
    missing_profile: string[];
    enrolment_complete: boolean;
    school_year: string | null;
    items_total: number;
    items_counted: number;
    counts_complete: boolean;
    ready: boolean;
};

export type ItemChange = <K extends keyof VisitItem>(
    id: number,
    field: K,
    value: VisitItem[K],
) => void;

/**
 * `team` edits the validated count and shows the school's own count beside
 * it; `school` edits the school's count and shows the validated one.
 */
export type CountMode = 'team' | 'school';

export const fieldClass =
    'h-8 w-full rounded-md border border-input bg-background px-2 text-sm text-foreground disabled:cursor-not-allowed disabled:opacity-70';

export const textareaClass =
    'min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground disabled:cursor-not-allowed disabled:opacity-70';

export const stageTone: Record<
    VisitStage,
    'warning' | 'info' | 'accent' | 'success'
> = {
    awaiting_school: 'warning',
    ready: 'info',
    validating: 'accent',
    completed: 'success',
};

const toNumber = (value: string): number | null =>
    value === '' ? null : Math.max(0, Math.floor(Number(value)));

export function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="grid gap-1 text-sm">
            <span className="font-medium text-foreground">{label}</span>
            {children}
            <InputError message={error} />
        </label>
    );
}

export function QuantityInput({
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

function ReadOnlyCount({ value }: { value: number | null }) {
    return (
        <span className="block text-right text-muted-foreground tabular-nums">
            {value?.toLocaleString() ?? '—'}
        </span>
    );
}

/**
 * The editable count cell for the current mode plus, for the team, the
 * school's count and a shortcut to accept it.
 */
function CountCells({
    item,
    mode,
    schoolCount,
    onChange,
    expected,
    expectedLabel,
}: {
    item: VisitItem;
    mode: CountMode;
    schoolCount: number | null;
    onChange: ItemChange;
    expected: number | null;
    expectedLabel: string;
}) {
    const label = `${item.group_label ?? ''} ${item.label}`.trim();

    if (mode === 'school') {
        return (
            <>
                <td className="w-28 px-2 py-1.5">
                    <QuantityInput
                        label={`${label} your count`}
                        value={item.school_quantity}
                        onChange={(value) =>
                            onChange(item.id, 'school_quantity', value)
                        }
                    />
                    {item.school_quantity !== null &&
                        expected !== null &&
                        item.school_quantity !== expected && (
                            <span className="text-xs text-amber-600 dark:text-amber-400">
                                Differs from {expectedLabel}
                            </span>
                        )}
                </td>
                <td className="w-24 px-2 py-1.5">
                    <ReadOnlyCount value={item.actual_quantity} />
                </td>
            </>
        );
    }

    const differsFromSchool =
        item.actual_quantity !== null &&
        schoolCount !== null &&
        item.actual_quantity !== schoolCount;

    return (
        <>
            <td className="w-24 px-2 py-1.5">
                <ReadOnlyCount value={schoolCount} />
            </td>
            <td className="w-32 px-2 py-1.5">
                <QuantityInput
                    label={`${label} validated count`}
                    value={item.actual_quantity}
                    onChange={(value) =>
                        onChange(item.id, 'actual_quantity', value)
                    }
                />
                {item.actual_quantity === null && schoolCount !== null && (
                    <button
                        type="button"
                        className="text-xs text-primary hover:underline disabled:hidden"
                        onClick={() =>
                            onChange(item.id, 'actual_quantity', schoolCount)
                        }
                    >
                        Confirm {schoolCount.toLocaleString()}
                    </button>
                )}
                {differsFromSchool && (
                    <span className="block text-xs text-amber-600 dark:text-amber-400">
                        Differs from school count
                    </span>
                )}
                {!differsFromSchool &&
                    item.actual_quantity !== null &&
                    expected !== null &&
                    item.actual_quantity !== expected && (
                        <span className="block text-xs text-amber-600 dark:text-amber-400">
                            Differs from {expectedLabel}
                        </span>
                    )}
            </td>
        </>
    );
}

function CountHeadings({ mode }: { mode: CountMode }) {
    return mode === 'school' ? (
        <>
            <th className="px-2 py-2">Your Count</th>
            <th className="px-2 py-2 text-right">Validated</th>
        </>
    ) : (
        <>
            <th className="px-2 py-2 text-right">School Count</th>
            <th className="px-2 py-2">Validated Count</th>
        </>
    );
}

export function LearningResourceTable({
    items,
    movOptions,
    mode,
    schoolCounts,
    onChange,
}: {
    items: VisitItem[];
    movOptions: string[];
    mode: CountMode;
    schoolCounts?: Record<number, number | null>;
    onChange: ItemChange;
}) {
    if (items.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No learning resource allocation was found for this school when
                monitoring started.
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
                        <CountHeadings mode={mode} />
                        <th className="px-2 py-2">Enrolment SY 2026–2027</th>
                        <th className="px-2 py-2">MOV Presented</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item) => (
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
                                {item.lrmis_quantity?.toLocaleString() ?? '-'}
                                {item.lrmis_adjusted_at && (
                                    <span
                                        className="block text-xs text-sky-600 dark:text-sky-400"
                                        title={`Updated by ${item.lrmis_adjusted_by ?? 'someone'} on ${item.lrmis_adjusted_at}`}
                                    >
                                        updated (was{' '}
                                        {item.lrmis_original_quantity?.toLocaleString() ??
                                            '-'}
                                        )
                                    </span>
                                )}
                            </td>
                            <td className="px-2 py-1.5 text-right tabular-nums">
                                {item.lrms_quantity?.toLocaleString() ?? '-'}
                            </td>
                            <CountCells
                                item={item}
                                mode={mode}
                                schoolCount={
                                    schoolCounts?.[item.id] ??
                                    item.school_quantity
                                }
                                expected={item.lrmis_quantity}
                                expectedLabel="LRMIS"
                                onChange={onChange}
                            />
                            <td className="w-28 px-2 py-1.5">
                                <QuantityInput
                                    label={`${item.group_label} ${item.label} enrolment`}
                                    value={item.enrolment}
                                    onChange={(value) =>
                                        onChange(item.id, 'enrolment', value)
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
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function EquipmentTable({
    items,
    groupHeading,
    isTvl,
    mode,
    schoolCounts,
    onChange,
}: {
    items: VisitItem[];
    groupHeading: string;
    isTvl: boolean;
    mode: CountMode;
    schoolCounts?: Record<number, number | null>;
    onChange: ItemChange;
}) {
    if (items.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No delivered package was on record for this school when
                monitoring started.
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
                        <CountHeadings mode={mode} />
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
                            <CountCells
                                item={item}
                                mode={mode}
                                schoolCount={
                                    schoolCounts?.[item.id] ??
                                    item.school_quantity
                                }
                                expected={item.reference_quantity}
                                expectedLabel="package quantity"
                                onChange={onChange}
                            />
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

export function LearnersWithDisabilitiesTable({
    items,
    mode,
    onChange,
}: {
    items: VisitItem[];
    mode: CountMode;
    onChange: ItemChange;
}) {
    const countField =
        mode === 'school' ? 'school_quantity' : 'actual_quantity';

    return (
        <div className="overflow-x-auto rounded-xl border border-border">
            <table className="min-w-full text-sm">
                <thead className="bg-muted text-left text-foreground">
                    <tr>
                        <th className="px-3 py-2">Learning Resource</th>
                        <th className="px-3 py-2">Allocated Quantity</th>
                        {mode === 'team' && (
                            <th className="px-3 py-2 text-right">
                                School Count
                            </th>
                        )}
                        <th className="px-3 py-2">
                            {mode === 'school'
                                ? 'Your Count (delivered)'
                                : 'Validated (delivered)'}
                        </th>
                        <th className="px-3 py-2">
                            Remarks (usability, access, storage, loss and
                            damage)
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item) => (
                        <tr key={item.id} className="border-t border-border">
                            <td className="px-3 py-1.5 text-foreground">
                                {item.label}
                            </td>
                            <td className="w-32 px-3 py-1.5">
                                <QuantityInput
                                    label={`${item.label} allocated`}
                                    value={item.reference_quantity}
                                    onChange={(value) =>
                                        onChange(
                                            item.id,
                                            'reference_quantity',
                                            value,
                                        )
                                    }
                                />
                            </td>
                            {mode === 'team' && (
                                <td className="w-28 px-3 py-1.5">
                                    <ReadOnlyCount
                                        value={item.school_quantity}
                                    />
                                </td>
                            )}
                            <td className="w-32 px-3 py-1.5">
                                <QuantityInput
                                    label={`${item.label} delivered`}
                                    value={item[countField]}
                                    onChange={(value) =>
                                        onChange(item.id, countField, value)
                                    }
                                />
                            </td>
                            <td className="px-3 py-1.5">
                                <input
                                    className={fieldClass}
                                    aria-label={`${item.label} remarks`}
                                    value={item.remarks_others ?? ''}
                                    onChange={(event) =>
                                        onChange(
                                            item.id,
                                            'remarks_others',
                                            event.target.value,
                                        )
                                    }
                                />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function AralTable({
    items,
    aralPrograms,
    onChange,
}: {
    items: VisitItem[];
    aralPrograms: Record<string, string>;
    onChange: ItemChange;
}) {
    return (
        <div className="overflow-x-auto rounded-xl border border-border">
            <table className="min-w-full text-sm">
                <thead className="bg-muted text-left text-foreground">
                    <tr>
                        <th className="px-3 py-2">Indicator</th>
                        <th className="px-3 py-2">ARAL Learning Resource</th>
                        {Object.values(aralPrograms).map((program) => (
                            <th key={program} className="px-3 py-2">
                                {program}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {items.map((item) => (
                        <tr key={item.id} className="border-t border-border">
                            <td className="px-3 py-1.5 text-muted-foreground">
                                {item.group_label}
                            </td>
                            <td className="px-3 py-1.5 text-foreground">
                                {item.label}
                            </td>
                            {Object.keys(aralPrograms).map((programKey) => (
                                <td
                                    key={programKey}
                                    className="w-36 px-3 py-1.5"
                                >
                                    <QuantityInput
                                        label={`${item.group_label} ${item.label} ${aralPrograms[programKey]}`}
                                        value={
                                            (item.extra[programKey] as
                                                | number
                                                | null) ?? null
                                        }
                                        onChange={(value) =>
                                            onChange(item.id, 'extra', {
                                                ...item.extra,
                                                [programKey]: value,
                                            })
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

/**
 * Items whose editable fields differ from what was loaded, so a save only
 * sends what this user changed and never overwrites the other side's edits.
 */
export function changedItems<T extends { id: number }>(
    current: T[],
    original: T[],
): T[] {
    const originalById = new Map(
        original.map((item) => [item.id, JSON.stringify(item)]),
    );

    return current.filter(
        (item) => originalById.get(item.id) !== JSON.stringify(item),
    );
}

export function PersonnelList({
    personnel,
    labels,
}: {
    personnel: Record<string, string | null>;
    labels: Record<string, string>;
}) {
    return (
        <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
            {Object.entries(labels).map(([field, label]) => (
                <div key={field}>
                    <dt className="text-xs text-muted-foreground">{label}</dt>
                    <dd
                        className={cn(
                            'font-medium',
                            personnel[field]
                                ? 'text-foreground'
                                : 'text-amber-600 dark:text-amber-400',
                        )}
                    >
                        {personnel[field] ?? 'Not set'}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
