import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2 } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { Button } from '@/components/ui/button';

type District = {
    id: number;
    name: string;
};

type SchoolRow = {
    school_id: string;
    school_name: string;
    district: string;
    municipality: string;
    barangay: string;
    is_activated: boolean;
    email?: string | null;
    learning_resources_count: number;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    stats: {
        total_schools: number;
        activated_schools: number;
        pending_schools: number;
        total_learning_resources: number;
        total_learners: number;
    };
    activeSchoolYear: { id: number; name: string } | null;
    districts: District[];
    filters: {
        search?: string;
        district_id?: number | null;
    };
    reportsByDistrict: Array<{ district: string; school_count: number }>;
    schools: Paginator<SchoolRow>;
};

export default function AdminDashboard({
    stats,
    activeSchoolYear,
    districts,
    filters,
    reportsByDistrict,
    schools,
}: Props) {
    const schoolColumns: ColumnDef<SchoolRow>[] = [
        { accessorKey: 'school_id', header: 'School ID' },
        { accessorKey: 'school_name', header: 'School Name' },
        { accessorKey: 'district', header: 'District' },
        {
            id: 'municipality_barangay',
            header: 'Municipality/Barangay',
            accessorFn: (row) => `${row.municipality ?? '-'} - ${row.barangay ?? '-'}`,
            cell: ({ row }) => `${row.original.municipality ?? '-'} - ${row.original.barangay ?? '-'}`,
        },
        {
            accessorKey: 'is_activated',
            header: 'Status',
            cell: ({ row }) => (
                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                    row.original.is_activated
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'
                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
                }`}>
                    {row.original.is_activated ? 'Activated' : 'Pending'}
                </span>
            ),
        },
        { accessorKey: 'learning_resources_count', header: 'Resources' },
        {
            id: 'actions',
            header: 'Actions',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/app/admin/schools/${row.original.school_id}/edit`}>
                            <Pencil className="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40"
                        onClick={() => deleteSchool(row.original.school_id)}
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            ),
        },
    ];

    const deleteSchool = (schoolId: string) => {
        if (!window.confirm('Delete this school record? This cannot be undone.')) {
            return;
        }

        router.delete(`/app/admin/schools/${schoolId}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="space-y-6 bg-background/40 p-4 md:p-6">
                <header className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Admin Dashboard</h1>
                        <p className="text-sm text-muted-foreground">Monitor school activation and learning resource submissions.</p>
                    </div>
                </header>

                <section className="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
                    <StatCard
                        label="Total Schools"
                        value={stats.total_schools}
                        accentClassName="border-sky-300/70 bg-gradient-to-br from-sky-50 to-cyan-100 text-sky-900 dark:border-sky-800 dark:from-sky-950/45 dark:to-cyan-950/45 dark:text-sky-100"
                    />
                    <StatCard
                        label="Activated Schools"
                        value={stats.activated_schools}
                        accentClassName="border-emerald-300/70 bg-gradient-to-br from-emerald-50 to-green-100 text-emerald-900 dark:border-emerald-800 dark:from-emerald-950/45 dark:to-green-950/45 dark:text-emerald-100"
                    />
                    <StatCard
                        label="Pending Schools"
                        value={stats.pending_schools}
                        accentClassName="border-amber-300/70 bg-gradient-to-br from-amber-50 to-orange-100 text-amber-900 dark:border-amber-800 dark:from-amber-950/45 dark:to-orange-950/45 dark:text-amber-100"
                    />
                    <StatCard
                        label="Total Resources"
                        value={stats.total_learning_resources}
                        accentClassName="border-fuchsia-300/70 bg-gradient-to-br from-fuchsia-50 to-pink-100 text-fuchsia-900 dark:border-fuchsia-800 dark:from-fuchsia-950/45 dark:to-pink-950/45 dark:text-fuchsia-100"
                    />
                    <StatCard
                        label={activeSchoolYear ? `Learners · SY ${activeSchoolYear.name}` : 'Learners (No Active SY)'}
                        value={stats.total_learners}
                        accentClassName="border-violet-300/70 bg-gradient-to-br from-violet-50 to-indigo-100 text-violet-900 dark:border-violet-800 dark:from-violet-950/45 dark:to-indigo-950/45 dark:text-violet-100"
                    />
                </section>

                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 className="mb-3 text-lg font-semibold text-foreground">Reports by District</h2>
                    <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-3">
                        {reportsByDistrict.map((item) => (
                            <div key={item.district} className="rounded-lg border border-violet-300/70 bg-gradient-to-br from-violet-50 to-indigo-100 p-3 dark:border-violet-800 dark:from-violet-950/40 dark:to-indigo-950/40">
                                <p className="font-semibold text-foreground">{item.district}</p>
                                <p>{item.school_count} schools</p>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-foreground">Schools</h2>
                        <form method="get" action="/app/admin/dashboard" className="flex flex-wrap gap-2">
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search school name or ID"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            />
                            <select
                                name="district_id"
                                defaultValue={filters.district_id ?? ''}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">All Districts</option>
                                {districts.map((district) => (
                                    <option key={district.id} value={district.id}>
                                        {district.name}
                                    </option>
                                ))}
                            </select>
                            <button type="submit" className="h-9 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                                Filter
                            </button>
                        </form>
                    </div>

                    <DataTable
                        columns={schoolColumns}
                        data={schools.data}
                        searchPlaceholder="Search in results…"
                        searchColumn="school_name"
                    />

                    {schools.links.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-2 text-sm">
                            {schools.links.map((link, index) => (
                                <span key={index}>
                                    {link.url ? (
                                        <Link
                                            href={link.url}
                                            className={`rounded border px-3 py-1 ${link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-foreground'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span
                                            className="rounded border border-border bg-card px-3 py-1 text-muted-foreground"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )}
                                </span>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    accentClassName,
}: {
    label: string;
    value: number;
    accentClassName: string;
}) {
    return (
        <article className={`rounded-2xl border p-4 shadow-sm ${accentClassName}`}>
            <p className="text-xs uppercase tracking-wide text-current/75">{label}</p>
            <p className="mt-2 text-3xl font-bold text-current">{value}</p>
        </article>
    );
}
