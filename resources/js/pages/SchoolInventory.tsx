import { Head, useForm } from '@inertiajs/react';
import { ArrowLeftRight, History, Loader2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { EmptyTableRow } from '@/components/empty-state';
import { RowActions } from '@/components/row-actions';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type InventoryCounts = {
    available: number;
    issued: number;
    borrowed: number;
    damaged: number;
    lost: number;
    condemned: number;
};

type InventoryResource = {
    id: number;
    title: string | null;
    resource_type: string | null;
    publisher: string;
    inventory: InventoryCounts;
};

type Movement = {
    id: number;
    learning_resource_id: number;
    resource_title: string | null;
    type: string;
    quantity: number;
    from_status: string | null;
    to_status: string | null;
    notes: string | null;
    recorded_by: string | null;
    created_at: string | null;
};

type Props = {
    resources: InventoryResource[];
    movements: Movement[];
    transitionSources: Record<string, string[]>;
};

const MOVEMENT_LABELS: Record<string, string> = {
    issued: 'Issue',
    borrowed: 'Borrow',
    returned: 'Return',
    damaged: 'Mark Damaged',
    lost: 'Mark Lost',
    condemned: 'Condemn',
};

const STATUS_LABELS: Record<string, string> = {
    available: 'Available',
    issued: 'Issued',
    borrowed: 'Borrowed',
    damaged: 'Damaged',
    lost: 'Lost',
    condemned: 'Condemned',
};

export default function SchoolInventory({ resources, movements, transitionSources }: Props) {
    const [movementResource, setMovementResource] = useState<InventoryResource | null>(null);
    const [historyResourceId, setHistoryResourceId] = useState<number | null>(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        type: 'issued',
        quantity: 1,
        from_status: '',
        notes: '',
    });

    const sourceOptions = transitionSources[data.type] ?? [];

    const openMovementDialog = (resource: InventoryResource) => {
        reset();
        clearErrors();
        setMovementResource(resource);
    };

    const submitMovement = () => {
        if (!movementResource) {
            return;
        }

        post(`/school/inventory/${movementResource.id}/movements`, {
            preserveScroll: true,
            onSuccess: () => {
                setMovementResource(null);
                toast.success('Movement recorded.');
            },
        });
    };

    const visibleMovements = useMemo(
        () =>
            historyResourceId === null
                ? movements
                : movements.filter((movement) => movement.learning_resource_id === historyResourceId),
        [movements, historyResourceId],
    );

    const historyResource = resources.find((resource) => resource.id === historyResourceId);

    return (
        <>
            <Head title="Inventory" />

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4">
                        <h2 className="text-lg font-semibold text-foreground">Learning Resource Inventory</h2>
                        <p className="text-sm text-muted-foreground">
                            Track the status of each learning resource. Record issuances, borrowings, returns, damage,
                            losses, and condemnation — every movement is kept in the history below.
                        </p>
                    </div>

                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted text-left text-foreground">
                                <tr>
                                    <th className="px-3 py-2">Title</th>
                                    <th className="px-3 py-2">Type</th>
                                    <th className="px-3 py-2 text-right">Available</th>
                                    <th className="px-3 py-2 text-right">Issued</th>
                                    <th className="px-3 py-2 text-right">Borrowed</th>
                                    <th className="px-3 py-2 text-right">Damaged</th>
                                    <th className="px-3 py-2 text-right">Lost</th>
                                    <th className="px-3 py-2 text-right">Condemned</th>
                                    <th className="px-3 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {resources.length === 0 && (
                                    <EmptyTableRow
                                        colSpan={9}
                                        message="No learning resources encoded yet. Add resources on the Learning Resources page first."
                                    />
                                )}
                                {resources.map((resource) => (
                                    <tr key={resource.id} className="border-t border-border">
                                        <td className="px-3 py-2 font-medium text-foreground">{resource.title ?? '-'}</td>
                                        <td className="px-3 py-2 text-muted-foreground">{resource.resource_type ?? '-'}</td>
                                        <td className="px-3 py-2 text-right">
                                            <StatusBadge tone="success">{resource.inventory.available}</StatusBadge>
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <StatusBadge tone="info">{resource.inventory.issued}</StatusBadge>
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <StatusBadge tone="accent">{resource.inventory.borrowed}</StatusBadge>
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <StatusBadge tone="warning">{resource.inventory.damaged}</StatusBadge>
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <StatusBadge tone="danger">{resource.inventory.lost}</StatusBadge>
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <StatusBadge tone="neutral">{resource.inventory.condemned}</StatusBadge>
                                        </td>
                                        <td className="px-3 py-2">
                                            <RowActions
                                                label={`Actions for ${resource.title ?? 'resource'}`}
                                                actions={[
                                                    {
                                                        label: 'Record Movement',
                                                        icon: ArrowLeftRight,
                                                        onSelect: () => openMovementDialog(resource),
                                                    },
                                                    {
                                                        label: 'View History',
                                                        icon: History,
                                                        onSelect: () =>
                                                            setHistoryResourceId(
                                                                historyResourceId === resource.id ? null : resource.id,
                                                            ),
                                                    },
                                                ]}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-lg font-semibold text-foreground">
                            Movement History
                            {historyResource ? ` · ${historyResource.title}` : ''}
                        </h2>
                        {historyResourceId !== null && (
                            <Button type="button" variant="outline" size="sm" onClick={() => setHistoryResourceId(null)}>
                                Show All
                            </Button>
                        )}
                    </div>

                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted text-left text-foreground">
                                <tr>
                                    <th className="px-3 py-2">Date</th>
                                    <th className="px-3 py-2">Resource</th>
                                    <th className="px-3 py-2">Movement</th>
                                    <th className="px-3 py-2 text-right">Qty</th>
                                    <th className="px-3 py-2">From → To</th>
                                    <th className="px-3 py-2">Notes</th>
                                    <th className="px-3 py-2">Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                {visibleMovements.length === 0 && (
                                    <EmptyTableRow colSpan={7} message="No inventory movements recorded yet." />
                                )}
                                {visibleMovements.map((movement) => (
                                    <tr key={movement.id} className="border-t border-border">
                                        <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                            {movement.created_at ? new Date(movement.created_at).toLocaleString() : '-'}
                                        </td>
                                        <td className="px-3 py-2">{movement.resource_title ?? '-'}</td>
                                        <td className="px-3 py-2 capitalize">{movement.type}</td>
                                        <td className="px-3 py-2 text-right">{movement.quantity}</td>
                                        <td className="px-3 py-2 text-muted-foreground">
                                            {movement.from_status || movement.to_status
                                                ? `${STATUS_LABELS[movement.from_status ?? ''] ?? '—'} → ${STATUS_LABELS[movement.to_status ?? ''] ?? '—'}`
                                                : '—'}
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">{movement.notes ?? '-'}</td>
                                        <td className="px-3 py-2 text-muted-foreground">{movement.recorded_by ?? '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <Dialog open={movementResource !== null} onOpenChange={(open) => !open && setMovementResource(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Record Inventory Movement</DialogTitle>
                        <DialogDescription>
                            {movementResource
                                ? `${movementResource.title} — ${movementResource.inventory.available} available`
                                : ''}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3">
                        <div>
                            <label htmlFor="movement-type" className="mb-1 block text-sm font-medium text-foreground">
                                Movement *
                            </label>
                            <select
                                id="movement-type"
                                value={data.type}
                                onChange={(event) => {
                                    setData('type', event.target.value);
                                    setData('from_status', '');
                                }}
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {Object.entries(MOVEMENT_LABELS).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                            {errors.type && <p className="mt-1 text-sm text-red-600">{errors.type}</p>}
                        </div>

                        {sourceOptions.length > 1 && (
                            <div>
                                <label htmlFor="movement-from" className="mb-1 block text-sm font-medium text-foreground">
                                    Take Copies From *
                                </label>
                                <select
                                    id="movement-from"
                                    value={data.from_status || sourceOptions[0]}
                                    onChange={(event) => setData('from_status', event.target.value)}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {sourceOptions.map((status) => (
                                        <option key={status} value={status}>
                                            {STATUS_LABELS[status] ?? status}
                                            {movementResource
                                                ? ` (${movementResource.inventory[status as keyof InventoryCounts]})`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                                {errors.from_status && <p className="mt-1 text-sm text-red-600">{errors.from_status}</p>}
                            </div>
                        )}

                        <div>
                            <label htmlFor="movement-quantity" className="mb-1 block text-sm font-medium text-foreground">
                                Quantity *
                            </label>
                            <Input
                                id="movement-quantity"
                                type="number"
                                min={1}
                                value={data.quantity}
                                onChange={(event) => setData('quantity', Math.max(1, Number(event.target.value) || 1))}
                            />
                            {errors.quantity && <p className="mt-1 text-sm text-red-600">{errors.quantity}</p>}
                        </div>

                        <div>
                            <label htmlFor="movement-notes" className="mb-1 block text-sm font-medium text-foreground">
                                Notes
                            </label>
                            <textarea
                                id="movement-notes"
                                rows={2}
                                value={data.notes}
                                onChange={(event) => setData('notes', event.target.value)}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="e.g. Issued to Grade 6 - Section A adviser"
                            />
                            {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setMovementResource(null)}>
                            Cancel
                        </Button>
                        <Button type="button" onClick={submitMovement} disabled={processing}>
                            {processing ? (
                                <>
                                    <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                                    Recording...
                                </>
                            ) : (
                                'Record Movement'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SchoolInventory.layout = {
    breadcrumbs: [
        {
            title: 'Inventory',
            href: '/school/inventory',
        },
    ],
};
