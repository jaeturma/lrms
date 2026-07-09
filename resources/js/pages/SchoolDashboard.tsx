import { Head } from '@inertiajs/react';
import { BookOpen, Boxes, CalendarRange, GraduationCap, Percent, Truck } from 'lucide-react';
import { ChartCard } from '@/components/charts/chart-card';
import { DivergingStackedBar } from '@/components/charts/diverging-stacked-bar';
import { EmptyChart } from '@/components/charts/empty-chart';
import { GroupedColumnChart } from '@/components/charts/grouped-column-chart';
import { StatTile } from '@/components/stat-tile';

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
    activeSchoolYear: { id: number; name: string } | null;
    stats: {
        total_learners: number;
        male_learners: number;
        female_learners: number;
        total_resources: number;
        copies_delivered: number;
        copies_with_defects: number;
        defect_rate: number;
        total_equipment: number;
        equipment_needing_repair: number;
        pending_distributions: number;
        total_distributions: number;
    };
    enrollmentByGrade: Array<{ grade: string; male: number; female: number }>;
    equipmentCondition: Array<{ type: string; good: number; fair: number; needs_attention: number }>;
};

export default function SchoolDashboard({ school, activeSchoolYear, stats, enrollmentByGrade, equipmentCondition }: Props) {
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

            <div className="space-y-4 bg-background/40 p-3 md:p-4">
                <section className="rounded-2xl border border-input bg-background p-4 shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h1 className="text-xl font-bold text-foreground">{schoolData.school_name} - {schoolData.school_id}</h1>
                        {activeSchoolYear && (
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-3 py-1 text-xs font-medium text-foreground">
                                <CalendarRange className="size-3.5 text-muted-foreground" />
                                SY {activeSchoolYear.name}
                            </span>
                        )}
                    </div>
                    <div className="mt-3">
                        <table className="w-full text-sm">
                            <tbody>
                                {schoolDetails.map((item, index) => {
                                    if (index % 2 === 0) {
                                        const nextItem = schoolDetails[index + 1];

                                        return (
                                            <tr key={`row-${index}`} className="border-b border-border">
                                                <td className="w-1/4 py-1.5 pr-3 font-semibold text-foreground">
                                                    {item.label}
                                                </td>
                                                <td className="w-1/4 py-1.5 pr-6 text-foreground">{item.value}</td>
                                                {nextItem && (
                                                    <>
                                                        <td className="w-1/4 py-1.5 pr-3 font-semibold text-foreground">
                                                            {nextItem.label}
                                                        </td>
                                                        <td className="w-1/4 py-1.5 text-foreground">{nextItem.value}</td>
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

                <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <StatTile
                        label="Learners"
                        value={stats.total_learners}
                        context={
                            activeSchoolYear
                                ? `${stats.male_learners.toLocaleString()} male · ${stats.female_learners.toLocaleString()} female`
                                : 'No active school year'
                        }
                        icon={GraduationCap}
                        colorClassName="bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-200"
                    />
                    <StatTile
                        label="Printed copies"
                        value={stats.copies_delivered}
                        context={`across ${stats.total_resources.toLocaleString()} resource entries`}
                        icon={BookOpen}
                        colorClassName="bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-200"
                    />
                    <StatTile
                        label="Defect rate"
                        value={stats.copies_with_defects}
                        context={`${stats.defect_rate}% of delivered copies`}
                        icon={Percent}
                        colorClassName="bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-200"
                    />
                    <StatTile
                        label="Equipment units"
                        value={stats.total_equipment}
                        context={`${stats.equipment_needing_repair.toLocaleString()} need repair`}
                        icon={Boxes}
                        colorClassName="bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-200"
                    />
                    <StatTile
                        label="Pending deliveries"
                        value={stats.pending_distributions}
                        context={`of ${stats.total_distributions.toLocaleString()} distributions`}
                        icon={Truck}
                        colorClassName="bg-lime-100 text-lime-700 dark:bg-lime-950/60 dark:text-lime-200"
                    />
                </section>

                <section className="grid gap-3 lg:grid-cols-2">
                    <ChartCard
                        title="Enrollment by Grade Level"
                        subtitle={activeSchoolYear ? `Learners encoded for SY ${activeSchoolYear.name}` : 'No active school year set'}
                        legend={[
                            { label: 'Male', color: 'var(--viz-series-1)' },
                            { label: 'Female', color: 'var(--viz-series-2)' },
                        ]}
                        table={{
                            headers: ['Grade level', 'Male', 'Female', 'Total'],
                            rows: enrollmentByGrade.map((row) => [row.grade, row.male, row.female, row.male + row.female]),
                        }}
                    >
                        {enrollmentByGrade.length > 0 ? (
                            <GroupedColumnChart
                                data={enrollmentByGrade.map((row) => ({ label: row.grade, values: [row.male, row.female] }))}
                                series={[
                                    { name: 'Male', color: 'var(--viz-series-1)' },
                                    { name: 'Female', color: 'var(--viz-series-2)' },
                                ]}
                            />
                        ) : (
                            <EmptyChart message="No enrollment encoded for the active school year yet. Open the Enrollment menu to encode." />
                        )}
                    </ChartCard>

                    <ChartCard
                        title="Equipment Condition"
                        subtitle="Serviceable units to the right, units needing repair to the left"
                        legend={[
                            { label: 'Needs attention', color: 'var(--viz-bad)' },
                            { label: 'Fair', color: 'var(--viz-neutral)' },
                            { label: 'Good', color: 'var(--viz-series-1)' },
                        ]}
                        table={{
                            headers: ['Equipment', 'Good', 'Fair', 'Needs attention', 'Total'],
                            rows: equipmentCondition.map((row) => [
                                row.type,
                                row.good,
                                row.fair,
                                row.needs_attention,
                                row.good + row.fair + row.needs_attention,
                            ]),
                        }}
                    >
                        {equipmentCondition.some((row) => row.good + row.fair + row.needs_attention > 0) ? (
                            <DivergingStackedBar
                                rows={equipmentCondition.map((row) => ({
                                    label: row.type,
                                    negative: row.needs_attention,
                                    neutral: row.fair,
                                    positive: row.good,
                                }))}
                            />
                        ) : (
                            <EmptyChart message="No equipment registered yet. Use the ICT Equipments, Science and Math, or Other Materials menus to register." />
                        )}
                    </ChartCard>
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
