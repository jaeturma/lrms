import { Head, router, useForm, usePage } from '@inertiajs/react';
import { BookOpen, Pencil, Power, Trash2 } from 'lucide-react';
import { FormEvent, useRef, useState } from 'react';
import { toast } from 'sonner';
import { EmptyTableRow } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { RowActions } from '@/components/row-actions';
import { SearchInput } from '@/components/search-input';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import http from '@/lib/http';

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

type ImportSummary = {
    total_rows: number;
    imported: number;
    updated: number;
    skipped: number;
    errors: Array<{ row: number; message: string }>;
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
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [importing, setImporting] = useState(false);
    const [importError, setImportError] = useState<string | undefined>();
    const [importSummary, setImportSummary] = useState<ImportSummary | null>(null);
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
            onSuccess: () => {
                resetForm();
                toast.success(editingId ? 'Resource title updated.' : 'Resource title added to catalog.');
            },
        };

        if (editingId) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/app/admin/resource-titles/${editingId}`, options);
        } else {
            form.transform((data) => data);
            form.post('/app/admin/resource-titles', options);
        }
    };

    const submitImport = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!selectedFile) {
            setImportError('Please select a CSV or Excel .xlsx file.');

            return;
        }

        setImporting(true);
        setImportError(undefined);

        const formData = new FormData();
        formData.append('file', selectedFile);

        try {
            const response = await http.post('/app/admin/resource-titles/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Accept: 'application/json',
                },
            });

            setImportSummary(response.data.summary);
        } catch {
            setImportError('Import failed. Check the template columns and try again.');
        } finally {
            setImporting(false);
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
            {
                preserveScroll: true,
                onSuccess: () => toast.success(row.is_active ? 'Resource title deactivated.' : 'Resource title activated.'),
            },
        );
    };

    const removeTitle = (row: ResourceTitleRow) => {
        if (confirm(`Remove "${row.title}" from the catalog?`)) {
            router.delete(`/app/admin/resource-titles/${row.id}`, {
                preserveScroll: true,
                onSuccess: () => toast.success('Resource title removed.'),
            });
        }
    };

    const inputClass = 'h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground';

    return (
        <>
            <Head title="Resource Catalog" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <PageHeader
                        icon={BookOpen}
                        iconClassName="bg-indigo-950 text-indigo-400 dark:bg-indigo-900/60 dark:text-indigo-300"
                        title="Learning Resource Catalog"
                        description="Division-managed master list of learning resource titles. Schools pick from this catalog and only report their quantities — details, covers, and files come from here."
                    />

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-foreground">Import Catalog Titles</h2>
                                <p className="text-sm text-muted-foreground">Upload CSV or Excel .xlsx entries for the master learning resource catalog.</p>
                            </div>
                            <a href="/app/admin/resource-titles/import/template" className="text-sm font-semibold underline">
                                Download Template
                            </a>
                        </div>
                        <form onSubmit={submitImport} className="flex flex-wrap items-center gap-3">
                            <input
                                type="file"
                                accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                onChange={(event) => setSelectedFile(event.target.files?.[0] ?? null)}
                            />
                            <Button type="submit" disabled={importing}>
                                {importing ? 'Importing...' : 'Import File'}
                            </Button>
                        </form>
                        <InputError message={importError} />
                        {importSummary && (
                            <div className="mt-4 rounded-md border border-border bg-muted/50 p-3 text-sm text-foreground">
                                <div className="flex flex-wrap gap-4">
                                    <span>Total: {importSummary.total_rows}</span>
                                    <span>Imported: {importSummary.imported}</span>
                                    <span>Updated: {importSummary.updated}</span>
                                    <span>Skipped: {importSummary.skipped}</span>
                                </div>
                                {importSummary.errors.length > 0 && (
                                    <ul className="mt-2 list-disc pl-5 text-red-700">
                                        {importSummary.errors.slice(0, 8).map((item, index) => (
                                            <li key={index}>
                                                Row {item.row}: {item.message}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        )}
                    </section>

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
                            <SearchInput
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search title, author, publisher, ISBN"
                                containerClassName="w-72"
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
                                        <EmptyTableRow colSpan={9} message="No titles in the catalog yet." />
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
                                                <StatusBadge tone={row.is_active ? 'success' : 'neutral'}>
                                                    {row.is_active ? 'active' : 'inactive'}
                                                </StatusBadge>
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                <RowActions
                                                    label={`Actions for ${row.title}`}
                                                    actions={[
                                                        { label: 'Edit', icon: Pencil, onSelect: () => startEditing(row) },
                                                        {
                                                            label: row.is_active ? 'Deactivate' : 'Activate',
                                                            icon: Power,
                                                            onSelect: () => toggleActive(row),
                                                        },
                                                        {
                                                            label: 'Delete',
                                                            icon: Trash2,
                                                            variant: 'destructive',
                                                            disabled: row.schools_using > 0,
                                                            onSelect: () => removeTitle(row),
                                                        },
                                                    ]}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination links={resourceTitles.links} className="mt-4" />
                    </section>
                </div>
            </main>
        </>
    );
}
