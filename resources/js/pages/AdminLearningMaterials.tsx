import { Head, Link } from '@inertiajs/react';

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

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h1 className="text-2xl font-bold text-foreground">
                            Learning Materials
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            View all learning materials encoded by schools.
                        </p>
                    </header>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form
                            method="get"
                            action="/app/admin/learning-materials"
                            className="flex flex-wrap gap-2"
                        >
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search school, type, title, or publisher"
                                className="h-10 min-w-72 rounded-md border border-input bg-background px-3 text-sm"
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
                                        <tr>
                                            <td
                                                className="px-3 py-6 text-center text-muted-foreground"
                                                colSpan={7}
                                            >
                                                No learning materials found.
                                            </td>
                                        </tr>
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

                        {materials.links.length > 3 && (
                            <div className="mt-4 flex flex-wrap gap-2 text-sm">
                                {materials.links.map((link, index) => (
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
