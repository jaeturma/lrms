import { Head, Link, router } from '@inertiajs/react';

type School = {
    school_id: string;
    school_name: string;
    school_type?: string | null;
    district?: string | null;
    municipality?: string | null;
    barangay?: string | null;
    school_head?: string | null;
    property_custodian?: string | null;
    librarian?: string | null;
    primary_mobile_no?: string | null;
    secondary_mobile_no?: string | null;
    email?: string | null;
    is_activated: boolean;
    activation_requested_at?: string | null;
};

type LearningResource = {
    id: number;
    resource_type: string;
    title?: string | null;
    publisher: string;
    quantity_delivered?: number | null;
    quantity_with_issue_defect?: number | null;
    remarks?: string | null;
};

type EnrollmentRow = {
    id: number;
    grade_level: string | null;
    male_count: number;
    female_count: number;
    total: number;
};

type Props = {
    school: School;
    learningResources: LearningResource[];
    activeSchoolYear: { id: number; name: string } | null;
    enrollments: EnrollmentRow[];
    generatedEmail?: string;
    generatedPassword?: string;
};

export default function AdminSchoolShow({
    school,
    learningResources,
    activeSchoolYear,
    enrollments,
    generatedEmail,
    generatedPassword,
}: Props) {
    const totalLearners = enrollments.reduce((total, enrollment) => total + enrollment.total, 0);
    const municipalityBarangay = `${school.municipality ?? '-'} - ${school.barangay ?? '-'}`;

    const manuallyActivate = () => {
        router.post(`/app/admin/schools/${school.school_id}/manual-activate`);
    };

    return (
        <>
            <Head title={`${school.school_name} - ${school.school_id}`} />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-6xl space-y-6">
                    <header className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">{school.school_name} - {school.school_id}</h1>
                        </div>
                        <div className="flex items-center gap-2">
                            <Link
                                href="/app/admin/schools"
                                className="rounded-md border border-input bg-background px-4 py-2 text-sm text-foreground"
                            >
                                Back to Index
                            </Link>
                            {!school.is_activated && school.activation_requested_at && (
                                <button
                                    type="button"
                                    onClick={manuallyActivate}
                                    className="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700"
                                >
                                    Manually Activate
                                </button>
                            )}
                            <Link
                                href={`/app/admin/schools/${school.school_id}/edit`}
                                className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                            >
                                Edit School
                            </Link>
                        </div>
                    </header>

                    {generatedEmail && generatedPassword && (
                        <section className="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 shadow-sm">
                            <h2 className="text-lg font-semibold text-emerald-900">Generated Credentials</h2>
                            <p className="mt-1 text-sm text-emerald-800">
                                Share these credentials with the school user after manual activation.
                            </p>
                            <div className="mt-3 text-sm text-emerald-900">
                                <p><span className="font-semibold">Email:</span> {generatedEmail}</p>
                                <p><span className="font-semibold">Password:</span> {generatedPassword}</p>
                            </div>
                        </section>
                    )}

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">School Details</h2>
                        <div className="grid gap-3 text-sm text-foreground md:grid-cols-3">
                            <Detail label="School Name" value={school.school_name} />
                            <Detail label="School ID" value={school.school_id} />
                            <Detail label="Type" value={school.school_type} />
                            <Detail label="Municipality/Barangay" value={municipalityBarangay} />
                            <Detail label="District" value={school.district} />
                            <Detail label="School Head" value={school.school_head} />
                            <Detail label="Property Custodian" value={school.property_custodian} />
                            <Detail label="Librarian" value={school.librarian} />
                            <Detail label="Primary Mobile No." value={school.primary_mobile_no} />
                            <Detail label="Secondary No." value={school.secondary_mobile_no} />
                            <Detail label="Email" value={school.email} />
                            <Detail
                                label="Activation Request"
                                value={school.activation_requested_at ? 'Requested' : 'No request yet'}
                            />
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <h2 className="text-lg font-semibold text-foreground">
                                Enrollment{activeSchoolYear ? ` · SY ${activeSchoolYear.name}` : ''}
                            </h2>
                            {enrollments.length > 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Total learners: <span className="font-semibold text-foreground">{totalLearners.toLocaleString()}</span>
                                </p>
                            )}
                        </div>
                        {!activeSchoolYear && (
                            <p className="text-sm text-muted-foreground">No active school year is set.</p>
                        )}
                        {activeSchoolYear && enrollments.length === 0 && (
                            <p className="text-sm text-muted-foreground">No enrollment encoded for the active school year yet.</p>
                        )}
                        {enrollments.length > 0 && (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="min-w-full border-collapse text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="border-b border-border px-3 py-2">Grade Level</th>
                                            <th className="border-b border-border px-3 py-2">Male</th>
                                            <th className="border-b border-border px-3 py-2">Female</th>
                                            <th className="border-b border-border px-3 py-2">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {enrollments.map((enrollment) => (
                                            <tr key={enrollment.id} className="border-t border-border">
                                                <td className="px-3 py-2">{enrollment.grade_level ?? '-'}</td>
                                                <td className="px-3 py-2">{enrollment.male_count.toLocaleString()}</td>
                                                <td className="px-3 py-2">{enrollment.female_count.toLocaleString()}</td>
                                                <td className="px-3 py-2 font-medium">{enrollment.total.toLocaleString()}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">Learning Resources</h2>
                        <div className="overflow-x-auto rounded-lg border border-border">
                            <table className="min-w-full border-collapse text-sm">
                                <thead className="bg-muted text-left text-foreground">
                                    <tr>
                                        <th className="border-b border-border px-3 py-2">Type</th>
                                        <th className="border-b border-border px-3 py-2">Title</th>
                                        <th className="border-b border-border px-3 py-2">Publisher</th>
                                        <th className="border-b border-border px-3 py-2">Quantity Delivered</th>
                                        <th className="border-b border-border px-3 py-2">Quantity with Issue/Defect</th>
                                        <th className="border-b border-border px-3 py-2">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {learningResources.length === 0 && (
                                        <tr>
                                            <td className="px-3 py-6 text-center text-muted-foreground" colSpan={6}>
                                                No learning resources submitted yet.
                                            </td>
                                        </tr>
                                    )}
                                    {learningResources.map((resource) => (
                                        <tr key={resource.id} className="border-t border-border">
                                            <td className="px-3 py-2">{resource.resource_type}</td>
                                            <td className="px-3 py-2">{resource.title ?? '-'}</td>
                                            <td className="px-3 py-2">{resource.publisher}</td>
                                            <td className="px-3 py-2">{resource.quantity_delivered ?? '-'}</td>
                                            <td className="px-3 py-2">{resource.quantity_with_issue_defect ?? '-'}</td>
                                            <td className="px-3 py-2">{resource.remarks ?? '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}

function Detail({ label, value }: { label: string; value?: string | null }) {
    return (
        <p>
            <span className="font-semibold text-foreground">{label}:</span> {value && value.trim() !== '' ? value : '-'}
        </p>
    );
}
