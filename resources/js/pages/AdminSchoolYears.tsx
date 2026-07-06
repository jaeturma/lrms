import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle2, Pencil, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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

type SchoolYear = {
    id: number;
    name: string;
    starts_on: string | null;
    ends_on: string | null;
    is_active: boolean;
    enrollments_count: number;
};

type Props = {
    schoolYears: SchoolYear[];
};

export default function AdminSchoolYears({ schoolYears }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        starts_on: '',
        ends_on: '',
    });

    const [editing, setEditing] = useState<SchoolYear | null>(null);
    const [editForm, setEditForm] = useState({ name: '', starts_on: '', ends_on: '' });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/app/admin/school-years', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const openEdit = (schoolYear: SchoolYear) => {
        setEditing(schoolYear);
        setEditForm({
            name: schoolYear.name,
            starts_on: schoolYear.starts_on ?? '',
            ends_on: schoolYear.ends_on ?? '',
        });
    };

    const saveEdit = () => {
        if (!editing) {
            return;
        }

        router.put(`/app/admin/school-years/${editing.id}`, editForm, {
            preserveScroll: true,
            onSuccess: () => setEditing(null),
        });
    };

    const activate = (id: number) => {
        router.post(`/app/admin/school-years/${id}/activate`, {}, { preserveScroll: true });
    };

    const deleteSchoolYear = (id: number) => {
        router.delete(`/app/admin/school-years/${id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="School Years" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-5xl space-y-6">
                    <header className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h1 className="text-2xl font-bold text-foreground">School Years</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage school years and set the active school year used for enrollment and reports.
                        </p>
                    </header>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                            <div>
                                <label htmlFor="sy-name" className="mb-1 block text-sm font-medium text-foreground">
                                    School Year *
                                </label>
                                <Input
                                    id="sy-name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    placeholder="e.g. 2026-2027"
                                    className="w-40 border-border"
                                />
                            </div>
                            <div>
                                <label htmlFor="sy-starts" className="mb-1 block text-sm font-medium text-foreground">
                                    Starts On
                                </label>
                                <Input
                                    id="sy-starts"
                                    type="date"
                                    value={data.starts_on}
                                    onChange={(event) => setData('starts_on', event.target.value)}
                                    className="w-44 border-border"
                                />
                            </div>
                            <div>
                                <label htmlFor="sy-ends" className="mb-1 block text-sm font-medium text-foreground">
                                    Ends On
                                </label>
                                <Input
                                    id="sy-ends"
                                    type="date"
                                    value={data.ends_on}
                                    onChange={(event) => setData('ends_on', event.target.value)}
                                    className="w-44 border-border"
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Adding...' : 'Add School Year'}
                            </Button>
                        </form>
                        {errors.name && <p className="mt-2 text-sm text-red-600">{errors.name}</p>}
                        {errors.starts_on && <p className="mt-2 text-sm text-red-600">{errors.starts_on}</p>}
                        {errors.ends_on && <p className="mt-2 text-sm text-red-600">{errors.ends_on}</p>}
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        {schoolYears.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No school years yet. Add the current school year to enable enrollment encoding.
                            </p>
                        )}

                        <div className="grid gap-3 md:grid-cols-2">
                            {schoolYears.map((schoolYear) => (
                                <div
                                    key={schoolYear.id}
                                    className="flex items-center justify-between rounded-xl border border-border bg-muted p-4"
                                >
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <p className="font-semibold text-foreground">SY {schoolYear.name}</p>
                                            {schoolYear.is_active && (
                                                <Badge className="bg-emerald-600 text-white hover:bg-emerald-600">Active</Badge>
                                            )}
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {schoolYear.starts_on ?? 'No start date'} — {schoolYear.ends_on ?? 'No end date'}
                                            {' · '}
                                            {schoolYear.enrollments_count} enrollment record{schoolYear.enrollments_count === 1 ? '' : 's'}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        {!schoolYear.is_active && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => activate(schoolYear.id)}
                                            >
                                                <CheckCircle2 className="h-3.5 w-3.5" />
                                                Set Active
                                            </Button>
                                        )}
                                        <Button type="button" variant="outline" size="sm" onClick={() => openEdit(schoolYear)}>
                                            <Pencil className="h-3.5 w-3.5" />
                                        </Button>
                                        {!schoolYear.is_active && schoolYear.enrollments_count === 0 && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="border-red-300 text-red-700 hover:bg-red-50"
                                                onClick={() => deleteSchoolYear(schoolYear.id)}
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </main>

            <Dialog open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit School Year</DialogTitle>
                        <DialogDescription>Update the school year name and dates.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-3">
                        <div>
                            <label htmlFor="edit-sy-name" className="mb-1 block text-sm font-medium text-foreground">
                                School Year *
                            </label>
                            <Input
                                id="edit-sy-name"
                                value={editForm.name}
                                onChange={(event) => setEditForm((current) => ({ ...current, name: event.target.value }))}
                            />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label htmlFor="edit-sy-starts" className="mb-1 block text-sm font-medium text-foreground">
                                    Starts On
                                </label>
                                <Input
                                    id="edit-sy-starts"
                                    type="date"
                                    value={editForm.starts_on}
                                    onChange={(event) => setEditForm((current) => ({ ...current, starts_on: event.target.value }))}
                                />
                            </div>
                            <div>
                                <label htmlFor="edit-sy-ends" className="mb-1 block text-sm font-medium text-foreground">
                                    Ends On
                                </label>
                                <Input
                                    id="edit-sy-ends"
                                    type="date"
                                    value={editForm.ends_on}
                                    onChange={(event) => setEditForm((current) => ({ ...current, ends_on: event.target.value }))}
                                />
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setEditing(null)}>
                            Cancel
                        </Button>
                        <Button type="button" onClick={saveEdit}>
                            Save Changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
