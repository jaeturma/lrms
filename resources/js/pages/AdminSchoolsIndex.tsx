import { Head, Link, router } from '@inertiajs/react';

type District = {
    id: number;
    name: string;
};

type SchoolRow = {
    school_id: string;
    school_name: string;
    district: string;
    municipality: string;
    is_activated: boolean;
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

export default function AdminSchoolsIndex({ districts, filters, schools }: Props) {
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
            <Head title="Schools Management" />

            <main className="min-h-screen bg-slate-50 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Schools Management</h1>
                            <p className="text-sm text-slate-600">Create, update, and remove schools from one place.</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Link href="/app/admin/schools/create" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white">
                                Add School
                            </Link>
                            <Link href="/app/admin/dashboard" className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700">
                                Back to Dashboard
                            </Link>
                        </div>
                    </header>

                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <form method="get" action="/app/admin/schools" className="grid gap-3 md:grid-cols-3">
                            <input
                                name="search"
                                defaultValue={filters.search ?? ''}
                                placeholder="Search school name or ID"
                                className="h-10 rounded-md border border-slate-300 px-3 text-sm"
                            />
                            <select
                                name="district_id"
                                defaultValue={filters.district_id ?? ''}
                                className="h-10 rounded-md border border-slate-300 px-3 text-sm"
                            >
                                <option value="">All Districts</option>
                                {districts.map((district) => (
                                    <option key={district.id} value={district.id}>
                                        {district.name}
                                    </option>
                                ))}
                            </select>
                            <button type="submit" className="h-10 rounded-md bg-slate-900 px-4 text-sm text-white">
                                Apply Filters
                            </button>
                        </form>
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-slate-100 text-left text-slate-700">
                                    <tr>
                                        <th className="px-3 py-2">School ID</th>
                                        <th className="px-3 py-2">School Name</th>
                                        <th className="px-3 py-2">District</th>
                                        <th className="px-3 py-2">Municipality</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2">Resources</th>
                                        <th className="px-3 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {schools.data.map((school) => (
                                        <tr key={school.school_id} className="border-t border-slate-200">
                                            <td className="px-3 py-2">{school.school_id}</td>
                                            <td className="px-3 py-2">{school.school_name}</td>
                                            <td className="px-3 py-2">{school.district}</td>
                                            <td className="px-3 py-2">{school.municipality}</td>
                                            <td className="px-3 py-2">{school.is_activated ? 'Activated' : 'Pending'}</td>
                                            <td className="px-3 py-2">{school.learning_resources_count}</td>
                                            <td className="px-3 py-2">
                                                <div className="flex gap-2">
                                                    <Link
                                                        href={`/app/admin/schools/${school.school_id}/edit`}
                                                        className="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => deleteSchool(school.school_id)}
                                                        className="rounded border border-red-300 px-2 py-1 text-xs text-red-700"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2 text-sm">
                            {schools.links.map((link, index) => (
                                <span key={index}>
                                    {link.url ? (
                                        <Link
                                            href={link.url}
                                            className={`rounded px-3 py-1 ${link.active ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span
                                            className="rounded bg-slate-100 px-3 py-1 text-slate-400"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )}
                                </span>
                            ))}
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
