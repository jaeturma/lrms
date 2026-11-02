import { Head, Link } from '@inertiajs/react';
import { ClipboardCheck, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyTableRow } from '@/components/empty-state';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import http from '@/lib/http';

type SchoolRow = {
    beis_school_id: string;
    school_name: string;
    district: string | null;
    municipality: string | null;
    curricular_offering: string | null;
    in_lrms: boolean;
    visits_count: number;
};

type RecentVisit = {
    id: number;
    status: string;
    visited_at: string | null;
    updated_at: string | null;
    beis_school_id: string;
    school_name: string;
    monitor: string | null;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    total: number;
};

type Props = {
    filters: { search?: string };
    schools: Paginator<SchoolRow>;
    recentVisits: RecentVisit[];
    divisions: string[];
    stats: Record<string, number>;
    canImport: boolean;
};

const importTypes = [
    {
        value: 'reference_data',
        label: '1. Reference data (_reference_data.xlsx)',
        help: 'Schools, LR allocations, and regional titles/publishers. Keeps only the division you enter.',
    },
    {
        value: 'lrmis_encoded',
        label: '2. LRMIS encoded (LRMIS_ENCODED.xlsx)',
        help: 'Total LRs each school encoded in LRMIS.',
    },
    {
        value: 'sme_masterlist',
        label: '3. SME masterlist (masterlist lte.xlsx)',
        help: 'Years each key stage received its SME package.',
    },
    {
        value: 'monitoring_tool',
        label: '4. Monitoring tool (VALID-LR_Monitoring_Tool.xlsx)',
        help: 'SME Central Office / Downloaded item lists, TVL packages, and TVL procurement status.',
    },
];

const statLabels: Array<[string, string]> = [
    ['schools', 'Schools'],
    ['allocations', 'LR allocations'],
    ['titles', 'Regional titles'],
    ['lrmis_counts', 'LRMIS records'],
    ['sme_schools', 'Schools with SME'],
    ['sme_items', 'SME package items'],
    ['tvl_programs', 'TVL specializations'],
    ['visits', 'Monitoring visits'],
];

export default function AdminValidLr({
    filters,
    schools,
    recentVisits,
    divisions,
    stats,
    canImport,
}: Props) {
    const [importType, setImportType] = useState(importTypes[0].value);
    const [division, setDivision] = useState(divisions[0] ?? '');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [importing, setImporting] = useState(false);
    const [importError, setImportError] = useState<string | undefined>();
    const [importSummary, setImportSummary] = useState<Record<
        string,
        string | number
    > | null>(null);

    const submitImport = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!selectedFile) {
            setImportError('Please select the Excel .xlsx workbook.');

            return;
        }

        setImporting(true);
        setImportError(undefined);
        setImportSummary(null);

        const formData = new FormData();
        formData.append('type', importType);
        formData.append('file', selectedFile);

        if (importType === 'reference_data') {
            formData.append('division', division);
        }

        try {
            const response = await http.post(
                '/app/admin/valid-lr/import',
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        Accept: 'application/json',
                    },
                },
            );

            setImportSummary(response.data.summary);
        } catch (error) {
            const errors = (
                error as {
                    response?: { data?: { errors?: Record<string, string[]> } };
                }
            ).response?.data?.errors;

            setImportError(
                errors
                    ? Object.values(errors).flat().join(' ')
                    : 'Import failed. Check that you selected the right workbook and try again.',
            );
        } finally {
            setImporting(false);
        }
    };

    const currentType = importTypes.find((type) => type.value === importType);

    return (
        <>
            <Head title="VALID-LR Monitoring" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <PageHeader
                        icon={ClipboardCheck}
                        iconClassName="bg-teal-100 text-teal-600 dark:bg-teal-900/60 dark:text-teal-300"
                        title="VALID-LR Monitoring"
                        description="Validation and inventory of delivered learning resources, SME and TVL packages. Enter a School ID to load its allocation, LRMIS, SME and TVL records, then record a monitoring visit."
                    />

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <form
                            method="get"
                            action="/app/admin/valid-lr"
                            className="flex flex-wrap items-end gap-2"
                        >
                            <input type="hidden" name="lookup" value="1" />
                            <div className="grid gap-1">
                                <label
                                    htmlFor="valid-lr-search"
                                    className="text-sm font-medium text-foreground"
                                >
                                    School ID or name
                                </label>
                                <Input
                                    id="valid-lr-search"
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="e.g. 304205"
                                    className="w-72"
                                />
                            </div>
                            <Button type="submit">
                                <Search className="mr-1 h-4 w-4" />
                                Look up
                            </Button>
                        </form>

                        <dl className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                            {statLabels.map(([key, label]) => (
                                <div
                                    key={key}
                                    className="rounded-xl border border-border bg-muted/40 px-3 py-2"
                                >
                                    <dt className="text-xs text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="text-lg font-semibold text-foreground tabular-nums">
                                        {(stats[key] ?? 0).toLocaleString()}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>

                    {canImport && (
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="text-lg font-semibold text-foreground">
                                Import Reference Workbooks
                            </h2>
                            <p className="mb-4 text-sm text-muted-foreground">
                                Upload the workbooks in order. Steps 2–4 keep
                                only schools loaded by step 1. Re-importing
                                replaces the previous data; saved visits keep
                                their own snapshot.
                            </p>
                            <form
                                onSubmit={submitImport}
                                className="grid gap-3 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end"
                            >
                                <div className="grid gap-1">
                                    <label
                                        htmlFor="valid-lr-import-type"
                                        className="text-sm font-medium text-foreground"
                                    >
                                        Workbook
                                    </label>
                                    <select
                                        id="valid-lr-import-type"
                                        value={importType}
                                        onChange={(event) =>
                                            setImportType(event.target.value)
                                        }
                                        className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                    >
                                        {importTypes.map((type) => (
                                            <option
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="grid gap-1">
                                    <label
                                        htmlFor="valid-lr-division"
                                        className="text-sm font-medium text-foreground"
                                    >
                                        Division
                                    </label>
                                    <Input
                                        id="valid-lr-division"
                                        list="valid-lr-divisions"
                                        value={division}
                                        onChange={(event) =>
                                            setDivision(event.target.value)
                                        }
                                        disabled={
                                            importType !== 'reference_data'
                                        }
                                        placeholder="e.g. Davao De Oro"
                                    />
                                    <datalist id="valid-lr-divisions">
                                        {divisions.map((name) => (
                                            <option key={name} value={name} />
                                        ))}
                                    </datalist>
                                </div>
                                <input
                                    type="file"
                                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    onChange={(event) =>
                                        setSelectedFile(
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="text-sm"
                                />
                                <Button type="submit" disabled={importing}>
                                    {importing ? 'Importing...' : 'Import'}
                                </Button>
                            </form>
                            <p className="mt-2 text-xs text-muted-foreground">
                                {currentType?.help} Large workbooks can take up
                                to a minute.
                            </p>
                            <InputError message={importError} />
                            {importSummary && (
                                <div className="mt-3 flex flex-wrap gap-4 rounded-md border border-border bg-muted/50 p-3 text-sm text-foreground">
                                    {Object.entries(importSummary).map(
                                        ([key, value]) => (
                                            <span key={key}>
                                                {key.replaceAll('_', ' ')}:{' '}
                                                <strong>
                                                    {typeof value === 'number'
                                                        ? value.toLocaleString()
                                                        : value}
                                                </strong>
                                            </span>
                                        ),
                                    )}
                                </div>
                            )}
                        </section>
                    )}

                    <div className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="mb-3 text-lg font-semibold text-foreground">
                                Schools ({schools.total.toLocaleString()})
                            </h2>
                            <div className="overflow-x-auto rounded-xl border border-border">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="px-3 py-2">
                                                School ID
                                            </th>
                                            <th className="px-3 py-2">
                                                School
                                            </th>
                                            <th className="px-3 py-2">
                                                District
                                            </th>
                                            <th className="px-3 py-2">
                                                Offering
                                            </th>
                                            <th className="px-3 py-2">
                                                Visits
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {schools.data.length === 0 && (
                                            <EmptyTableRow
                                                colSpan={5}
                                                message="No schools loaded yet. Import the reference data for your division first."
                                            />
                                        )}
                                        {schools.data.map((school) => (
                                            <tr
                                                key={school.beis_school_id}
                                                className="border-t border-border"
                                            >
                                                <td className="px-3 py-2 tabular-nums">
                                                    <Link
                                                        href={`/app/admin/valid-lr/schools/${school.beis_school_id}`}
                                                        className="font-semibold text-primary underline-offset-2 hover:underline"
                                                    >
                                                        {school.beis_school_id}
                                                    </Link>
                                                </td>
                                                <td className="px-3 py-2 text-foreground">
                                                    {school.school_name}
                                                    {school.in_lrms && (
                                                        <StatusBadge
                                                            tone="info"
                                                            className="ml-2"
                                                        >
                                                            In LRMS
                                                        </StatusBadge>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-muted-foreground">
                                                    {school.district ?? '-'}
                                                </td>
                                                <td className="px-3 py-2 text-muted-foreground">
                                                    {school.curricular_offering ??
                                                        '-'}
                                                </td>
                                                <td className="px-3 py-2 tabular-nums">
                                                    {school.visits_count}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <Pagination
                                links={schools.links}
                                className="mt-4"
                            />
                        </section>

                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="mb-3 text-lg font-semibold text-foreground">
                                Recent Visits
                            </h2>
                            {recentVisits.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No monitoring visits recorded yet.
                                </p>
                            )}
                            <ul className="divide-y divide-border">
                                {recentVisits.map((visit) => (
                                    <li key={visit.id} className="py-2">
                                        <Link
                                            href={`/app/admin/valid-lr/visits/${visit.id}/edit`}
                                            className="font-medium text-foreground hover:underline"
                                        >
                                            {visit.school_name}
                                        </Link>
                                        <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                            <span>{visit.beis_school_id}</span>
                                            <span>
                                                {visit.visited_at ?? '-'}
                                            </span>
                                            {visit.monitor && (
                                                <span>{visit.monitor}</span>
                                            )}
                                            <StatusBadge
                                                tone={
                                                    visit.status === 'completed'
                                                        ? 'success'
                                                        : 'warning'
                                                }
                                            >
                                                {visit.status}
                                            </StatusBadge>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    </div>
                </div>
            </main>
        </>
    );
}
