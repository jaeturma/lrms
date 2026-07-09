import { Head, Link, router, useForm } from '@inertiajs/react';
import { Loader2, MonitorSmartphone, Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { PageHeaderIcon } from '@/components/page-header-icon';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import http from '@/lib/http';

type CatalogItem = {
    id: number;
    item_name: string;
    category: string;
    brand: string | null;
    model: string | null;
    specifications: string | null;
    manufacturer: string | null;
    description: string | null;
    is_active: boolean;
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
        category?: string | null;
    };
    categories: string[];
    catalogItems: Paginator<CatalogItem>;
};

type ImportSummary = {
    total_rows: number;
    imported: number;
    updated: number;
    skipped: number;
    errors: Array<{ row: number; message: string }>;
};

const emptyForm = (categories: string[]) => ({
    item_name: '',
    category: categories[0] ?? '',
    brand: '',
    model: '',
    specifications: '',
    manufacturer: '',
    description: '',
    is_active: true,
});

export default function AdminIctEquipmentCatalog({ filters, categories, catalogItems }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [importing, setImporting] = useState(false);
    const [importError, setImportError] = useState<string | undefined>();
    const [importSummary, setImportSummary] = useState<ImportSummary | null>(null);

    const { data, setData, post, put, processing, errors, clearErrors } = useForm(emptyForm(categories));

    const openAddDialog = () => {
        setEditingId(null);
        clearErrors();
        setData(emptyForm(categories));
        setDialogOpen(true);
    };

    const openEditDialog = (item: CatalogItem) => {
        setEditingId(item.id);
        clearErrors();
        setData({
            item_name: item.item_name,
            category: item.category,
            brand: item.brand ?? '',
            model: item.model ?? '',
            specifications: item.specifications ?? '',
            manufacturer: item.manufacturer ?? '',
            description: item.description ?? '',
            is_active: item.is_active,
        });
        setDialogOpen(true);
    };

    const submit = () => {
        const options = {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        };

        if (editingId === null) {
            post('/app/admin/ict-equipment-catalog', options);
        } else {
            put(`/app/admin/ict-equipment-catalog/${editingId}`, options);
        }
    };

    const deleteItem = (item: CatalogItem) => {
        if (item.schools_using > 0) {
            window.alert('This equipment is already used by school records. Deactivate it instead.');

            return;
        }

        if (!window.confirm(`Remove ${item.item_name} from the equipment catalog?`)) {
            return;
        }

        router.delete(`/app/admin/ict-equipment-catalog/${item.id}`, { preserveScroll: true });
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
            const response = await http.post('/app/admin/ict-equipment-catalog/import', formData, {
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

    return (
        <>
            <Head title="ICT Equipment Catalog" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div className="flex items-center gap-4">
                                <PageHeaderIcon
                                    icon={MonitorSmartphone}
                                    className="bg-teal-950 text-teal-400 dark:bg-teal-900/60 dark:text-teal-300"
                                />
                                <div>
                                    <h1 className="text-2xl font-bold text-foreground">ICT Equipment Catalog</h1>
                                    <p className="text-sm text-muted-foreground">
                                        Division-managed list of ICT equipment ({catalogItems.total.toLocaleString()} items). Schools
                                        pick from this catalog when registering the equipment in their possession.
                                    </p>
                                </div>
                            </div>
                            <Button type="button" onClick={openAddDialog}>
                                <Plus className="mr-1 h-4 w-4" />
                                Add Equipment
                            </Button>
                        </div>
                    </header>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-foreground">Import Catalog Equipment</h2>
                                <p className="text-sm text-muted-foreground">Upload CSV or Excel .xlsx entries for the master ICT equipment catalog.</p>
                            </div>
                            <a href="/app/admin/ict-equipment-catalog/import/template" className="text-sm font-semibold underline">
                                Download Template
                            </a>
                        </div>
                        <form onSubmit={submitImport} className="mb-4 flex flex-wrap items-center gap-3">
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
                            <div className="mb-4 rounded-md border border-border bg-muted/50 p-3 text-sm text-foreground">
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
                        <form method="get" action="/app/admin/ict-equipment-catalog" className="mb-4 flex flex-wrap gap-2">
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search name, brand, model, or manufacturer"
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
                            <button type="submit" className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                                Filter
                            </button>
                        </form>

                        <div className="overflow-x-auto rounded-xl border border-border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="px-3 py-2">Name</th>
                                        <th className="px-3 py-2">Category</th>
                                        <th className="px-3 py-2">Brand / Model</th>
                                        <th className="px-3 py-2">Manufacturer</th>
                                        <th className="px-3 py-2">School Records</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {catalogItems.data.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-center text-muted-foreground" colSpan={7}>
                                                No catalog equipment found. Click Add Equipment to create the first entry.
                                            </td>
                                        </tr>
                                    )}
                                    {catalogItems.data.map((item) => (
                                        <tr key={item.id} className="border-t border-border">
                                            <td className="px-3 py-2 font-medium text-foreground">{item.item_name}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{item.category}</td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {[item.brand, item.model].filter(Boolean).join(' / ') || '-'}
                                            </td>
                                            <td className="px-3 py-2 text-muted-foreground">{item.manufacturer ?? '-'}</td>
                                            <td className="px-3 py-2 text-muted-foreground">{item.schools_using.toLocaleString()}</td>
                                            <td className="px-3 py-2">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                        item.is_active
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                            : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                    }`}
                                                >
                                                    {item.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex gap-2">
                                                    <Button type="button" variant="outline" size="sm" onClick={() => openEditDialog(item)}>
                                                        <Pencil className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40"
                                                        onClick={() => deleteItem(item)}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {catalogItems.links.length > 3 && (
                            <div className="mt-4 flex flex-wrap gap-2 text-sm">
                                {catalogItems.links.map((link, index) => (
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

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>{editingId === null ? 'Add Catalog Equipment' : 'Edit Catalog Equipment'}</DialogTitle>
                        <DialogDescription>
                            Schools will see this entry when registering equipment. Deactivate an entry to hide it from
                            schools without affecting existing records.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3">
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <Field label="Item Name *" error={errors.item_name}>
                                <Input value={data.item_name} onChange={(event) => setData('item_name', event.target.value)} />
                            </Field>
                            <Field label="Category *" error={errors.category}>
                                <select
                                    value={data.category}
                                    onChange={(event) => setData('category', event.target.value)}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {categories.map((category) => (
                                        <option key={category} value={category}>
                                            {category}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                            <Field label="Brand" error={errors.brand}>
                                <Input value={data.brand} onChange={(event) => setData('brand', event.target.value)} />
                            </Field>
                            <Field label="Model" error={errors.model}>
                                <Input value={data.model} onChange={(event) => setData('model', event.target.value)} />
                            </Field>
                            <Field label="Manufacturer" error={errors.manufacturer}>
                                <Input value={data.manufacturer} onChange={(event) => setData('manufacturer', event.target.value)} />
                            </Field>
                            <Field label="Active" error={errors.is_active}>
                                <select
                                    value={data.is_active ? '1' : '0'}
                                    onChange={(event) => setData('is_active', event.target.value === '1')}
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="1">Active (visible to schools)</option>
                                    <option value="0">Inactive (hidden from schools)</option>
                                </select>
                            </Field>
                        </div>
                        <div className="grid gap-3 lg:grid-cols-2">
                            <Field label="Specifications" error={errors.specifications}>
                                <textarea
                                    rows={2}
                                    value={data.specifications}
                                    onChange={(event) => setData('specifications', event.target.value)}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </Field>
                            <Field label="Description" error={errors.description}>
                                <textarea
                                    rows={2}
                                    value={data.description}
                                    onChange={(event) => setData('description', event.target.value)}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </Field>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="button" onClick={submit} disabled={processing}>
                            {processing ? (
                                <>
                                    <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                                    Saving...
                                </>
                            ) : editingId === null ? (
                                'Add Equipment'
                            ) : (
                                'Save Changes'
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-foreground">{label}</label>
            {children}
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}
