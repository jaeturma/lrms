import { Head, Link, usePage } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import http from '@/lib/http';

type Summary = {
    total_rows: number;
    imported: number;
    skipped: number;
    errors: Array<{ row: number; message: string }>;
};

type Props = {
    summary?: Summary | null;
};

type PageProps = {
    flash?: {
        importSummary?: Summary;
    };
};

export default function SchoolImportPage({ summary }: Props) {
    const page = usePage<PageProps>();
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | undefined>();
    const [importSummary, setImportSummary] = useState<Summary | null>(
        summary ?? page.props.flash?.importSummary ?? null,
    );

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!selectedFile) {
            setError('Please select a CSV file to upload.');

            return;
        }

        setSubmitting(true);
        setError(undefined);

        const formData = new FormData();
        formData.append('csv', selectedFile);

        try {
            const response = await http.post('/app/admin/import/schools', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Accept: 'application/json',
                },
            });

            setImportSummary(response.data.summary);
        } catch {
            setError('Import failed. Confirm CSV format and try again.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <>
            <Head title="School Import" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="mx-auto max-w-4xl space-y-6 rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">School CSV Import</h1>
                            <p className="text-sm text-muted-foreground">
                                Upload a CSV with school_id and school_name required. Municipality, district, and barangay are optional.
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <a href="/app/admin/import/schools/template" className="text-sm font-semibold underline">
                                Download CSV Template
                            </a>
                            <Link href="/app/admin/dashboard" className="text-sm font-semibold underline">
                                Back to Dashboard
                            </Link>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-3 rounded-xl border border-border bg-muted/50 p-4">
                        <input
                            type="file"
                            accept=".csv,text/csv"
                            onChange={(event) => setSelectedFile(event.target.files?.[0] ?? null)}
                        />
                        <InputError message={error} />
                        <Button type="submit" disabled={submitting}>
                            {submitting ? 'Importing...' : 'Import CSV'}
                        </Button>
                    </form>

                    {importSummary && (
                        <section className="rounded-xl border border-border bg-muted/50 p-4">
                            <h2 className="text-lg font-semibold text-foreground">Import Summary</h2>
                            <div className="mt-2 grid gap-2 text-sm text-foreground md:grid-cols-3">
                                <p>Total Rows: {importSummary.total_rows}</p>
                                <p>Imported: {importSummary.imported}</p>
                                <p>Skipped: {importSummary.skipped}</p>
                            </div>

                            {importSummary.errors.length > 0 && (
                                <div className="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    <p className="font-semibold">Validation Report</p>
                                    <ul className="mt-1 list-disc pl-5">
                                        {importSummary.errors.slice(0, 10).map((item, index) => (
                                            <li key={index}>
                                                Row {item.row}: {item.message}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </section>
                    )}
                </div>
            </main>
        </>
    );
}
