import { Head, router, useForm } from '@inertiajs/react';
import { Truck } from 'lucide-react';
import { FormEvent } from 'react';
import { toast } from 'sonner';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { StatusBadge } from '@/components/status-badge';
import type { StatusTone } from '@/components/status-badge';

type Option = {
    id: number;
    name?: string;
    school_name?: string;
};

type ResourceTitleOption = {
    id: number;
    title: string;
    author: string | null;
    publisher: string | null;
    resource_type: string | null;
    grade_level: string | null;
};

type DistributionRow = {
    id: number;
    reference_code: string | null;
    school_name: string | null;
    school_code: string | null;
    resource_title_id: number | null;
    resource_type: string | null;
    title: string;
    author: string | null;
    publisher: string | null;
    quantity: number;
    quantity_damaged: number | null;
    status: string;
    notes: string | null;
    created_by: string | null;
    received_by: string | null;
    received_at: string | null;
    created_at: string | null;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};

type Props = {
    filters: {
        search?: string;
        status?: string | null;
    };
    statuses: string[];
    distributions: Paginator<DistributionRow>;
    schools: Option[];
    resourceTitles: ResourceTitleOption[];
    summary: Record<string, number>;
};

const statusTones: Record<string, StatusTone> = {
    pending: 'warning',
    received: 'success',
    cancelled: 'neutral',
};

export default function AdminDistributions({ filters, statuses, distributions, schools, resourceTitles, summary }: Props) {
    const form = useForm({
        school_id: '',
        resource_title_id: '',
        quantity: '',
        notes: '',
    });
    const selectedTitle = resourceTitles.find((title) => String(title.id) === form.data.resource_title_id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/app/admin/distributions', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const cancelDistribution = (distribution: DistributionRow) => {
        if (confirm(`Cancel delivery ${distribution.reference_code}?`)) {
            router.post(
                `/app/admin/distributions/${distribution.id}/cancel`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => toast.success(`Delivery ${distribution.reference_code} cancelled.`),
                },
            );
        }
    };

    return (
        <>
            <Head title="Distributions" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={Truck}
                        iconClassName="bg-lime-100 text-lime-600 dark:bg-lime-900/60 dark:text-lime-300"
                        title="Resource Distributions"
                        description="Deliveries of learning resources from the division to schools. Schools confirm receipt to add copies into their inventories."
                        align="start"
                    >
                        {Object.keys(summary).length > 0 && (
                            <div className="mt-3 flex flex-wrap gap-2 text-xs">
                                {Object.entries(summary).map(([status, total]) => (
                                    <span
                                        key={status}
                                        className="inline-flex items-center gap-1 rounded-full border border-border bg-muted px-2.5 py-1 text-muted-foreground"
                                    >
                                        <span className="font-semibold text-foreground">{total.toLocaleString()}</span>
                                        {status}
                                    </span>
                                ))}
                            </div>
                        )}
                    </PageHeader>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">Record a Delivery</h2>
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <select
                                    value={form.data.school_id}
                                    onChange={(event) => form.setData('school_id', event.target.value)}
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                >
                                    <option value="">Select school…</option>
                                    {schools.map((school) => (
                                        <option key={school.id} value={school.id}>
                                            {school.school_name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.school_id && <p className="mt-1 text-xs text-destructive">{form.errors.school_id}</p>}
                            </div>
                            <div>
                                <select
                                    value={form.data.resource_title_id}
                                    onChange={(event) => form.setData('resource_title_id', event.target.value)}
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                >
                                    <option value="">Select catalog title...</option>
                                    {resourceTitles.map((title) => (
                                        <option key={title.id} value={title.id}>
                                            {[title.title, title.author].filter(Boolean).join(' - ')}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.resource_title_id && (
                                    <p className="mt-1 text-xs text-destructive">{form.errors.resource_title_id}</p>
                                )}
                            </div>
                            {selectedTitle && (
                                <div className="rounded-md border border-border bg-muted/50 px-3 py-2 text-sm md:col-span-2 lg:col-span-1">
                                    <p className="font-medium text-foreground">{selectedTitle.title}</p>
                                    <p className="text-muted-foreground">
                                        {[selectedTitle.author, selectedTitle.publisher].filter(Boolean).join(' / ') || '-'}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {[selectedTitle.resource_type, selectedTitle.grade_level].filter(Boolean).join(' - ') || '-'}
                                    </p>
                                </div>
                            )}
                            <div>
                                <input
                                    type="number"
                                    min={1}
                                    value={form.data.quantity}
                                    onChange={(event) => form.setData('quantity', event.target.value)}
                                    placeholder="Quantity"
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                />
                                {form.errors.quantity && <p className="mt-1 text-xs text-destructive">{form.errors.quantity}</p>}
                            </div>
                            <div>
                                <input
                                    value={form.data.notes}
                                    onChange={(event) => form.setData('notes', event.target.value)}
                                    placeholder="Notes (optional)"
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                />
                                {form.errors.notes && <p className="mt-1 text-xs text-destructive">{form.errors.notes}</p>}
                            </div>
                            <div className="md:col-span-2 lg:col-span-3">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground disabled:opacity-50"
                                >
                                    Record Delivery
                                </button>
                            </div>
                        </form>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form method="get" action="/app/admin/distributions" className="mb-4 flex flex-wrap gap-2">
                            <SearchInput
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search reference, title, or school"
                                containerClassName="w-72"
                            />
                            <select
                                name="status"
                                defaultValue={filters.status ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">All Statuses</option>
                                {statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </select>
                            <button type="submit" className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                                Filter
                            </button>
                        </form>

                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">Reference</th>
                                        <th className="px-3 py-2">School</th>
                                        <th className="px-3 py-2">Type</th>
                                        <th className="px-3 py-2">Title</th>
                                        <th className="px-3 py-2">Author</th>
                                        <th className="px-3 py-2 text-right">Qty</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2">Received</th>
                                        <th className="px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {distributions.data.length === 0 && (
                                        <EmptyTableRow colSpan={9} message="No deliveries recorded." />
                                    )}
                                    {distributions.data.map((distribution) => (
                                        <tr key={distribution.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs text-muted-foreground">
                                                {distribution.reference_code}
                                            </td>
                                            <td className="px-3 py-2 text-foreground">{distribution.school_name ?? '-'}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{distribution.resource_type ?? '-'}</td>
                                            <td className="px-3 py-2 font-medium text-foreground">{distribution.title}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{distribution.author ?? '-'}</td>
                                            <td className="px-3 py-2 text-right text-muted-foreground">
                                                {distribution.quantity.toLocaleString()}
                                            </td>
                                            <td className="px-3 py-2">
                                                <StatusBadge tone={statusTones[distribution.status] ?? 'neutral'}>
                                                    {distribution.status}
                                                </StatusBadge>
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {distribution.received_at
                                                    ? `${new Date(distribution.received_at).toLocaleDateString()} by ${distribution.received_by ?? '-'}`
                                                    : '-'}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                {distribution.status === 'pending' && (
                                                    <button
                                                        type="button"
                                                        onClick={() => cancelDistribution(distribution)}
                                                        className="rounded-md border border-border px-2.5 py-1 text-xs text-foreground hover:bg-muted"
                                                    >
                                                        Cancel
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination links={distributions.links} className="mt-4" />
                    </section>
                </div>
            </main>
        </>
    );
}
