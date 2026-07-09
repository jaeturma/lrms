import { Head, router, useForm } from '@inertiajs/react';
import { MonitorPlay } from 'lucide-react';
import { FormEvent, useRef, useState } from 'react';
import { EmptyTableRow } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import http from '@/lib/http';

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
    is_active: boolean;
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
        quality_assured?: string | null;
    };
    categories: string[];
    types: string[];
    materials: Paginator<MaterialRow>;
    canManage: boolean;
};

type MaterialForm = {
    name: string;
    category: string;
    type: string;
    publisher: string;
    link: string;
    description: string;
    cover_image: File | null;
    attachment: File | null;
    quality_assured: boolean;
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

const emptyForm = (categories: string[], types: string[]): MaterialForm => ({
    name: '',
    category: categories[0] ?? '',
    type: types[0] ?? '',
    publisher: '',
    link: '',
    description: '',
    cover_image: null,
    attachment: null,
    quality_assured: false,
    is_active: true,
});

export default function AdminDigitalLearningMaterials({ filters, categories, types, materials, canManage }: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [importing, setImporting] = useState(false);
    const [importError, setImportError] = useState<string | undefined>();
    const [importSummary, setImportSummary] = useState<ImportSummary | null>(null);
    const coverInputRef = useRef<HTMLInputElement>(null);
    const attachmentInputRef = useRef<HTMLInputElement>(null);
    const form = useForm<MaterialForm>(emptyForm(categories, types));

    const resetForm = () => {
        form.setData(emptyForm(categories, types));
        setEditingId(null);
        if (coverInputRef.current) coverInputRef.current.value = '';
        if (attachmentInputRef.current) attachmentInputRef.current.value = '';
    };

    const startEditing = (row: MaterialRow) => {
        setEditingId(row.id);
        form.setData({
            ...emptyForm(categories, types),
            name: row.name,
            category: row.category,
            type: row.type,
            publisher: row.publisher ?? '',
            link: row.link ?? '',
            description: row.description ?? '',
            quality_assured: row.quality_assured,
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
            form.post(`/app/admin/digital-learning-materials/${editingId}`, options);
        } else {
            form.transform((data) => data);
            form.post('/app/admin/digital-learning-materials', options);
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
            const response = await http.post('/app/admin/digital-learning-materials/import', formData, {
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

    const toggleActive = (row: MaterialRow) => {
        router.post(
            `/app/admin/digital-learning-materials/${row.id}`,
            {
                _method: 'put',
                name: row.name,
                category: row.category,
                type: row.type,
                publisher: row.publisher,
                link: row.link,
                description: row.description,
                quality_assured: row.quality_assured,
                is_active: !row.is_active,
            },
            { preserveScroll: true },
        );
    };

    const removeMaterial = (row: MaterialRow) => {
        if (confirm(`Remove "${row.name}" from the catalog?`)) {
            router.delete(`/app/admin/digital-learning-materials/${row.id}`, { preserveScroll: true });
        }
    };

    const inputClass = 'h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground';

    return (
        <>
            <Head title="Digital Learning Materials" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={MonitorPlay}
                        iconClassName="bg-cyan-100 text-cyan-600 dark:bg-cyan-900/60 dark:text-cyan-300"
                        title="Digital Learning Materials"
                        description={`Division-managed library of digital content — PDFs, PPTs, videos, interactive media, digital storybooks, worksheets, e-comics, H5P packages, lesson plans, and test materials (${materials.total.toLocaleString()} items).`}
                    />

                    {canManage && (
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold text-foreground">Import Digital LM</h2>
                                    <p className="text-sm text-muted-foreground">Upload CSV or Excel .xlsx entries for the master Digital LM library.</p>
                                </div>
                                <a href="/app/admin/digital-learning-materials/import/template" className="text-sm font-semibold underline">
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
                    )}

                    {canManage && (
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="mb-4 text-lg font-semibold text-foreground">
                                {editingId ? 'Edit Digital Learning Material' : 'Add a Digital Learning Material'}
                            </h2>
                            <form onSubmit={submit} className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <input
                                        value={form.data.name}
                                        onChange={(event) => form.setData('name', event.target.value)}
                                        placeholder="Name of digital learning material *"
                                        className={inputClass}
                                    />
                                    {form.errors.name && <p className="mt-1 text-xs text-destructive">{form.errors.name}</p>}
                                </div>
                                <div>
                                    <select
                                        value={form.data.category}
                                        onChange={(event) => form.setData('category', event.target.value)}
                                        className={inputClass}
                                    >
                                        {categories.map((category) => (
                                            <option key={category} value={category}>
                                                {category}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.category && <p className="mt-1 text-xs text-destructive">{form.errors.category}</p>}
                                </div>
                                <div>
                                    <select
                                        value={form.data.type}
                                        onChange={(event) => form.setData('type', event.target.value)}
                                        className={inputClass}
                                    >
                                        {types.map((type) => (
                                            <option key={type} value={type}>
                                                {type}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.type && <p className="mt-1 text-xs text-destructive">{form.errors.type}</p>}
                                </div>
                                <input
                                    value={form.data.publisher}
                                    onChange={(event) => form.setData('publisher', event.target.value)}
                                    placeholder="Publisher"
                                    className={inputClass}
                                />
                                <div>
                                    <input
                                        value={form.data.link}
                                        onChange={(event) => form.setData('link', event.target.value)}
                                        placeholder="Link (https://…)"
                                        className={inputClass}
                                    />
                                    {form.errors.link && <p className="mt-1 text-xs text-destructive">{form.errors.link}</p>}
                                </div>
                                <div>
                                    <select
                                        value={form.data.quality_assured ? '1' : '0'}
                                        onChange={(event) => form.setData('quality_assured', event.target.value === '1')}
                                        className={inputClass}
                                    >
                                        <option value="0">Not Quality Assured</option>
                                        <option value="1">Quality Assured</option>
                                    </select>
                                </div>
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
                                    <label className="mb-1 block text-xs font-medium text-muted-foreground">Cover photo</label>
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
                                        File attachment (PDF, PPT, Word, Excel, H5P, etc.)
                                    </label>
                                    <input
                                        ref={attachmentInputRef}
                                        type="file"
                                        onChange={(event) => form.setData('attachment', event.target.files?.[0] ?? null)}
                                        className="w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:text-secondary-foreground"
                                    />
                                    {form.errors.attachment && <p className="mt-1 text-xs text-destructive">{form.errors.attachment}</p>}
                                </div>
                                <div className="flex items-center gap-2 md:col-span-2 lg:col-span-3">
                                    <button
                                        type="submit"
                                        disabled={form.processing}
                                        className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground disabled:opacity-50"
                                    >
                                        {editingId ? 'Save Changes' : 'Add to Library'}
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
                    )}

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form method="get" action="/app/admin/digital-learning-materials" className="mb-4 flex flex-wrap gap-2">
                            <SearchInput
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search name, publisher, or description"
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
                            <select
                                name="quality_assured"
                                defaultValue={filters.quality_assured ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">Quality Assured (any)</option>
                                <option value="1">Quality Assured only</option>
                                <option value="0">Not Quality Assured</option>
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
                                        <th className="px-3 py-2">Status</th>
                                        {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody>
                                    {materials.data.length === 0 && (
                                        <EmptyTableRow
                                            colSpan={canManage ? 8 : 7}
                                            message="No digital learning materials found."
                                        />
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
                                                <p className="text-xs text-muted-foreground">{row.publisher ?? '-'}</p>
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
                                            <td className="px-3 py-2">
                                                <StatusBadge tone={row.is_active ? 'success' : 'neutral'}>
                                                    {row.is_active ? 'active' : 'inactive'}
                                                </StatusBadge>
                                            </td>
                                            {canManage && (
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
                                                            onClick={() => removeMaterial(row)}
                                                            className="rounded-md border border-border px-2 py-1 text-xs text-destructive hover:bg-muted"
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            )}
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
