import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type LearningResourceType = {
    id: number;
    name: string;
    is_active: boolean;
};

type Props = {
    learningResourceTypes: LearningResourceType[];
};

export default function AdminLearningResourceTypes({ learningResourceTypes }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/app/admin/learning-resource-types', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const toggleType = (id: number, name: string, isActive: boolean) => {
        router.put(
            `/app/admin/learning-resource-types/${id}`,
            {
                name,
                is_active: !isActive,
            },
            { preserveScroll: true },
        );
    };

    const deleteType = (id: number) => {
        router.delete(`/app/admin/learning-resource-types/${id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Learning Material Types" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-5xl space-y-6">
                    <header className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h1 className="text-2xl font-bold text-foreground">Learning Material Types</h1>
                        <p className="text-sm text-muted-foreground">Create, activate/deactivate, and remove material types used in school encoding.</p>
                    </header>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form onSubmit={submit} className="flex flex-wrap gap-2">
                            <Input
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="Add type (e.g. Textbook)"
                                className="min-w-72 border-border"
                            />
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Adding...' : 'Add Type'}
                            </Button>
                        </form>
                        {errors.name && <p className="mt-2 text-sm text-red-600">{errors.name}</p>}
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="grid gap-3 md:grid-cols-2">
                            {learningResourceTypes.map((type) => (
                                <div key={type.id} className="flex items-center justify-between rounded-xl border border-border bg-muted p-4">
                                    <div>
                                        <p className="font-semibold text-foreground">{type.name}</p>
                                        <p className="text-xs text-muted-foreground">{type.is_active ? 'Active' : 'Inactive'}</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => toggleType(type.id, type.name, type.is_active)}
                                        >
                                            <Pencil className="h-3.5 w-3.5" />
                                            {type.is_active ? 'Deactivate' : 'Activate'}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            className="border-red-300 text-red-700 hover:bg-red-50"
                                            onClick={() => deleteType(type.id)}
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                            Delete
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