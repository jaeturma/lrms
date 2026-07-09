import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Check, Eye, Pencil, School, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { SearchInput } from '@/components/search-input';
import { StatusBadge } from '@/components/status-badge';
import type { StatusTone } from '@/components/status-badge';
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
    activation_requested_at?: string | null;
    learning_resources_count: number;
};

type Paginator<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    districts: District[];
    filters: {
        search?: string;
        district_id?: number | null;
    };
    schools: Paginator<SchoolRow>;
};

function schoolStatusTone(row: SchoolRow): StatusTone {
    if (row.is_activated) {
        return 'success';
    }

    if (row.activation_requested_at) {
        return 'info';
    }

    return 'warning';
}

export default function AdminSchoolsIndex({ districts, filters, schools }: Props) {
    const deleteSchool = (schoolId: string) => {
        if (!window.confirm('Delete this school record? This cannot be undone.')) {
            return;
        }

        router.delete(`/app/admin/schools/${schoolId}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('School removed.'),
        });
    };

    const columns: ColumnDef<SchoolRow>[] = [
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
                <StatusBadge tone={schoolStatusTone(row.original)}>
                    {row.original.is_activated
                        ? 'Activated'
                        : row.original.activation_requested_at
                          ? 'Requested'
                          : 'Pending'}
                </StatusBadge>
            ),
        },
        {
            accessorKey: 'learning_resources_count',
            header: 'Resources',
        },
        {
            id: 'actions',
            header: 'Actions',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/app/admin/schools/${row.original.school_id}`}>
                            <Eye className="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/app/admin/schools/${row.original.school_id}/edit`}>
                            <Pencil className="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                    {!row.original.is_activated && row.original.activation_requested_at && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                            onClick={() =>
                                router.post(
                                    `/app/admin/schools/${row.original.school_id}/manual-activate`,
                                    {},
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => toast.success('School activated.'),
                                    },
                                )
                            }
                        >
                            <Check className="h-3.5 w-3.5" />
                        </Button>
                    )}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="border-red-300 text-red-700 hover:bg-red-50"
                        onClick={() => deleteSchool(row.original.school_id)}
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Schools Management" />

            <div className="space-y-6 bg-muted/50 p-4 md:p-6">
                <PageHeader
                    icon={School}
                    iconClassName="bg-blue-950 text-blue-400 dark:bg-blue-900/60 dark:text-blue-300"
                    title="Schools Management"
                    description="Create, update, and remove schools."
                    className="border-input bg-background"
                    actions={
                        <>
                            <Link
                                href="/app/admin/import/schools"
                                className="rounded-md border border-input bg-background px-4 py-2 text-sm text-foreground"
                            >
                                Import CSV
                            </Link>
                            <Link
                                href="/app/admin/schools/create"
                                className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                            >
                                Add School
                            </Link>
                        </>
                    }
                />

                <section className="rounded-2xl border border-input bg-background p-5 shadow-sm">
                    <form method="get" action="/app/admin/schools" className="mb-4 flex flex-wrap gap-2">
                        <SearchInput
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Search school name or ID"
                            className="h-10"
                            containerClassName="w-72"
                        />
                        <select
                            name="district_id"
                            defaultValue={filters.district_id ?? ''}
                            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All Districts</option>
                            {districts.map((district) => (
                                <option key={district.id} value={district.id}>
                                    {district.name}
                                </option>
                            ))}
                        </select>
                        <button type="submit" className="h-10 rounded-md bg-primary px-4 text-sm text-primary-foreground">
                            Filter
                        </button>
                    </form>

                    <DataTable
                        columns={columns}
                        data={schools.data}
                        searchPlaceholder="Search in results…"
                        searchColumn="school_name"
                    />

                    <Pagination links={schools.links} className="mt-4" />
                </section>
            </div>
        </>
    );
}
