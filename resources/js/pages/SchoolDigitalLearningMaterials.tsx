import { Head, Link } from '@inertiajs/react';

type MaterialRow = {
    id: number;
    name: string;
    category: string;
    type: string;
    publisher: string | null;
    link: string | null;
    description: string | null;
    cover_image_url: string | null;
    attachment_url: string | null;
    quality_assured: boolean;
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
        type?: string | null;
    };
    categories: string[];
    types: string[];
    materials: Paginator<MaterialRow>;
};

export default function SchoolDigitalLearningMaterials({ filters, categories, types, materials }: Props) {
    return (
        <>
            <Head title="Digital LMs" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 className="text-lg font-semibold text-foreground">Digital Learning Materials</h2>
                            <p className="text-sm text-muted-foreground">
                                Browse the division's library of digital content — PDFs, PPTs, videos, interactive
                                media, digital storybooks, worksheets, e-comics, H5P packages, lesson plans, and test
                                materials ({materials.total.toLocaleString()} items).
                            </p>
                        </div>
                    </div>

                    <form method="get" action="/school/digital-learning-materials" className="mb-4 flex flex-wrap gap-2">
                        <input
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Search name, publisher, or description"
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
                            name="type"
                            defaultValue={filters.type ?? ''}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                        >
                            <option value="">All Types</option>
                            {types.map((type) => (
                                <option key={type} value={type}>
                                    {type}
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
                                    <th className="px-3 py-2">Cover</th>
                                    <th className="px-3 py-2">Name</th>
                                    <th className="px-3 py-2">Category / Type</th>
                                    <th className="px-3 py-2">Publisher</th>
                                    <th className="px-3 py-2">Link / File</th>
                                    <th className="px-3 py-2">Quality Assured</th>
                                </tr>
                            </thead>
                            <tbody>
                                {materials.data.length === 0 && (
                                    <tr>
                                        <td className="px-3 py-6 text-center text-muted-foreground" colSpan={6}>
                                            No digital learning materials found.
                                        </td>
                                    </tr>
                                )}
                                {materials.data.map((row) => (
                                    <tr key={row.id} className="border-t border-border">
                                        <td className="px-3 py-2">
                                            {row.cover_image_url ? (
                                                <img
                                                    src={row.cover_image_url}
                                                    alt={`Cover of ${row.name}`}
                                                    className="h-12 w-9 rounded object-cover shadow-sm"
                                                />
                                            ) : (
                                                <div className="flex h-12 w-9 items-center justify-center rounded bg-muted text-[10px] text-muted-foreground">
                                                    No cover
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-3 py-2">
                                            <p className="font-medium text-foreground">{row.name}</p>
                                            <p className="text-xs text-muted-foreground">{row.description ?? ''}</p>
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">
                                            {row.category} · {row.type}
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">{row.publisher ?? '-'}</td>
                                        <td className="px-3 py-2 text-xs">
                                            <div className="flex flex-col gap-0.5">
                                                {row.link && (
                                                    <a
                                                        href={row.link}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-primary underline-offset-2 hover:underline"
                                                    >
                                                        Link
                                                    </a>
                                                )}
                                                {row.attachment_url && (
                                                    <a
                                                        href={row.attachment_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-primary underline-offset-2 hover:underline"
                                                    >
                                                        Attachment
                                                    </a>
                                                )}
                                                {!row.link && !row.attachment_url && <span className="text-muted-foreground">-</span>}
                                            </div>
                                        </td>
                                        <td className="px-3 py-2">
                                            <span
                                                className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    row.quality_assured
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                }`}
                                            >
                                                {row.quality_assured ? 'Quality Assured' : 'Not Assured'}
                                            </span>
                                        </td>
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
        </>
    );
}

SchoolDigitalLearningMaterials.layout = {
    breadcrumbs: [
        {
            title: 'Digital LMs',
            href: '/school/digital-learning-materials',
        },
    ],
};
