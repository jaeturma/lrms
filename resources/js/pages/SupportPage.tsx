import { Head } from '@inertiajs/react';

type Props = {
    content: {
        title: string;
        body: string;
    };
};

export default function SupportPage({ content }: Props) {
    return (
        <>
            <Head title="Support" />

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <section className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <h1 className="text-3xl font-bold text-foreground">{content.title}</h1>
                    <div
                        className="prose prose-sm mt-6 max-w-none text-foreground"
                        dangerouslySetInnerHTML={{ __html: content.body }}
                    />
                </section>
            </div>
        </>
    );
}

SupportPage.layout = {
    breadcrumbs: [
        {
            title: 'Support',
            href: '/support',
        },
    ],
};
