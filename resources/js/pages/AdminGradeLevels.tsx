import { Head, router, useForm } from '@inertiajs/react';
import { GraduationCap, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type GradeLevel = {
    id: number;
    name: string;
    sort_order: number;
    is_active: boolean;
    enrollments_count: number;
};

type Props = {
    gradeLevels: GradeLevel[];
};

export default function AdminGradeLevels({ gradeLevels }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        sort_order: gradeLevels.length,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/app/admin/grade-levels', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                toast.success('Grade level added.');
            },
        });
    };

    const toggleGradeLevel = (gradeLevel: GradeLevel) => {
        router.put(
            `/app/admin/grade-levels/${gradeLevel.id}`,
            {
                name: gradeLevel.name,
                sort_order: gradeLevel.sort_order,
                is_active: !gradeLevel.is_active,
            },
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(gradeLevel.is_active ? 'Grade level deactivated.' : 'Grade level activated.'),
            },
        );
    };

    const deleteGradeLevel = (id: number) => {
        router.delete(`/app/admin/grade-levels/${id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Grade level removed.'),
        });
    };

    return (
        <>
            <Head title="Grade Levels" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-5xl space-y-6">
                    <PageHeader
                        icon={GraduationCap}
                        iconClassName="bg-violet-950 text-violet-400 dark:bg-violet-900/60 dark:text-violet-300"
                        title="Grade Levels"
                        description="Manage the grade levels available for school enrollment encoding."
                    />

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
                            <div>
                                <label htmlFor="gl-name" className="mb-1 block text-sm font-medium text-foreground">
                                    Grade Level *
                                </label>
                                <Input
                                    id="gl-name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    placeholder="e.g. Grade 7"
                                    className="w-56 border-border"
                                />
                            </div>
                            <div>
                                <label htmlFor="gl-sort" className="mb-1 block text-sm font-medium text-foreground">
                                    Sort Order *
                                </label>
                                <Input
                                    id="gl-sort"
                                    type="number"
                                    min={0}
                                    value={data.sort_order}
                                    onChange={(event) => setData('sort_order', Number(event.target.value))}
                                    className="w-28 border-border"
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Adding...' : 'Add Grade Level'}
                            </Button>
                        </form>
                        {errors.name && <p className="mt-2 text-sm text-red-600">{errors.name}</p>}
                        {errors.sort_order && <p className="mt-2 text-sm text-red-600">{errors.sort_order}</p>}
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        {gradeLevels.length === 0 && (
                            <EmptyState message="No grade levels yet. Add one above to enable enrollment encoding." />
                        )}

                        <div className="grid gap-3 md:grid-cols-2">
                            {gradeLevels.map((gradeLevel) => (
                                <div
                                    key={gradeLevel.id}
                                    className="flex items-center justify-between rounded-xl border border-border bg-muted p-4"
                                >
                                    <div>
                                        <p className="font-semibold text-foreground">{gradeLevel.name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {gradeLevel.is_active ? 'Active' : 'Inactive'} · Order {gradeLevel.sort_order}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => toggleGradeLevel(gradeLevel)}
                                        >
                                            {gradeLevel.is_active ? 'Deactivate' : 'Activate'}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="border-red-300 text-red-700 hover:bg-red-50"
                                            onClick={() => deleteGradeLevel(gradeLevel.id)}
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
