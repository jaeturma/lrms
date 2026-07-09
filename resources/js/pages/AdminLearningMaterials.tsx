import { Head } from '@inertiajs/react';
import { BookText } from 'lucide-react';
import { EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';

type MaterialRow = {
    id: number;
    resource_type: string;
    title?: string | null;
    publisher?: string | null;
    quantity_delivered?: number | null;
    quantity_with_issue_defect?: number | null;
    remarks?: string | null;
    school_id?: string | null;
    school_name?: string | null;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    filters: {
        search?: string;
    };
    materials: Paginator<MaterialRow>;
};

export default function AdminLearningMaterials({ filters, materials }: Props) {
    return (
        <>
            <Head title="Learning Materials" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={BookText}
                        iconClassName="bg-indigo-100 text-indigo-600 dark:bg-indigo-900/60 dark:text-indigo-300"
                        title="Learning Materials"
                        description="View all learning materials encoded by schools."
                    />

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form
                            method="get"
                            action="/app/admin/learning-materials"
                            className="flex flex-wrap gap-2"
                        >
                            <SearchInput
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search school, type, title, or publisher"
                                containerClassName="min-w-72"
                            />
                            <button
                                type="submit"
                                className="h-10 rounded-md bg-primary px-4 text-sm text-primary-foreground"
                            >
                                Filter
                            </button>
                        </form>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="overflow-x-auto rounded-md border border-border">
                            <table className="min-w-full border-collapse text-sm">
                                <thead className="bg-background/60 text-left text-muted-foreground">
                                    <tr>
                                        <th className="border-b border-border px-3 py-2">School</th>
                                        <th className="border-b border-border px-3 py-2">Type</th>
                                        <th className="border-b border-border px-3 py-2">Title</th>
                                        <th className="border-b border-border px-3 py-2">Publisher</th>
                                        <th className="border-b border-border px-3 py-2">Delivered</th>
                                        <th className="border-b border-border px-3 py-2">With Issue</th>
                                        <th className="border-b border-border px-3 py-2">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {materials.data.length === 0 && (
                                        <EmptyTableRow colSpan={7} message="No learning materials found." />
                                    )}
                                    {materials.data.map((material) => (
                                        <tr key={material.id} className="border-t border-border">
                                            <td className="px-3 py-2">
                                                <div className="font-medium text-foreground">
                                                    {material.school_name ?? '-'}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {material.school_id ?? '-'}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2">{material.resource_type}</td>
                                            <td className="px-3 py-2">{material.title ?? '-'}</td>
                                            <td className="px-3 py-2">{material.publisher ?? '-'}</td>
                                            <td className="px-3 py-2">{material.quantity_delivered ?? '-'}</td>
                                            <td className="px-3 py-2">{material.quantity_with_issue_defect ?? '-'}</td>
                                            <td className="px-3 py-2">{material.remarks ?? '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination links={materials.links} className="mt-4" />
                    </section>
                </div>
            </main>
        </>
    );
}
