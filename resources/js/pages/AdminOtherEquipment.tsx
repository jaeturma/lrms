import { Head, Link } from '@inertiajs/react';
import { PackageSearch } from 'lucide-react';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';

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

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={PackageSearch}
                        iconClassName="bg-orange-100 text-orange-600 dark:bg-orange-900/60 dark:text-orange-300"
                        title="Other Equipment"
                        description={`Division-wide view of TVL, ALS, Library, SPED, Sports, and other equipment registered by schools (${summary.total.toLocaleString()} items).`}
                        align="start"
                    >
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
                    </PageHeader>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form method="get" action="/app/admin/other-equipment" className="mb-4 flex flex-wrap gap-2">
                            <SearchInput
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search item, code, serial, or school"
                                containerClassName="w-72"
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
                                        <EmptyTableRow colSpan={8} message="No equipment found." />
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

                        <Pagination links={equipment.links} className="mt-4" />
                    </section>
                </div>
            </main>
        </>
    );
}
