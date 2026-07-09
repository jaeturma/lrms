import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    content: {
        id: number;
        key: string;
        title: string;
        body: string;
    };
    type: 'support' | 'about';
};

export default function EditContent({ content, type }: Props) {
    const [isPreview, setIsPreview] = useState(false);

    const route = type === 'support' ? 'admin.content.update-support' : 'admin.content.update-about';
    const title = type === 'support' ? 'Edit Support' : 'Edit About';

    const { data, setData, post, processing, errors } = useForm({
        title: content.title,
        body: content.body,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route);
    };

    return (
        <>
            <Head title={title} />

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-3xl font-bold text-foreground">{title}</h1>
                    <button
                        onClick={() => setIsPreview(!isPreview)}
                        className="rounded-md border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted/50"
                    >
                        {isPreview ? 'Edit' : 'Preview'}
                    </button>
                </div>

                {isPreview ? (
                    <section className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 className="text-2xl font-bold text-foreground">{data.title}</h2>
                        <div
                            className="prose prose-sm mt-4 max-w-none text-foreground"
                            dangerouslySetInnerHTML={{ __html: data.body }}
                        />
                    </section>
                ) : (
                    <section className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Title</label>
                                <Input
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Enter title"
                                />
                                {errors.title && <p className="mt-1 text-xs text-red-600">{errors.title}</p>}
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Content (HTML)</label>
                                <textarea
                                    value={data.body}
                                    onChange={(e) => setData('body', e.target.value)}
                                    placeholder="Enter content (HTML allowed)"
                                    rows={12}
                                    className="w-full rounded-md border border-border px-3 py-2 font-mono text-sm"
                                />
                                {errors.body && <p className="mt-1 text-xs text-red-600">{errors.body}</p>}
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </section>
                )}
            </div>
        </>
    );
}

EditContent.layout = {
    breadcrumbs: [
        {
            title: 'Content Management',
            href: '/app/admin/dashboard',
        },
    ],
};
