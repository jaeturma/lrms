import { Head } from '@inertiajs/react';

type Props = {
    content: {
        title: string;
        body: string;
    };
};

export default function AboutPage({ content }: Props) {
    return (
        <>
            <Head title="About" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h1 className="text-3xl font-bold text-slate-900">{content.title}</h1>
                    <div
                        className="prose prose-sm mt-6 max-w-none text-slate-700"
                        dangerouslySetInnerHTML={{ __html: content.body }}
                    />
                </section>
            </div>
        </>
    );
}

AboutPage.layout = {
    breadcrumbs: [
        {
            title: 'About the App',
            href: '/about',
        },
    ],
};
