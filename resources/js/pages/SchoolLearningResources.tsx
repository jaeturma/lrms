import { Head } from '@inertiajs/react';
import { Eye, Loader2, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
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
    resource_type: string | null;
    title: string;
    publisher: string;
    quantity_delivered: number;
    quantity_with_issue_defect: number;
    remarks?: string | null;
};

type LearningResourceForm = {
    id?: number;
    learning_resource_type_id: number | '';
    resource_type: string;
    title: string;
    publisher: string;
    quantity_delivered: number;
    quantity_with_issue_defect: number;
    remarks: string;
};

type LearningResourceTypeOption = {
    id: number;
    name: string;
};

type Props = {
    learningResources: LearningResource[] | { data: LearningResource[] };
    learningResourceTypes: LearningResourceTypeOption[];
};

export default function SchoolLearningResources({ learningResources, learningResourceTypes }: Props) {
    const initialRows = useMemo<LearningResourceForm[]>(() => {
        const source = Array.isArray(learningResources)
            ? learningResources
            : learningResources.data ?? [];

        return source.map((resource) => ({
            id: resource.id,
            learning_resource_type_id: resource.learning_resource_type_id,
            resource_type: resource.resource_type ?? '',
            title: resource.title,
            publisher: resource.publisher,
            quantity_delivered: Number(resource.quantity_delivered) || 1,
            quantity_with_issue_defect: Number(resource.quantity_with_issue_defect) || 0,
            remarks: resource.remarks ?? '',
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
        resource_type: '',
        title: '',
        publisher: '',
        quantity_delivered: 1,
        quantity_with_issue_defect: 0,
        remarks: '',
    });

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
                    learning_resource_type_id: row.learning_resource_type_id,
                    title: row.title,
                    publisher: row.publisher,
                    quantity_delivered: Number(row.quantity_delivered),
                    quantity_with_issue_defect: Number(row.quantity_with_issue_defect),
                    remarks: row.remarks,
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
                    resource_type: resource.resource_type ?? '',
                    title: resource.title,
                    publisher: resource.publisher,
                    quantity_delivered: Number(resource.quantity_delivered) || 1,
                    quantity_with_issue_defect: Number(resource.quantity_with_issue_defect) || 0,
                    remarks: resource.remarks ?? '',
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
            resource_type: learningResourceTypes[0]?.name ?? '',
            title: '',
            publisher: '',
            quantity_delivered: 1,
            quantity_with_issue_defect: 0,
            remarks: '',
        });
        setDialogOpen(true);
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
        if (! form.learning_resource_type_id || ! form.title || ! form.publisher) {
            setError('Type, Title, and Publisher are required.');

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

            <div className="space-y-6 p-4 md:p-6">
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
                                    <tr>
                                        <td className="px-3 py-6 text-center text-muted-foreground" colSpan={5}>
                                            No learning resources yet. Click Add Entry to create one.
                                        </td>
                                    </tr>
                                )}
                                {paginatedRows.map((row, index) => {
                                    const absoluteIndex = pageStart + index;

                                    return (
                                        <tr key={`${row.id ?? 'new'}-${index}`} className="border-t border-border">
                                            <td className="px-3 py-2">{row.resource_type}</td>
                                            <td className="px-3 py-2">{row.title}</td>
                                            <td className="px-3 py-2">{row.quantity_delivered}</td>
                                            <td className="px-3 py-2">{row.quantity_with_issue_defect}</td>
                                            <td className="px-3 py-2">
                                                <div className="flex gap-2">
                                                    <Button type="button" variant="outline" size="sm" onClick={() => openViewDialog(row)}>
                                                        <Eye className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button type="button" variant="outline" size="sm" onClick={() => openEditDialog(absoluteIndex)}>
                                                        <Pencil className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="border-red-300 text-red-700 hover:bg-red-50"
                                                        onClick={() => removeRow(absoluteIndex)}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
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
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingIndex === null ? 'Add Learning Resource' : 'Edit Learning Resource'}</DialogTitle>
                        <DialogDescription>
                            Fill in the resource details. Fields marked with * are required.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3">
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
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Learning Resource Details</DialogTitle>
                        <DialogDescription>View the complete details of this learning resource record.</DialogDescription>
                    </DialogHeader>

                    {viewRow && (
                        <div className="grid gap-2 text-sm text-foreground">
                            <p><span className="font-semibold text-foreground">Type:</span> {viewRow.resource_type}</p>
                            <p><span className="font-semibold text-foreground">Title:</span> {viewRow.title}</p>
                            <p><span className="font-semibold text-foreground">Publisher:</span> {viewRow.publisher}</p>
                            <p><span className="font-semibold text-foreground">Quantity Delivered:</span> {viewRow.quantity_delivered}</p>
                            <p><span className="font-semibold text-foreground">Quantity with Issue:</span> {viewRow.quantity_with_issue_defect}</p>
                            <p><span className="font-semibold text-foreground">Remarks:</span> {viewRow.remarks || '-'}</p>
                        </div>
                    )}

                    <DialogFooter>
                        <Button type="button" onClick={() => setViewDialogOpen(false)}>
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
