import { Head, Link } from '@inertiajs/react';
import { PackageSearch } from 'lucide-react';
import { PageHeaderIcon } from '@/components/page-header-icon';

type EquipmentRow = {
    id: number;
    item_code: string;
    item_name: string;
    category: string;
    brand: string | null;
    model: string | null;
    condition: string;
    status: string;
    assigned_personnel: string | null;
    school_id: string | null;
    school_name: string | null;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};

type Props = {
    filters: {
        search?: string;
        category?: string | null;
        status?: string | null;
    };
    categories: string[];
    statuses: string[];
    equipment: Paginator<EquipmentRow>;
    summary: {
        total: number;
        by_status: Record<string, number>;
    };
};

export default function AdminOtherEquipment({ filters, categories, statuses, equipment, summary }: Props) {
    return (
        <>
            <Head title="Other Equipment" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="flex items-start gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <PageHeaderIcon
                            icon={PackageSearch}
                            className="bg-orange-950 text-orange-400 dark:bg-orange-900/60 dark:text-orange-300"
                        />
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">Other Equipment</h1>
                            <p className="text-sm text-muted-foreground">
                                Division-wide view of TVL, ALS, Library, SPED, Sports, and other equipment registered by schools ({summary.total.toLocaleString()} items).
                            </p>
                            {Object.keys(summary.by_status).length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-2 text-xs">
                                    {Object.entries(summary.by_status).map(([status, total]) => (
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
                        </div>
                    </header>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form method="get" action="/app/admin/other-equipment" className="mb-4 flex flex-wrap gap-2">
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search item, code, serial, or school"
                                className="h-9 w-72 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            />
                            <select
                                name="category"
                                defaultValue={filters.category ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">All Categories</option>
                                {categories.map((category) => (
                                    <option key={category} value={category}>
                                        {category}
                                    </option>
                                ))}
                            </select>
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
                                        <th className="px-3 py-2">Item Code</th>
                                        <th className="px-3 py-2">Name</th>
                                        <th className="px-3 py-2">Category</th>
                                        <th className="px-3 py-2">Brand / Model</th>
                                        <th className="px-3 py-2">Condition</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2">Assigned To</th>
                                        <th className="px-3 py-2">School</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {equipment.data.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-center text-muted-foreground" colSpan={8}>
                                                No equipment found.
                                            </td>
                                        </tr>
                                    )}
                                    {equipment.data.map((item) => (
                                        <tr key={item.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-mono text-xs text-muted-foreground">{item.item_code}</td>
                                            <td className="px-3 py-2 font-medium text-foreground">{item.item_name}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{item.category}</td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {[item.brand, item.model].filter(Boolean).join(' / ') || '-'}
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">{item.condition}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{item.status}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{item.assigned_personnel ?? '-'}</td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {item.school_id ? (
                                                    <Link
                                                        href={`/app/admin/schools/${item.school_id}`}
                                                        className="text-foreground underline-offset-2 hover:underline"
                                                    >
                                                        {item.school_name}
                                                    </Link>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {equipment.links.length > 3 && (
                            <div className="mt-4 flex flex-wrap gap-2 text-sm">
                                {equipment.links.map((link, index) => (
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
