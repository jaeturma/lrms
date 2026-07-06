import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

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
    };
    districts: District[];
    filters: {
        search?: string;
        district_id?: number | null;
    };
    reportsByDistrict: Array<{ district: string; school_count: number }>;
    schools: Paginator<SchoolRow>;
    learningResourceTypes: Array<{ id: number; name: string; is_active: boolean }>;
};

export default function AdminDashboard({
    stats,
    districts,
    filters,
    reportsByDistrict,
    schools,
    learningResourceTypes,
}: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
    });

    const submitType = (event: FormEvent<HTMLFormElement>) => {
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

            <main className="min-h-screen bg-slate-50 p-4 md:p-8">
                <div className="mx-auto max-w-7xl space-y-6">
                    <header className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Admin Dashboard</h1>
                            <p className="text-sm text-slate-600">Monitor school activation and learning resource submissions.</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Link href="/app/admin/schools/create" className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700">
                                Add School
                            </Link>
                            <Link href="/app/admin/import/schools" className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white">
                                Import Schools CSV
                            </Link>
                            <Link href="/" className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700">
                                School Portal
                            </Link>
                        </div>
                    </header>

                    <section className="grid gap-4 md:grid-cols-4">
                        <StatCard label="Total Schools" value={stats.total_schools} />
                        <StatCard label="Activated Schools" value={stats.activated_schools} />
                        <StatCard label="Pending Schools" value={stats.pending_schools} />
                        <StatCard label="Total Resources" value={stats.total_learning_resources} />
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <form method="get" action="/app/admin/dashboard" className="grid gap-3 md:grid-cols-3">
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
                        <h2 className="mb-3 text-lg font-semibold text-slate-900">Reports by District</h2>
                        <div className="grid gap-2 text-sm text-slate-700 md:grid-cols-3">
                            {reportsByDistrict.map((item) => (
                                <div key={item.district} className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <p className="font-semibold">{item.district}</p>
                                    <p>{item.school_count} schools</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 className="mb-3 text-lg font-semibold text-slate-900">Learning Material Types</h2>
                        <form onSubmit={submitType} className="mb-4 flex flex-wrap gap-2">
                            <input
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                placeholder="Add type (e.g. Textbook)"
                                className="h-10 min-w-72 rounded-md border border-slate-300 px-3 text-sm"
                            />
                            <button
                                type="submit"
                                className="h-10 rounded-md bg-slate-900 px-4 text-sm text-white"
                                disabled={processing}
                            >
                                {processing ? 'Adding...' : 'Add Type'}
                            </button>
                        </form>
                        {errors.name && <p className="mb-3 text-sm text-red-600">{errors.name}</p>}

                        <div className="grid gap-2 md:grid-cols-2">
                            {learningResourceTypes.map((type) => (
                                <div key={type.id} className="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                                    <div>
                                        <p className="font-semibold text-slate-900">{type.name}</p>
                                        <p className="text-xs text-slate-600">{type.is_active ? 'Active' : 'Inactive'}</p>
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() => toggleType(type.id, type.name, type.is_active)}
                                            className="rounded-md border border-slate-300 px-3 py-1 text-xs text-slate-700"
                                        >
                                            {type.is_active ? 'Deactivate' : 'Activate'}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => deleteType(type.id)}
                                            className="rounded-md border border-red-300 px-3 py-1 text-xs text-red-700"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 className="mb-3 text-lg font-semibold text-slate-900">Schools</h2>
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
                                            <td className="px-3 py-2">
                                                {school.is_activated ? 'Activated' : 'Pending'}
                                            </td>
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

function StatCard({ label, value }: { label: string; value: number }) {
    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p className="text-xs uppercase tracking-wide text-slate-500">{label}</p>
            <p className="mt-2 text-3xl font-bold text-slate-900">{value}</p>
        </article>
    );
}
