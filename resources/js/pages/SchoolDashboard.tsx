import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import LearningResourcesTable from '@/components/LearningResourcesTable';

type School = {
    school_id: string;
    school_name: string;
    district?: string | null;
    municipality?: string | null;
    barangay?: string | null;
    school_head?: string | null;
    librarian?: string | null;
    property_custodian?: string | null;
    email?: string | null;
};

type LearningResource = {
    id?: number;
    resource_type: string;
    issue_defect: string;
    quantity: number;
    publisher: string;
};

type Props = {
    school: School;
    learningResources: LearningResource[];
    learningResourceTypes: string[];
};

export default function SchoolDashboard({ school, learningResources, learningResourceTypes }: Props) {
    return (
        <>
            <Head title="School Dashboard" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h1 className="text-2xl font-bold text-slate-900">School Dashboard</h1>
                    <div className="mt-3">
                        <Link
                            href={`/school/activate/${school.school_id}`}
                            className="inline-flex rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700"
                        >
                            Update School Details
                        </Link>
                    </div>
                    <div className="mt-4 grid gap-2 text-sm text-slate-700 md:grid-cols-2">
                        <p>
                            <span className="font-semibold">School:</span> {school.school_name}
                        </p>
                        <p>
                            <span className="font-semibold">School ID:</span> {school.school_id}
                        </p>
                        <p>
                            <span className="font-semibold">District:</span> {school.district ?? '-'}
                        </p>
                        <p>
                            <span className="font-semibold">Municipality:</span> {school.municipality ?? '-'}
                        </p>
                        <p>
                            <span className="font-semibold">Barangay:</span> {school.barangay ?? '-'}
                        </p>
                        <p>
                            <span className="font-semibold">School Head:</span> {school.school_head ?? '-'}
                        </p>
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-slate-900">Learning Resources</h2>
                    <LearningResourcesTable initialRows={learningResources} learningResourceTypes={learningResourceTypes} />
                </section>
            </div>
        </>
    );
}

SchoolDashboard.layout = {
    breadcrumbs: [
        {
            title: 'School Dashboard',
            href: '/dashboard',
        },
    ],
};
