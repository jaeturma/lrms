import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Option = {
    id: number;
    name?: string;
    school_name?: string;
};

type DistributionRow = {
    id: number;
    reference_code: string | null;
    school_name: string | null;
    school_code: string | null;
    resource_type: string | null;
    title: string;
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
    resourceTypes: Option[];
    summary: Record<string, number>;
};

const statusClasses: Record<string, string> = {
    pending: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    received: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    cancelled: 'bg-muted text-muted-foreground',
};

export default function AdminDistributions({ filters, statuses, distributions, schools, resourceTypes, summary }: Props) {
    const form = useForm({
        school_id: '',
        learning_resource_type_id: '',
        title: '',
        publisher: '',
        quantity: '',
        notes: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/app/admin/distributions', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const cancelDistribution = (distribution: DistributionRow) => {
        if (confirm(`Cancel delivery ${distribution.reference_code}?`)) {
            router.post(`/app/admin/distributions/${distribution.id}/cancel`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Distributions" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h1 className="text-2xl font-bold text-foreground">Resource Distributions</h1>
                        <p className="text-sm text-muted-foreground">
                            Deliveries of learning resources from the division to schools. Schools confirm receipt to add
                            copies into their inventories.
                        </p>
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
                    </header>

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
                                    value={form.data.learning_resource_type_id}
                                    onChange={(event) => form.setData('learning_resource_type_id', event.target.value)}
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                >
                                    <option value="">Select resource type…</option>
                                    {resourceTypes.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.learning_resource_type_id && (
                                    <p className="mt-1 text-xs text-destructive">{form.errors.learning_resource_type_id}</p>
                                )}
                            </div>
                            <div>
                                <input
                                    value={form.data.title}
                                    onChange={(event) => form.setData('title', event.target.value)}
                                    placeholder="Title"
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                />
                                {form.errors.title && <p className="mt-1 text-xs text-destructive">{form.errors.title}</p>}
                            </div>
                            <div>
                                <input
                                    value={form.data.publisher}
                                    onChange={(event) => form.setData('publisher', event.target.value)}
                                    placeholder="Publisher (optional)"
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                />
                                {form.errors.publisher && <p className="mt-1 text-xs text-destructive">{form.errors.publisher}</p>}
                            </div>
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
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search reference, title, or school"
                                className="h-9 w-72 rounded-md border border-input bg-background px-3 text-sm text-foreground"
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
                                        <th className="px-3 py-2 text-right">Qty</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2">Received</th>
                                        <th className="px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {distributions.data.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-center text-muted-foreground" colSpan={8}>
                                                No deliveries recorded.
                                            </td>
                                        </tr>
                                    )}
                                    {distributions.data.map((distribution) => (
                                        <tr key={distribution.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs text-muted-foreground">
                                                {distribution.reference_code}
                                            </td>
                                            <td className="px-3 py-2 text-foreground">{distribution.school_name ?? '-'}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{distribution.resource_type ?? '-'}</td>
                                            <td className="px-3 py-2 font-medium text-foreground">{distribution.title}</td>
                                            <td className="px-3 py-2 text-right text-muted-foreground">
                                                {distribution.quantity.toLocaleString()}
                                            </td>
                                            <td className="px-3 py-2">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusClasses[distribution.status] ?? 'bg-muted text-muted-foreground'}`}
                                                >
                                                    {distribution.status}
                                                </span>
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

                        {distributions.links.length > 3 && (
                            <div className="mt-4 flex flex-wrap gap-2 text-sm">
                                {distributions.links.map((link, index) => (
                                    <span key={index}>
                                        {link.url ? (
                                            <Link
                                                href={link.url}
                                                className={`rounded border px-3 py-1 ${link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-foreground'}`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ) : (
                                            <span
                                                className="rounded border border-border bg-card px-3 py-1 text-muted-foreground"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        )}
                                    </span>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </main>
        </>
    );
}
