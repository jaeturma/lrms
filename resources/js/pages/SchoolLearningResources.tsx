import { Head } from '@inertiajs/react';
import { Download, Eye, Loader2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { EmptyTableRow } from '@/components/empty-state';
import { RowActions } from '@/components/row-actions';
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

type LearningResource = {
    id?: number;
    learning_resource_type_id: number;
    resource_title_id?: number | null;
    resource_type: string | null;
    title: string;
    publisher: string;
    quantity_delivered: number;
    quantity_with_issue_defect: number;
    remarks?: string | null;
    source?: string | null;
    supplier?: string | null;
    date_delivered?: string | null;
    ier_no?: string | null;
    cover_image_url?: string | null;
    attachment_url?: string | null;
    media_url?: string | null;
};

type LearningResourceForm = {
    id?: number;
    learning_resource_type_id: number | '';
    resource_title_id: number | '';
    resource_type: string;
    title: string;
    publisher: string;
    quantity_delivered: number;
    quantity_with_issue_defect: number;
    remarks: string;
    source: string;
    supplier: string;
    date_delivered: string;
    ier_no: string;
    cover_image_url: string | null;
    attachment_url: string | null;
    media_url: string | null;
};

type LearningResourceTypeOption = {
    id: number;
    name: string;
};

type ResourceTitleOption = {
    id: number;
    title: string;
    author: string | null;
    publisher: string | null;
    language: string | null;
    subject: string | null;
    resource_type: string | null;
    grade_level: string | null;
    isbn: string | null;
    cover_image_url: string | null;
    attachment_url: string | null;
    media_url: string | null;
};

type Props = {
    learningResources: LearningResource[] | { data: LearningResource[] };
    learningResourceTypes: LearningResourceTypeOption[];
    resourceTitles: ResourceTitleOption[];
};

export default function SchoolLearningResources({ learningResources, learningResourceTypes, resourceTitles }: Props) {
    const initialRows = useMemo<LearningResourceForm[]>(() => {
        const source = Array.isArray(learningResources)
            ? learningResources
            : learningResources.data ?? [];

        return source.map((resource) => ({
            id: resource.id,
            learning_resource_type_id: resource.learning_resource_type_id,
            resource_title_id: resource.resource_title_id ?? '',
            resource_type: resource.resource_type ?? '',
            title: resource.title,
            publisher: resource.publisher,
            quantity_delivered: Number(resource.quantity_delivered) || 1,
            quantity_with_issue_defect: Number(resource.quantity_with_issue_defect) || 0,
            remarks: resource.remarks ?? '',
            source: resource.source ?? '',
            supplier: resource.supplier ?? '',
            date_delivered: resource.date_delivered ?? '',
            ier_no: resource.ier_no ?? '',
            cover_image_url: resource.cover_image_url ?? null,
            attachment_url: resource.attachment_url ?? null,
            media_url: resource.media_url ?? null,
        }));
    }, [learningResources]);

    const [rows, setRows] = useState<LearningResourceForm[]>(initialRows);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [viewDialogOpen, setViewDialogOpen] = useState(false);
    const [viewRow, setViewRow] = useState<LearningResourceForm | null>(null);
    const [editingIndex, setEditingIndex] = useState<number | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [form, setForm] = useState<LearningResourceForm>({
        learning_resource_type_id: '',
        resource_title_id: '',
        resource_type: '',
        title: '',
        publisher: '',
        quantity_delivered: 1,
        quantity_with_issue_defect: 0,
        remarks: '',
        source: '',
        supplier: '',
        date_delivered: '',
        ier_no: '',
        cover_image_url: null,
        attachment_url: null,
        media_url: null,
    });

    const selectedCatalogTitle = form.resource_title_id === ''
        ? null
        : resourceTitles.find((option) => option.id === form.resource_title_id) ?? null;

    const pageSize = 10;
    const totalRows = rows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    const currentPageSafe = Math.min(currentPage, totalPages);
    const pageStart = (currentPageSafe - 1) * pageSize;
    const paginatedRows = rows.slice(pageStart, pageStart + pageSize);

    const openViewDialog = (row: LearningResourceForm) => {
        setViewRow(row);
        setViewDialogOpen(true);
    };

    const persistRows = async (nextRows: LearningResourceForm[]): Promise<boolean> => {
        setSaving(true);
        setStatus(null);
        setError(null);

        try {
            const payload = {
                resources: nextRows.map((row) => ({
                    id: row.id ?? null,
                    resource_title_id: row.resource_title_id === '' ? null : row.resource_title_id,
                    learning_resource_type_id: row.learning_resource_type_id === '' ? null : row.learning_resource_type_id,
                    title: row.title || null,
                    publisher: row.publisher || null,
                    quantity_delivered: Number(row.quantity_delivered),
                    quantity_with_issue_defect: Number(row.quantity_with_issue_defect),
                    remarks: row.remarks,
                    source: row.source || null,
                    supplier: row.supplier || null,
                    date_delivered: row.date_delivered || null,
                    ier_no: row.ier_no || null,
                })),
            };

            const response = await http.put('/school/resources', payload, {
                headers: {
                    Accept: 'application/json',
                },
            });

            const persistedRows = (response.data.resources ?? []) as LearningResource[];

            setRows(
                persistedRows.map((resource) => ({
                    id: resource.id,
                    learning_resource_type_id: resource.learning_resource_type_id,
                    resource_title_id: resource.resource_title_id ?? '',
                    resource_type: resource.resource_type ?? '',
                    title: resource.title,
                    publisher: resource.publisher,
                    quantity_delivered: Number(resource.quantity_delivered) || 1,
                    quantity_with_issue_defect: Number(resource.quantity_with_issue_defect) || 0,
                    remarks: resource.remarks ?? '',
                    source: resource.source ?? '',
                    supplier: resource.supplier ?? '',
                    date_delivered: resource.date_delivered ?? '',
                    ier_no: resource.ier_no ?? '',
                    cover_image_url: resource.cover_image_url ?? null,
                    attachment_url: resource.attachment_url ?? null,
                    media_url: resource.media_url ?? null,
                })),
            );
            setStatus(response.data.message ?? 'Saved.');

            return true;
        } catch {
            setError('Unable to save learning resources. Please check your entries.');

            return false;
        } finally {
            setSaving(false);
        }
    };

    const openAddDialog = () => {
        setEditingIndex(null);
        setForm({
            learning_resource_type_id: learningResourceTypes[0]?.id ?? '',
            resource_title_id: '',
            resource_type: learningResourceTypes[0]?.name ?? '',
            title: '',
            publisher: '',
            quantity_delivered: 1,
            quantity_with_issue_defect: 0,
            remarks: '',
            source: '',
            supplier: '',
            date_delivered: '',
            ier_no: '',
            cover_image_url: null,
            attachment_url: null,
            media_url: null,
        });
        setDialogOpen(true);
    };

    const selectCatalogTitle = (value: string) => {
        if (value === '') {
            setForm((current) => ({ ...current, resource_title_id: '' }));

            return;
        }

        const option = resourceTitles.find((candidate) => candidate.id === Number(value));

        if (!option) {
            return;
        }

        setForm((current) => ({
            ...current,
            resource_title_id: option.id,
            title: option.title,
            publisher: option.publisher ?? '',
            resource_type: option.resource_type ?? '',
            cover_image_url: option.cover_image_url,
            attachment_url: option.attachment_url,
            media_url: option.media_url,
        }));
    };

    const openEditDialog = (index: number) => {
        setEditingIndex(index);
        setForm(rows[index]);
        setDialogOpen(true);
    };

    const removeRow = (index: number) => {
        const nextRows = rows.filter((_, rowIndex) => rowIndex !== index);

        void persistRows(nextRows).then((ok) => {
            if (!ok) {
                return;
            }

            const nextTotalPages = Math.max(1, Math.ceil(nextRows.length / pageSize));

            if (currentPageSafe > nextTotalPages) {
                setCurrentPage(nextTotalPages);
            }
        });
    };

    const saveDialogEntry = async () => {
        if (form.resource_title_id === '' && (! form.learning_resource_type_id || ! form.title || ! form.publisher)) {
            setError('Pick a catalog title, or provide the Type, Title, and Publisher manually.');

            return;
        }

        const nextRows = editingIndex === null
            ? [...rows, form]
            : rows.map((row, index) => (index === editingIndex ? form : row));

        const ok = await persistRows(nextRows);

        if (!ok) {
            return;
        }

        if (editingIndex === null) {
            setCurrentPage(Math.max(1, Math.ceil(nextRows.length / pageSize)));
        } else {
            setCurrentPage(currentPageSafe);
        }

        setDialogOpen(false);
        setError(null);
    };

    return (
        <>
            <Head title="Learning Resources" />

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-lg font-semibold text-foreground">Learning Resources List</h2>
                        <Button type="button" onClick={openAddDialog} disabled={learningResourceTypes.length === 0}>
                            <Plus className="mr-1 h-4 w-4" />
                            Add Entry
                        </Button>
                    </div>

                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted text-left text-foreground">
                                <tr>
                                    <th className="px-3 py-2">Type</th>
                                    <th className="px-3 py-2">Title</th>
                                    <th className="px-3 py-2">Qty Delivered</th>
                                    <th className="px-3 py-2">Qty with Issue</th>
                                    <th className="px-3 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 && (
                                    <EmptyTableRow colSpan={5} message="No learning resources yet. Click Add Entry to create one." />
                                )}
                                {paginatedRows.map((row, index) => {
                                    const absoluteIndex = pageStart + index;

                                    return (
                                        <tr key={`${row.id ?? 'new'}-${index}`} className="border-t border-border">
                                            <td className="px-3 py-2">{row.resource_type}</td>
                                            <td className="px-3 py-2">
                                                <div className="flex items-center gap-2.5">
                                                    {row.cover_image_url ? (
                                                        <img
                                                            src={row.cover_image_url}
                                                            alt={`Cover of ${row.title}`}
                                                            className="h-12 w-9 shrink-0 rounded object-cover shadow-sm"
                                                        />
                                                    ) : (
                                                        <div className="flex h-12 w-9 shrink-0 items-center justify-center rounded bg-muted text-[9px] text-muted-foreground">
                                                            No cover
                                                        </div>
                                                    )}
                                                    <span className="font-medium text-foreground">{row.title}</span>
                                                </div>
                                            </td>
                                            <td className="px-3 py-2">{row.quantity_delivered}</td>
                                            <td className="px-3 py-2">{row.quantity_with_issue_defect}</td>
                                            <td className="px-3 py-2">
                                                <div className="flex items-center justify-end gap-2">
                                                    {row.attachment_url && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-emerald-950"
                                                            aria-label={`Download attachment for ${row.title}`}
                                                            title="Download attachment"
                                                            asChild
                                                        >
                                                            <a href={row.attachment_url} target="_blank" rel="noreferrer" download>
                                                                <Download className="h-3.5 w-3.5" />
                                                            </a>
                                                        </Button>
                                                    )}
                                                    <RowActions
                                                        label={`Actions for ${row.title}`}
                                                        actions={[
                                                            { label: 'View', icon: Eye, onSelect: () => openViewDialog(row) },
                                                            {
                                                                label: 'Edit',
                                                                icon: Pencil,
                                                                onSelect: () => openEditDialog(absoluteIndex),
                                                            },
                                                            {
                                                                label: 'Delete',
                                                                icon: Trash2,
                                                                variant: 'destructive',
                                                                onSelect: () => removeRow(absoluteIndex),
                                                            },
                                                        ]}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {learningResourceTypes.length === 0 && (
                        <p className="mt-3 text-sm text-amber-700">
                            No active learning material types found. Please ask your admin to add at least one type.
                        </p>
                    )}

                    {status && <p className="mt-3 text-sm text-emerald-700">{status}</p>}
                    {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3">
                        <p className="text-sm text-muted-foreground">Rows: {totalRows}</p>

                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setCurrentPage((page) => Math.max(1, page - 1))}
                                disabled={currentPageSafe <= 1 || saving}
                            >
                                Previous
                            </Button>
                            <span className="text-sm text-muted-foreground">
                                Page {currentPageSafe} of {totalPages}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setCurrentPage((page) => Math.min(totalPages, page + 1))}
                                disabled={currentPageSafe >= totalPages || saving}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </section>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-5xl">
                    <DialogHeader>
                        <DialogTitle>{editingIndex === null ? 'Add Learning Resource' : 'Edit Learning Resource'}</DialogTitle>
                        <DialogDescription>
                            Fill in the resource details. Fields marked with * are required.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3">
                        <div>
                            <label htmlFor="catalog_title" className="mb-1 block text-sm font-medium text-foreground">
                                Division Catalog Title
                            </label>
                            <select
                                id="catalog_title"
                                value={form.resource_title_id}
                                onChange={(event) => selectCatalogTitle(event.target.value)}
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">Manual entry (not in catalog)</option>
                                {resourceTitles.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.title}
                                        {option.grade_level ? ` — ${option.grade_level}` : ''}
                                    </option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Pick from the division catalog so details come pre-filled — you only enter quantities.
                            </p>
                        </div>

                        {selectedCatalogTitle && (
                            <div className="flex gap-3 rounded-lg border border-border bg-muted/50 p-3">
                                {selectedCatalogTitle.cover_image_url && (
                                    <img
                                        src={selectedCatalogTitle.cover_image_url}
                                        alt={`Cover of ${selectedCatalogTitle.title}`}
                                        className="h-20 w-14 rounded object-cover shadow-sm"
                                    />
                                )}
                                <div className="text-xs text-muted-foreground">
                                    <p className="text-sm font-medium text-foreground">{selectedCatalogTitle.title}</p>
                                    <p>{[selectedCatalogTitle.author, selectedCatalogTitle.publisher].filter(Boolean).join(' · ') || '-'}</p>
                                    <p>
                                        {[selectedCatalogTitle.resource_type, selectedCatalogTitle.grade_level, selectedCatalogTitle.subject, selectedCatalogTitle.language]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                    {selectedCatalogTitle.isbn && <p>ISBN {selectedCatalogTitle.isbn}</p>}
                                    <div className="mt-1 flex gap-2">
                                        {selectedCatalogTitle.attachment_url && (
                                            <a
                                                href={selectedCatalogTitle.attachment_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-primary underline-offset-2 hover:underline"
                                            >
                                                View attachment
                                            </a>
                                        )}
                                        {selectedCatalogTitle.media_url && (
                                            <a
                                                href={selectedCatalogTitle.media_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-primary underline-offset-2 hover:underline"
                                            >
                                                View media
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {!selectedCatalogTitle && (
                            <div className="grid gap-3 lg:grid-cols-3">
                                <div>
                                    <label htmlFor="resource_type" className="mb-1 block text-sm font-medium text-foreground">
                                        Type *
                                    </label>
                                    <select
                                        id="resource_type"
                                        value={form.learning_resource_type_id}
                                        onChange={(event) =>
                                            setForm((current) => ({
                                                ...current,
                                                learning_resource_type_id: event.target.value === '' ? '' : Number(event.target.value),
                                                resource_type:
                                                    learningResourceTypes.find((type) => type.id === Number(event.target.value))?.name ?? '',
                                            }))
                                        }
                                        className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        required
                                    >
                                        <option value="">Select type</option>
                                        {learningResourceTypes.map((type) => (
                                            <option key={type.id} value={type.id}>
                                                {type.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label htmlFor="title" className="mb-1 block text-sm font-medium text-foreground">
                                        Title *
                                    </label>
                                    <Input
                                        id="title"
                                        value={form.title}
                                        onChange={(event) => setForm((current) => ({ ...current, title: event.target.value }))}
                                        required
                                    />
                                </div>

                                <div>
                                    <label htmlFor="publisher" className="mb-1 block text-sm font-medium text-foreground">
                                        Publisher *
                                    </label>
                                    <Input
                                        id="publisher"
                                        value={form.publisher}
                                        onChange={(event) => setForm((current) => ({ ...current, publisher: event.target.value }))}
                                        required
                                    />
                                </div>
                            </div>
                        )}

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label htmlFor="quantity_delivered" className="mb-1 block text-sm font-medium text-foreground">
                                    Quantity Delivered *
                                </label>
                                <Input
                                    id="quantity_delivered"
                                    type="number"
                                    min={1}
                                    value={form.quantity_delivered}
                                    onChange={(event) =>
                                        setForm((current) => ({
                                            ...current,
                                            quantity_delivered: Number(event.target.value),
                                        }))
                                    }
                                    required
                                />
                            </div>
                            <div>
                                <label htmlFor="quantity_with_issue" className="mb-1 block text-sm font-medium text-foreground">
                                    Quantity with Issue *
                                </label>
                                <Input
                                    id="quantity_with_issue"
                                    type="number"
                                    min={0}
                                    value={form.quantity_with_issue_defect}
                                    onChange={(event) =>
                                        setForm((current) => ({
                                            ...current,
                                            quantity_with_issue_defect: Number(event.target.value),
                                        }))
                                    }
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label htmlFor="remarks" className="mb-1 block text-sm font-medium text-foreground">
                                Remarks
                            </label>
                            <textarea
                                id="remarks"
                                rows={3}
                                value={form.remarks}
                                onChange={(event) => setForm((current) => ({ ...current, remarks: event.target.value }))}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </div>

                        <p className="text-sm font-semibold text-foreground">Delivery Details</p>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label htmlFor="source" className="mb-1 block text-sm font-medium text-foreground">
                                    Source
                                </label>
                                <Input
                                    id="source"
                                    value={form.source}
                                    onChange={(event) => setForm((current) => ({ ...current, source: event.target.value }))}
                                />
                            </div>
                            <div>
                                <label htmlFor="supplier" className="mb-1 block text-sm font-medium text-foreground">
                                    Supplier
                                </label>
                                <Input
                                    id="supplier"
                                    value={form.supplier}
                                    onChange={(event) => setForm((current) => ({ ...current, supplier: event.target.value }))}
                                />
                            </div>
                            <div>
                                <label htmlFor="date_delivered" className="mb-1 block text-sm font-medium text-foreground">
                                    Date Delivered
                                </label>
                                <Input
                                    id="date_delivered"
                                    type="date"
                                    value={form.date_delivered}
                                    onChange={(event) => setForm((current) => ({ ...current, date_delivered: event.target.value }))}
                                />
                            </div>
                            <div>
                                <label htmlFor="ier_no" className="mb-1 block text-sm font-medium text-foreground">
                                    IER No.
                                </label>
                                <Input
                                    id="ier_no"
                                    value={form.ier_no}
                                    onChange={(event) => setForm((current) => ({ ...current, ier_no: event.target.value }))}
                                />
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="button" onClick={saveDialogEntry} disabled={saving}>
                            {saving ? (
                                <>
                                    <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                                    Saving...
                                </>
                            ) : editingIndex === null ? (
                                <>
                                    <Plus className="mr-1 h-4 w-4" />
                                    Add Entry
                                </>
                            ) : (
                                <>
                                    <Pencil className="mr-1 h-4 w-4" />
                                    Save Entry
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={viewDialogOpen} onOpenChange={setViewDialogOpen}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Learning Resource Details</DialogTitle>
                        <DialogDescription>View the complete details of this learning resource record.</DialogDescription>
                    </DialogHeader>

                    {viewRow && (
                        <div className="grid gap-6 sm:grid-cols-[280px_minmax(0,1fr)]">
                            <div className="space-y-3">
                                {viewRow.cover_image_url ? (
                                    <img
                                        src={viewRow.cover_image_url}
                                        alt={`Cover of ${viewRow.title}`}
                                        className="aspect-[3/4] w-full rounded-xl border border-border object-cover shadow-md"
                                    />
                                ) : (
                                    <div className="flex aspect-[3/4] w-full flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-border bg-muted/60 text-center">
                                        <span className="text-sm font-medium text-muted-foreground">No cover image</span>
                                        <span className="px-4 text-xs text-muted-foreground">
                                            Covers come from the division catalog.
                                        </span>
                                    </div>
                                )}

                                {viewRow.attachment_url && (
                                    <Button type="button" className="w-full bg-emerald-600 text-white hover:bg-emerald-700" asChild>
                                        <a href={viewRow.attachment_url} target="_blank" rel="noreferrer" download>
                                            <Download className="mr-1 h-4 w-4" />
                                            Download PDF
                                        </a>
                                    </Button>
                                )}

                                {viewRow.media_url && (
                                    <Button type="button" variant="outline" className="w-full" asChild>
                                        <a href={viewRow.media_url} target="_blank" rel="noreferrer">
                                            <Eye className="mr-1 h-4 w-4" />
                                            View Media
                                        </a>
                                    </Button>
                                )}
                            </div>

                            <div className="min-w-0 space-y-4">
                                <div>
                                    <span className="inline-flex rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                        {viewRow.resource_type || 'Uncategorized'}
                                    </span>
                                    <h3 className="mt-2 text-lg font-semibold leading-snug text-foreground">{viewRow.title}</h3>
                                    <p className="text-sm text-muted-foreground">{viewRow.publisher || '-'}</p>
                                </div>

                                <dl className="grid grid-cols-2 gap-3">
                                    <div className="rounded-lg border border-border bg-muted/40 p-3">
                                        <dt className="text-xs text-muted-foreground">Quantity Delivered</dt>
                                        <dd className="text-xl font-semibold text-foreground">
                                            {viewRow.quantity_delivered.toLocaleString()}
                                        </dd>
                                    </div>
                                    <div className="rounded-lg border border-border bg-muted/40 p-3">
                                        <dt className="text-xs text-muted-foreground">With Issue / Defect</dt>
                                        <dd className={`text-xl font-semibold ${viewRow.quantity_with_issue_defect > 0 ? 'text-destructive' : 'text-foreground'}`}>
                                            {viewRow.quantity_with_issue_defect.toLocaleString()}
                                        </dd>
                                    </div>
                                </dl>

                                <div>
                                    <p className="text-xs font-medium text-muted-foreground">Remarks</p>
                                    <p className="mt-0.5 text-sm text-foreground">{viewRow.remarks || 'No remarks.'}</p>
                                </div>

                                <dl className="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt className="text-xs font-medium text-muted-foreground">Source</dt>
                                        <dd className="text-foreground">{viewRow.source || '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs font-medium text-muted-foreground">Supplier</dt>
                                        <dd className="text-foreground">{viewRow.supplier || '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs font-medium text-muted-foreground">Date Delivered</dt>
                                        <dd className="text-foreground">{viewRow.date_delivered || '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs font-medium text-muted-foreground">IER No.</dt>
                                        <dd className="text-foreground">{viewRow.ier_no || '-'}</dd>
                                    </div>
                                </dl>

                                <p className="text-xs text-muted-foreground">
                                    {viewRow.resource_title_id !== ''
                                        ? 'Details provided by the division resource catalog.'
                                        : 'Manually encoded by the school (not in the division catalog).'}
                                </p>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setViewDialogOpen(false)}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SchoolLearningResources.layout = {
    breadcrumbs: [
        {
            title: 'Learning Resources',
            href: '/school/learning-resources',
        },
    ],
};
