import { Head } from '@inertiajs/react';

type School = {
    id?: number;
    school_id: string;
    school_name: string;
    district?: string | null;
    municipality?: string | null;
    barangay?: string | null;
    school_head?: string | null;
    librarian?: string | null;
    property_custodian?: string | null;
    primary_mobile_no?: string | null;
    secondary_mobile_no?: string | null;
    email?: string | null;
};

type Props = {
    school: School | { data: School };
    resourceSummary: {
        total_resources: number;
        total_delivered: number;
        total_with_issue: number;
        issue_rate: number;
        learning_types_count: number;
    };
};

export default function SchoolDashboard({ school, resourceSummary }: Props) {
    const schoolData = ('data' in school ? school.data : school) as School;
    const municipalityBarangay = `${schoolData.municipality ?? '-'} - ${schoolData.barangay ?? '-'}`;

    const schoolDetails = [
        { label: 'School Head', value: schoolData.school_head ?? '-' },
        { label: 'Email', value: schoolData.email ?? '-' },
        { label: 'Municipality/Barangay', value: municipalityBarangay },
        { label: 'District', value: schoolData.district ?? '-' },
        { label: 'Librarian', value: schoolData.librarian ?? '-' },
        { label: 'Property Custodian', value: schoolData.property_custodian ?? '-' },
        { label: 'Primary Mobile', value: schoolData.primary_mobile_no ?? '-' },
        { label: 'Secondary Mobile', value: schoolData.secondary_mobile_no ?? '-' },
    ];

    return (
        <>
            <Head title="School Dashboard" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-input bg-background p-5 shadow-sm">
                    <h1 className="text-2xl font-bold text-foreground">{schoolData.school_name} - {schoolData.school_id}</h1>
                    <div className="mt-4">
                        <table className="w-full text-sm">
                            <tbody>
                                {schoolDetails.map((item, index) => {
                                    if (index % 2 === 0) {
                                        const nextItem = schoolDetails[index + 1];

                                        return (
                                            <tr key={`row-${index}`} className="border-b border-border">
                                                <td className="w-1/4 py-2 pr-3 font-semibold text-foreground">
                                                    {item.label}
                                                </td>
                                                <td className="w-1/4 py-2 pr-6 text-foreground">{item.value}</td>
                                                {nextItem && (
                                                    <>
                                                        <td className="w-1/4 py-2 pr-3 font-semibold text-foreground">
                                                            {nextItem.label}
                                                        </td>
                                                        <td className="w-1/4 py-2 text-foreground">{nextItem.value}</td>
                                                    </>
                                                )}
                                            </tr>
                                        );
                                    }

                                    return null;
                                })}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="rounded-2xl border border-input bg-background p-5 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold text-foreground">Learning Resources Snapshot</h2>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        <SummaryCard
                            label="Resource Entries"
                            value={resourceSummary.total_resources.toString()}
                            color="blue"
                        />
                        <SummaryCard
                            label="Delivered Total"
                            value={resourceSummary.total_delivered.toString()}
                            color="emerald"
                        />
                        <SummaryCard label="With Issues" value={resourceSummary.total_with_issue.toString()} color="amber" />
                        <SummaryCard label="Issue Rate" value={`${resourceSummary.issue_rate}%`} color="rose" />
                        <SummaryCard
                            label="Active Resource Types"
                            value={resourceSummary.learning_types_count.toString()}
                            color="violet"
                        />
                    </div>
                    <p className="mt-4 text-sm text-muted-foreground">
                        To encode, edit, or remove learning resources, open the Learning Resources menu.
                    </p>
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

type CardColor = 'blue' | 'emerald' | 'amber' | 'rose' | 'violet';

function getCardColors(color: CardColor): { border: string; bg: string; label: string; value: string } {
    const colors: Record<CardColor, { border: string; bg: string; label: string; value: string }> = {
        blue: { border: 'border-blue-300', bg: 'bg-blue-50', label: 'text-blue-600', value: 'text-blue-900' },
        emerald: { border: 'border-emerald-300', bg: 'bg-emerald-50', label: 'text-emerald-600', value: 'text-emerald-900' },
        amber: { border: 'border-amber-300', bg: 'bg-amber-50', label: 'text-amber-600', value: 'text-amber-900' },
        rose: { border: 'border-rose-300', bg: 'bg-rose-50', label: 'text-rose-600', value: 'text-rose-900' },
        violet: { border: 'border-violet-300', bg: 'bg-violet-50', label: 'text-violet-600', value: 'text-violet-900' },
    };

    return colors[color];
}

function SummaryCard({
    label,
    value,
    color = 'blue',
}: {
    label: string;
    value: string;
    color?: CardColor;
}) {
    const colors = getCardColors(color);

    return (
        <article className={`rounded-xl border ${colors.border} ${colors.bg} p-4`}>
            <p className={`text-xs font-medium uppercase tracking-wide ${colors.label}`}>{label}</p>
            <p className={`mt-2 text-2xl font-semibold ${colors.value}`}>{value}</p>
        </article>
    );
}
