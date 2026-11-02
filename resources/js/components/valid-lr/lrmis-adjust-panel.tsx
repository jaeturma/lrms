import { useForm } from '@inertiajs/react';
import { DatabaseZap } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import type { VisitItem } from '@/components/valid-lr/visit-sections';
import { QuantityInput } from '@/components/valid-lr/visit-sections';

/**
 * Lets the LR manager, ITO, superadmin and system admin correct a visit's
 * LRMIS-encoded counts so they match the school's LRMIS records.
 */
export function LrmisAdjustPanel({
    visitId,
    items,
}: {
    visitId: number;
    items: VisitItem[];
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        items: items.map((item) => ({
            id: item.id,
            lrmis_quantity: item.lrmis_quantity,
        })),
    });

    const quantityFor = (id: number) =>
        form.data.items.find((item) => item.id === id)?.lrmis_quantity ?? null;

    const setQuantity = (id: number, value: number | null) =>
        form.setData(
            'items',
            form.data.items.map((item) =>
                item.id === id ? { ...item, lrmis_quantity: value } : item,
            ),
        );

    const save = () => {
        const original = new Map(
            items.map((item) => [item.id, item.lrmis_quantity]),
        );

        form.transform((payload) => ({
            items: payload.items.filter(
                (item) => original.get(item.id) !== item.lrmis_quantity,
            ),
        }));
        form.put(`/app/admin/valid-lr/visits/${visitId}/lrmis`, {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                toast.success('LRMIS counts updated.');
            },
            onError: () => toast.error('Some LRMIS counts are invalid.'),
        });
    };

    return (
        <div className="mb-4 rounded-xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="flex items-center gap-1.5 text-sm font-semibold text-foreground">
                        <DatabaseZap className="size-4 text-sky-600" />
                        Update LRMIS counts
                    </p>
                    <p className="text-xs text-muted-foreground">
                        Correct the LRMIS-encoded totals to match the school's
                        LRMIS records. The imported value and who changed it are
                        kept.
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setOpen(!open)}
                    >
                        {open ? 'Close' : 'Edit LRMIS counts'}
                    </Button>
                    {open && (
                        <Button
                            type="button"
                            size="sm"
                            disabled={form.processing || !form.isDirty}
                            onClick={save}
                        >
                            Save LRMIS counts
                        </Button>
                    )}
                </div>
            </div>

            {open && (
                <div className="mt-3 max-h-[28rem] overflow-auto rounded-lg border border-border bg-card">
                    <table className="min-w-full text-sm">
                        <thead className="sticky top-0 bg-muted text-left text-foreground">
                            <tr>
                                <th className="px-2 py-2">Grade</th>
                                <th className="px-2 py-2">Learning Area</th>
                                <th className="px-2 py-2">LRMIS Encoded</th>
                                <th className="px-2 py-2 text-right">
                                    School Count
                                </th>
                                <th className="px-2 py-2 text-right">
                                    Validated
                                </th>
                                <th className="px-2 py-2">Last change</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item) => (
                                <tr
                                    key={item.id}
                                    className="border-t border-border"
                                >
                                    <td className="px-2 py-1.5 whitespace-nowrap text-muted-foreground">
                                        {item.group_label}
                                    </td>
                                    <td className="px-2 py-1.5 text-foreground">
                                        {item.label}
                                    </td>
                                    <td className="w-32 px-2 py-1.5">
                                        <QuantityInput
                                            label={`${item.group_label} ${item.label} LRMIS encoded`}
                                            value={quantityFor(item.id)}
                                            onChange={(value) =>
                                                setQuantity(item.id, value)
                                            }
                                        />
                                    </td>
                                    <td className="px-2 py-1.5 text-right tabular-nums">
                                        {item.school_quantity?.toLocaleString() ??
                                            '—'}
                                    </td>
                                    <td className="px-2 py-1.5 text-right tabular-nums">
                                        {item.actual_quantity?.toLocaleString() ??
                                            '—'}
                                    </td>
                                    <td className="px-2 py-1.5 text-xs text-muted-foreground">
                                        {item.lrmis_adjusted_at
                                            ? `${item.lrmis_adjusted_by ?? 'Someone'} · ${item.lrmis_adjusted_at} (imported ${item.lrmis_original_quantity?.toLocaleString() ?? 'none'})`
                                            : 'Imported value'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
