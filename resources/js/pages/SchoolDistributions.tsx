import { Head, router } from '@inertiajs/react';
import { Truck } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { toast } from 'sonner';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import type { StatusTone } from '@/components/status-badge';

type DistributionRow = {
    id: number;
    reference_code: string | null;
    resource_type: string | null;
    title: string;
    author: string | null;
    publisher: string | null;
    quantity: number;
    quantity_damaged: number | null;
    status: string;
    notes: string | null;
    received_by: string | null;
    received_at: string | null;
    created_at: string | null;
};

type Props = {
    distributions: DistributionRow[];
};

const statusTones: Record<string, StatusTone> = {
    pending: 'warning',
    received: 'success',
    cancelled: 'neutral',
};

function ReceiveForm({ distribution }: { distribution: DistributionRow }) {
    const [quantityDamaged, setQuantityDamaged] = useState('0');
    const [processing, setProcessing] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.post(
            `/school/distributions/${distribution.id}/receive`,
            { quantity_damaged: quantityDamaged === '' ? 0 : Number(quantityDamaged) },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => toast.success(`Receipt confirmed for ${distribution.reference_code}.`),
            },
        );
    };

    return (
        <form onSubmit={submit} className="flex items-center justify-end gap-2">
            <label className="text-xs text-muted-foreground" htmlFor={`damaged-${distribution.id}`}>
                Damaged
            </label>
            <input
                id={`damaged-${distribution.id}`}
                type="number"
                min={0}
                max={distribution.quantity}
                value={quantityDamaged}
                onChange={(event) => setQuantityDamaged(event.target.value)}
                className="h-8 w-20 rounded-md border border-input bg-background px-2 text-sm text-foreground"
            />
            <button
                type="submit"
                disabled={processing}
                className="h-8 rounded-md bg-primary px-3 text-xs text-primary-foreground disabled:opacity-50"
            >
                Confirm Receipt
            </button>
        </form>
    );
}

export default function SchoolDistributions({ distributions }: Props) {
    const pendingCount = distributions.filter((distribution) => distribution.status === 'pending').length;

    return (
        <>
            <Head title="Deliveries" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-6xl space-y-6">
                    <PageHeader
                        icon={Truck}
                        iconClassName="bg-lime-950 text-lime-400 dark:bg-lime-900/60 dark:text-lime-300"
                        title="Division Deliveries"
                        description={`Learning resources sent by the division office. Confirming a receipt adds the copies to your inventory${pendingCount > 0 ? ` — ${pendingCount} awaiting confirmation.` : '.'}`}
                    />

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">Reference</th>
                                        <th className="px-3 py-2">Type</th>
                                        <th className="px-3 py-2">Title</th>
                                        <th className="px-3 py-2">Author</th>
                                        <th className="px-3 py-2">Publisher</th>
                                        <th className="px-3 py-2 text-right">Qty</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2">Sent</th>
                                        <th className="px-3 py-2 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {distributions.length === 0 && (
                                        <EmptyTableRow colSpan={9} message="No deliveries from the division yet." />
                                    )}
                                    {distributions.map((distribution) => (
                                        <tr key={distribution.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs text-muted-foreground">
                                                {distribution.reference_code}
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">{distribution.resource_type ?? '-'}</td>
                                            <td className="px-3 py-2 font-medium text-foreground">{distribution.title}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{distribution.author ?? '-'}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{distribution.publisher ?? '-'}</td>
                                            <td className="px-3 py-2 text-right text-muted-foreground">
                                                {distribution.quantity.toLocaleString()}
                                            </td>
                                            <td className="px-3 py-2">
                                                <StatusBadge tone={statusTones[distribution.status] ?? 'neutral'}>
                                                    {distribution.status}
                                                </StatusBadge>
                                                {distribution.status === 'received' && (distribution.quantity_damaged ?? 0) > 0 && (
                                                    <span className="ml-1 text-xs text-muted-foreground">
                                                        ({distribution.quantity_damaged} damaged)
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {distribution.created_at ? new Date(distribution.created_at).toLocaleDateString() : '-'}
                                            </td>
                                            <td className="px-3 py-2">
                                                {distribution.status === 'pending' ? (
                                                    <ReceiveForm distribution={distribution} />
                                                ) : (
                                                    <span className="block text-right text-xs text-muted-foreground">
                                                        {distribution.received_at
                                                            ? new Date(distribution.received_at).toLocaleDateString()
                                                            : '-'}
                                                    </span>
                                                )}
                                            </td>
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
