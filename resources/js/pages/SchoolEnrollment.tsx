import { Head, useForm } from '@inertiajs/react';
import { Loader2, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type GradeLevel = {
    id: number;
    name: string;
};

type EnrollmentCounts = {
    male_count: number;
    female_count: number;
};

type EnrollmentEntry = {
    grade_level_id: number;
    male_count: number;
    female_count: number;
};

type Props = {
    activeSchoolYear: { id: number; name: string } | null;
    gradeLevels: GradeLevel[];
    enrollments: Record<number, EnrollmentCounts>;
};

export default function SchoolEnrollment({ activeSchoolYear, gradeLevels, enrollments }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm<{ enrollments: EnrollmentEntry[] }>({
        enrollments: gradeLevels.map((gradeLevel) => ({
            grade_level_id: gradeLevel.id,
            male_count: enrollments[gradeLevel.id]?.male_count ?? 0,
            female_count: enrollments[gradeLevel.id]?.female_count ?? 0,
        })),
    });

    const updateCount = (index: number, field: 'male_count' | 'female_count', value: string) => {
        const count = Math.max(0, Number(value) || 0);

        setData(
            'enrollments',
            data.enrollments.map((entry, entryIndex) =>
                entryIndex === index ? { ...entry, [field]: count } : entry,
            ),
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        put('/school/enrollment', { preserveScroll: true });
    };

    const totalLearners = data.enrollments.reduce(
        (total, entry) => total + entry.male_count + entry.female_count,
        0,
    );

    const generalError = Object.values(errors)[0];

    return (
        <>
            <Head title="Enrollment" />

            <div className="space-y-6 p-4 md:p-6">
                <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 className="text-lg font-semibold text-foreground">Enrollment</h2>
                            <p className="text-sm text-muted-foreground">
                                {activeSchoolYear
                                    ? `Encode learner counts per grade level for SY ${activeSchoolYear.name}. Leave grades your school does not offer at zero.`
                                    : 'No active school year is set. Please coordinate with the Division admin.'}
                            </p>
                        </div>
                        <p className="text-sm font-medium text-foreground">
                            Total learners: <span className="font-bold">{totalLearners.toLocaleString()}</span>
                        </p>
                    </div>

                    {activeSchoolYear && gradeLevels.length > 0 && (
                        <form onSubmit={submit}>
                            <div className="overflow-x-auto rounded-xl border border-border">
                                <table className="min-w-full text-sm">
                                    <thead className="bg-muted text-left text-foreground">
                                        <tr>
                                            <th className="px-3 py-2">Grade Level</th>
                                            <th className="px-3 py-2">Male</th>
                                            <th className="px-3 py-2">Female</th>
                                            <th className="px-3 py-2">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {gradeLevels.map((gradeLevel, index) => {
                                            const entry = data.enrollments[index];

                                            return (
                                                <tr key={gradeLevel.id} className="border-t border-border">
                                                    <td className="px-3 py-2 font-medium text-foreground">{gradeLevel.name}</td>
                                                    <td className="px-3 py-2">
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            value={entry.male_count}
                                                            onChange={(event) => updateCount(index, 'male_count', event.target.value)}
                                                            className="w-28"
                                                            aria-label={`${gradeLevel.name} male learners`}
                                                        />
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            value={entry.female_count}
                                                            onChange={(event) => updateCount(index, 'female_count', event.target.value)}
                                                            className="w-28"
                                                            aria-label={`${gradeLevel.name} female learners`}
                                                        />
                                                    </td>
                                                    <td className="px-3 py-2 text-foreground">
                                                        {(entry.male_count + entry.female_count).toLocaleString()}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            {generalError && <p className="mt-3 text-sm text-red-600">{generalError}</p>}
                            {recentlySuccessful && <p className="mt-3 text-sm text-emerald-600">Enrollment saved successfully.</p>}

                            <div className="mt-4 flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <>
                                            <Loader2 className="mr-1 h-4 w-4 animate-spin" />
                                            Saving...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="mr-1 h-4 w-4" />
                                            Save Enrollment
                                        </>
                                    )}
                                </Button>
                            </div>
                        </form>
                    )}

                    {activeSchoolYear && gradeLevels.length === 0 && (
                        <p className="text-sm text-amber-700 dark:text-amber-400">
                            No active grade levels found. Please ask your admin to add grade levels.
                        </p>
                    )}
                </section>
            </div>
        </>
    );
}

SchoolEnrollment.layout = {
    breadcrumbs: [
        {
            title: 'Enrollment',
            href: '/school/enrollment',
        },
    ],
};
