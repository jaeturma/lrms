import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useRef, useState } from 'react';

type Option = {
    id: number;
    name: string;
};

type ResourceTitleRow = {
    id: number;
    title: string;
    author: string | null;
    publisher: string | null;
    language: string | null;
    subject: string | null;
    volume: string | null;
    edition: string | null;
    copyright_year: number | null;
    pages: number | null;
    isbn: string | null;
    description: string | null;
    media_url: string | null;
    cover_image_url: string | null;
    attachment_url: string | null;
    is_active: boolean;
    learning_resource_type_id: number;
    resource_type: string | null;
    grade_level_id: number | null;
    grade_level: string | null;
    schools_using: number;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};

type Props = {
    filters: {
        search?: string;
        learning_resource_type_id?: number | null;
    };
    resourceTitles: Paginator<ResourceTitleRow>;
    resourceTypes: Option[];
    gradeLevels: Option[];
};

type TitleForm = {
    learning_resource_type_id: string;
    grade_level_id: string;
    title: string;
    author: string;
    publisher: string;
    language: string;
    subject: string;
    volume: string;
    edition: string;
    copyright_year: string;
    pages: string;
    isbn: string;
    description: string;
    media_url: string;
    cover_image: File | null;
    attachment: File | null;
    is_active: boolean;
    _method?: string;
};

const emptyForm: TitleForm = {
    learning_resource_type_id: '',
    grade_level_id: '',
    title: '',
    author: '',
    publisher: '',
    language: '',
    subject: '',
    volume: '',
    edition: '',
    copyright_year: '',
    pages: '',
    isbn: '',
    description: '',
    media_url: '',
    cover_image: null,
    attachment: null,
    is_active: true,
};

export default function AdminResourceTitles({ filters, resourceTitles, resourceTypes, gradeLevels }: Props) {
    const { errors } = usePage().props as { errors: Record<string, string> };
    const [editingId, setEditingId] = useState<number | null>(null);
    const coverInputRef = useRef<HTMLInputElement>(null);
    const attachmentInputRef = useRef<HTMLInputElement>(null);
    const form = useForm<TitleForm>(emptyForm);

    const resetForm = () => {
        form.setData(emptyForm);
        setEditingId(null);
        if (coverInputRef.current) coverInputRef.current.value = '';
        if (attachmentInputRef.current) attachmentInputRef.current.value = '';
    };

    const startEditing = (row: ResourceTitleRow) => {
        setEditingId(row.id);
        form.setData({
            ...emptyForm,
            learning_resource_type_id: String(row.learning_resource_type_id),
            grade_level_id: row.grade_level_id ? String(row.grade_level_id) : '',
            title: row.title,
            author: row.author ?? '',
            publisher: row.publisher ?? '',
            language: row.language ?? '',
            subject: row.subject ?? '',
            volume: row.volume ?? '',
            edition: row.edition ?? '',
            copyright_year: row.copyright_year ? String(row.copyright_year) : '',
            pages: row.pages ? String(row.pages) : '',
            isbn: row.isbn ?? '',
            description: row.description ?? '',
            media_url: row.media_url ?? '',
            is_active: row.is_active,
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: resetForm,
        };

        if (editingId) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/app/admin/resource-titles/${editingId}`, options);
        } else {
            form.transform((data) => data);
            form.post('/app/admin/resource-titles', options);
        }
    };

    const toggleActive = (row: ResourceTitleRow) => {
        router.post(
            `/app/admin/resource-titles/${row.id}`,
            {
                _method: 'put',
                learning_resource_type_id: row.learning_resource_type_id,
                grade_level_id: row.grade_level_id,
                title: row.title,
                author: row.author,
                publisher: row.publisher,
                language: row.language,
                subject: row.subject,
                volume: row.volume,
                edition: row.edition,
                copyright_year: row.copyright_year,
                pages: row.pages,
                isbn: row.isbn,
                description: row.description,
                media_url: row.media_url,
                is_active: !row.is_active,
            },
            { preserveScroll: true },
        );
    };

    const removeTitle = (row: ResourceTitleRow) => {
        if (confirm(`Remove "${row.title}" from the catalog?`)) {
            router.delete(`/app/admin/resource-titles/${row.id}`, { preserveScroll: true });
        }
    };

    const inputClass = 'h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground';

    return (
        <>
            <Head title="Resource Catalog" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h1 className="text-2xl font-bold text-foreground">Learning Resource Catalog</h1>
                        <p className="text-sm text-muted-foreground">
                            Division-managed master list of learning resource titles. Schools pick from this catalog and
                            only report their quantities — details, covers, and files come from here.
                        </p>
                    </header>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">
                            {editingId ? 'Edit Resource Title' : 'Add a Resource Title'}
                        </h2>
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <select
                                    value={form.data.learning_resource_type_id}
                                    onChange={(event) => form.setData('learning_resource_type_id', event.target.value)}
                                    className={inputClass}
                                >
                                    <option value="">Type…</option>
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
                                <select
                                    value={form.data.grade_level_id}
                                    onChange={(event) => form.setData('grade_level_id', event.target.value)}
                                    className={inputClass}
                                >
                                    <option value="">Grade level (optional)…</option>
                                    {gradeLevels.map((grade) => (
                                        <option key={grade.id} value={grade.id}>
                                            {grade.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <input
                                    value={form.data.title}
                                    onChange={(event) => form.setData('title', event.target.value)}
                                    placeholder="Title *"
                                    className={inputClass}
                                />
                                {form.errors.title && <p className="mt-1 text-xs text-destructive">{form.errors.title}</p>}
                            </div>
                            <input
                                value={form.data.author}
                                onChange={(event) => form.setData('author', event.target.value)}
                                placeholder="Author/s"
                                className={inputClass}
                            />
                            <input
                                value={form.data.publisher}
                                onChange={(event) => form.setData('publisher', event.target.value)}
                                placeholder="Publisher"
                                className={inputClass}
                            />
                            <input
                                value={form.data.language}
                                onChange={(event) => form.setData('language', event.target.value)}
                                placeholder="Language"
                                className={inputClass}
                            />
                            <input
                                value={form.data.subject}
                                onChange={(event) => form.setData('subject', event.target.value)}
                                placeholder="Subject/s"
                                className={inputClass}
                            />
                            <input
                                value={form.data.volume}
                                onChange={(event) => form.setData('volume', event.target.value)}
                                placeholder="Volume"
                                className={inputClass}
                            />
                            <input
                                value={form.data.edition}
                                onChange={(event) => form.setData('edition', event.target.value)}
                                placeholder="Edition"
                                className={inputClass}
                            />
                            <div>
                                <input
                                    type="number"
                                    value={form.data.copyright_year}
                                    onChange={(event) => form.setData('copyright_year', event.target.value)}
                                    placeholder="Copyright year"
                                    className={inputClass}
                                />
                                {form.errors.copyright_year && (
                                    <p className="mt-1 text-xs text-destructive">{form.errors.copyright_year}</p>
                                )}
                            </div>
                            <input
                                type="number"
                                value={form.data.pages}
                                onChange={(event) => form.setData('pages', event.target.value)}
                                placeholder="Pages"
                                className={inputClass}
                            />
                            <input
                                value={form.data.isbn}
                                onChange={(event) => form.setData('isbn', event.target.value)}
                                placeholder="ISBN"
                                className={inputClass}
                            />
                            <div className="md:col-span-2 lg:col-span-3">
                                <textarea
                                    rows={2}
                                    value={form.data.description}
                                    onChange={(event) => form.setData('description', event.target.value)}
                                    placeholder="Short description (optional)"
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-muted-foreground">Cover image</label>
                                <input
                                    ref={coverInputRef}
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) => form.setData('cover_image', event.target.files?.[0] ?? null)}
                                    className="w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:text-secondary-foreground"
                                />
                                {form.errors.cover_image && <p className="mt-1 text-xs text-destructive">{form.errors.cover_image}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-muted-foreground">
                                    Attachment (PDF, video, or animation)
                                </label>
                                <input
                                    ref={attachmentInputRef}
                                    type="file"
                                    accept=".pdf,.mp4,.webm,.gif"
                                    onChange={(event) => form.setData('attachment', event.target.files?.[0] ?? null)}
                                    className="w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:text-secondary-foreground"
                                />
                                {form.errors.attachment && <p className="mt-1 text-xs text-destructive">{form.errors.attachment}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-muted-foreground">
                                    Media URL (video/animation link)
                                </label>
                                <input
                                    value={form.data.media_url}
                                    onChange={(event) => form.setData('media_url', event.target.value)}
                                    placeholder="https://…"
                                    className={inputClass}
                                />
                                {form.errors.media_url && <p className="mt-1 text-xs text-destructive">{form.errors.media_url}</p>}
                            </div>
                            <div className="flex items-center gap-2 md:col-span-2 lg:col-span-3">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground disabled:opacity-50"
                                >
                                    {editingId ? 'Save Changes' : 'Add to Catalog'}
                                </button>
                                {editingId && (
                                    <button
                                        type="button"
                                        onClick={resetForm}
                                        className="h-9 rounded-md border border-border px-4 text-sm text-foreground hover:bg-muted"
                                    >
                                        Cancel Edit
                                    </button>
                                )}
                            </div>
                        </form>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form method="get" action="/app/admin/resource-titles" className="mb-4 flex flex-wrap gap-2">
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search title, author, publisher, ISBN"
                                className="h-9 w-72 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            />
                            <select
                                name="learning_resource_type_id"
                                defaultValue={filters.learning_resource_type_id ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">All Types</option>
                                {resourceTypes.map((type) => (
                                    <option key={type.id} value={type.id}>
                                        {type.name}
                                    </option>
                                ))}
                            </select>
                            <button type="submit" className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                                Filter
                            </button>
                        </form>

                        {errors.resource_title && <p className="mb-3 text-sm text-destructive">{errors.resource_title}</p>}

                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">Cover</th>
                                        <th className="px-3 py-2">Title</th>
                                        <th className="px-3 py-2">Type / Grade</th>
                                        <th className="px-3 py-2">Publisher</th>
                                        <th className="px-3 py-2">ISBN</th>
                                        <th className="px-3 py-2">Files</th>
                                        <th className="px-3 py-2 text-right">Schools</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {resourceTitles.data.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-center text-muted-foreground" colSpan={9}>
                                                No titles in the catalog yet.
                                            </td>
                                        </tr>
                                    )}
                                    {resourceTitles.data.map((row) => (
                                        <tr key={row.id} className="border-t border-border">
                                            <td className="px-3 py-2">
                                                {row.cover_image_url ? (
                                                    <img
                                                        src={row.cover_image_url}
                                                        alt={`Cover of ${row.title}`}
                                                        className="h-12 w-9 rounded object-cover shadow-sm"
                                                    />
                                                ) : (
                                                    <div className="flex h-12 w-9 items-center justify-center rounded bg-muted text-[10px] text-muted-foreground">
                                                        No cover
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                <p className="font-medium text-foreground">{row.title}</p>
                                                <p className="text-xs text-muted-foreground">{row.author ?? '-'}</p>
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {row.resource_type ?? '-'}
                                                {row.grade_level ? ` · ${row.grade_level}` : ''}
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">{row.publisher ?? '-'}</td>
                                            <td className="px-3 py-2 font-mono text-xs text-muted-foreground">{row.isbn ?? '-'}</td>
                                            <td className="px-3 py-2 text-xs">
                                                <div className="flex flex-col gap-0.5">
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
                                                    {row.media_url && (
                                                        <a
                                                            href={row.media_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="text-primary underline-offset-2 hover:underline"
                                                        >
                                                            Media link
                                                        </a>
                                                    )}
                                                    {!row.attachment_url && !row.media_url && (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2 text-right text-muted-foreground">{row.schools_using}</td>
                                            <td className="px-3 py-2">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${row.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-muted text-muted-foreground'}`}
                                                >
                                                    {row.is_active ? 'active' : 'inactive'}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex justify-end gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => startEditing(row)}
                                                        className="rounded-md border border-border px-2 py-1 text-xs text-foreground hover:bg-muted"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleActive(row)}
                                                        className="rounded-md border border-border px-2 py-1 text-xs text-foreground hover:bg-muted"
                                                    >
                                                        {row.is_active ? 'Deactivate' : 'Activate'}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => removeTitle(row)}
                                                        disabled={row.schools_using > 0}
                                                        className="rounded-md border border-border px-2 py-1 text-xs text-destructive hover:bg-muted disabled:opacity-40"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {resourceTitles.links.length > 3 && (
                            <div className="mt-4 flex flex-wrap gap-2 text-sm">
                                {resourceTitles.links.map((link, index) => (
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
