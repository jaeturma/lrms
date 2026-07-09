import { Head, Link, router } from '@inertiajs/react';
import { School as SchoolIcon } from 'lucide-react';
import { toast } from 'sonner';
import { EmptyState, EmptyTableRow } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';

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
    inventory?: {
        available: number;
        issued: number;
        borrowed: number;
        damaged: number;
        lost: number;
        condemned: number;
    };
};

type InventoryMovementRow = {
    id: number;
    resource_title: string | null;
    type: string;
    quantity: number;
    from_status: string | null;
    to_status: string | null;
    notes: string | null;
    recorded_by: string | null;
    created_at: string | null;
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
    inventoryMovements: InventoryMovementRow[];
    activeSchoolYear: { id: number; name: string } | null;
    enrollments: EnrollmentRow[];
    generatedEmail?: string;
    generatedPassword?: string;
};

export default function AdminSchoolShow({
    school,
    learningResources,
    inventoryMovements,
    activeSchoolYear,
    enrollments,
    generatedEmail,
    generatedPassword,
}: Props) {
    const totalLearners = enrollments.reduce((total, enrollment) => total + enrollment.total, 0);
    const municipalityBarangay = `${school.municipality ?? '-'} - ${school.barangay ?? '-'}`;

    const manuallyActivate = () => {
        router.post(`/app/admin/schools/${school.school_id}/manual-activate`, {}, {
            onSuccess: () => toast.success('School manually activated.'),
        });
    };

    const sendCredentials = () => {
        router.post(
            `/app/admin/schools/${school.school_id}/send-credentials`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Credentials email sent.'),
            },
        );
    };

    return (
        <>
            <Head title={`${school.school_name} - ${school.school_id}`} />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-6xl space-y-6">
                    <PageHeader
                        icon={SchoolIcon}
                        iconClassName="bg-blue-950 text-blue-400 dark:bg-blue-900/60 dark:text-blue-300"
                        title={`${school.school_name} - ${school.school_id}`}
                        actions={
                            <>
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
                            </>
                        }
                    />

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
                            <button
                                type="button"
                                onClick={sendCredentials}
                                className="mt-4 rounded-md border border-emerald-400 bg-white px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100"
                            >
                                Send Credentials via Email
                            </button>
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
                        {!activeSchoolYear && <EmptyState message="No active school year is set." />}
                        {activeSchoolYear && enrollments.length === 0 && (
                            <EmptyState message="No enrollment encoded for the active school year yet." />
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
                                        <th className="border-b border-border px-3 py-2">Inventory Status</th>
                                        <th className="border-b border-border px-3 py-2">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {learningResources.length === 0 && (
                                        <EmptyTableRow colSpan={6} message="No learning resources submitted yet." />
                                    )}
                                    {learningResources.map((resource) => (
                                        <tr key={resource.id} className="border-t border-border">
                                            <td className="px-3 py-2">{resource.resource_type}</td>
                                            <td className="px-3 py-2">{resource.title ?? '-'}</td>
                                            <td className="px-3 py-2">{resource.publisher}</td>
                                            <td className="px-3 py-2">{resource.quantity_delivered ?? '-'}</td>
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {resource.inventory
                                                    ? [
                                                          `${resource.inventory.available} available`,
                                                          resource.inventory.issued > 0 && `${resource.inventory.issued} issued`,
                                                          resource.inventory.borrowed > 0 && `${resource.inventory.borrowed} borrowed`,
                                                          resource.inventory.damaged > 0 && `${resource.inventory.damaged} damaged`,
                                                          resource.inventory.lost > 0 && `${resource.inventory.lost} lost`,
                                                          resource.inventory.condemned > 0 && `${resource.inventory.condemned} condemned`,
                                                      ]
                                                          .filter(Boolean)
                                                          .join(' · ')
                                                    : '-'}
                                            </td>
                                            <td className="px-3 py-2">{resource.remarks ?? '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">Recent Inventory Movements</h2>
                        {inventoryMovements.length === 0 && (
                            <EmptyState message="No inventory movements recorded yet." />
                        )}
                        {inventoryMovements.length > 0 && (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="min-w-full border-collapse text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="border-b border-border px-3 py-2">Date</th>
                                            <th className="border-b border-border px-3 py-2">Resource</th>
                                            <th className="border-b border-border px-3 py-2">Movement</th>
                                            <th className="border-b border-border px-3 py-2 text-right">Qty</th>
                                            <th className="border-b border-border px-3 py-2">From → To</th>
                                            <th className="border-b border-border px-3 py-2">Notes</th>
                                            <th className="border-b border-border px-3 py-2">Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {inventoryMovements.map((movement) => (
                                            <tr key={movement.id} className="border-t border-border">
                                                <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                                                    {movement.created_at ? new Date(movement.created_at).toLocaleString() : '-'}
                                                </td>
                                                <td className="px-3 py-2">{movement.resource_title ?? '-'}</td>
                                                <td className="px-3 py-2 capitalize">{movement.type}</td>
                                                <td className="px-3 py-2 text-right">{movement.quantity}</td>
                                                <td className="px-3 py-2 text-muted-foreground">
                                                    {movement.from_status || movement.to_status
                                                        ? `${movement.from_status ?? '—'} → ${movement.to_status ?? '—'}`
                                                        : '—'}
                                                </td>
                                                <td className="px-3 py-2 text-muted-foreground">{movement.notes ?? '-'}</td>
                                                <td className="px-3 py-2 text-muted-foreground">{movement.recorded_by ?? '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
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
