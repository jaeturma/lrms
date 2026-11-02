import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';

type PrintPageProps = {
    title: string;
    backHref: string;
    backLabel: string;
    orientation?: 'portrait' | 'landscape';
    children: ReactNode;
};

/**
 * Bare, always-light page for printing: a screen-only toolbar plus content
 * laid out for A4 paper.
 */
export function PrintPage({
    title,
    backHref,
    backLabel,
    orientation = 'landscape',
    children,
}: PrintPageProps) {
    return (
        <>
            <Head title={title} />
            <style>{`
                @page { size: A4 ${orientation}; margin: 10mm; }
                @media print {
                    html, body { background: #fff !important; }
                    .print-sheet tr, .print-sheet .print-avoid-break { break-inside: avoid; }
                    .print-sheet thead { display: table-header-group; }
                }
            `}</style>

            <div className="min-h-screen bg-neutral-100 text-black print:bg-white">
                <div className="sticky top-0 z-10 flex items-center justify-between gap-2 border-b border-neutral-300 bg-white px-4 py-2 print:hidden">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={backHref}>
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            {backLabel}
                        </Link>
                    </Button>
                    <p className="hidden text-sm text-neutral-600 sm:block">
                        Print preview — use A4{' '}
                        {orientation === 'landscape' ? 'landscape' : 'portrait'}
                        .
                    </p>
                    <Button size="sm" onClick={() => window.print()}>
                        <Printer className="mr-1 h-4 w-4" />
                        Print
                    </Button>
                </div>

                <div className="print-sheet mx-auto my-4 max-w-[297mm] bg-white p-6 text-[11px] leading-snug shadow print:m-0 print:max-w-none print:p-0 print:shadow-none">
                    {children}
                </div>
            </div>
        </>
    );
}

export function PrintSection({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="mt-4">
            <h2 className="mb-1 border-b-2 border-black pb-0.5 text-[12px] font-bold tracking-wide uppercase">
                {title}
            </h2>
            {children}
        </section>
    );
}

export const printTable = 'w-full border-collapse';
export const printCell = 'border border-neutral-500 px-1.5 py-1 align-top';
export const printHead =
    'border border-neutral-500 bg-neutral-100 px-1.5 py-1 text-left align-bottom font-semibold';
