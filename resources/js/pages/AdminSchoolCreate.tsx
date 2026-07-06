import { Head, Link, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';

type District = {
    id: number;
    name: string;
};

type Municipality = {
    id: number;
    district_id: number;
    name: string;
};

type Barangay = {
    id: number;
    municipality_id: number;
    name: string;
};

type Props = {
    districts: District[];
    municipalities: Municipality[];
    barangays: Barangay[];
};

export default function AdminSchoolCreate({ districts, municipalities, barangays }: Props) {
    const defaultDistrictId = districts[0]?.id ?? 0;
    const defaultMunicipalityId = municipalities.find(
        (municipality) => municipality.district_id === defaultDistrictId,
    )?.id ?? 0;

    const { data, setData, post, processing, errors } = useForm({
        school_id: '',
        school_name: '',
        district_id: defaultDistrictId,
        municipality_id: defaultMunicipalityId,
        barangay_id: '',
        school_head: '',
        librarian: '',
        property_custodian: '',
        email: '',
    });

    const filteredMunicipalities = municipalities.filter(
        (municipality) => municipality.district_id === Number(data.district_id),
    );

    const selectedMunicipalityId =
        Number(data.municipality_id) > 0
            ? Number(data.municipality_id)
            : (filteredMunicipalities[0]?.id ?? 0);

    const filteredBarangays = barangays.filter(
        (barangay) => barangay.municipality_id === selectedMunicipalityId,
    );

    const submit = () => {
        post('/app/admin/schools');
    };

    return (
        <>
            <Head title="Add School" />

            <main className="min-h-screen bg-slate-50 p-4 md:p-8">
                <div className="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-6">
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-xl font-bold text-slate-900">Add School</h1>
                        <Link href="/app/admin/dashboard" className="text-sm text-slate-700 underline">
                            Back to Dashboard
                        </Link>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="School ID" error={errors.school_id}>
                            <input
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.school_id}
                                onChange={(event) => setData('school_id', event.target.value)}
                            />
                        </Field>

                        <Field label="School Name" error={errors.school_name}>
                            <input
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.school_name}
                                onChange={(event) => setData('school_name', event.target.value)}
                            />
                        </Field>

                        <Field label="District" error={errors.district_id}>
                            <select
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.district_id}
                                onChange={(event) => {
                                    const nextDistrictId = Number(event.target.value);
                                    const nextMunicipality = municipalities.find(
                                        (municipality) => municipality.district_id === nextDistrictId,
                                    );
                                    setData((currentData) => ({
                                        ...currentData,
                                        district_id: nextDistrictId,
                                        municipality_id: nextMunicipality?.id ?? 0,
                                        barangay_id: '',
                                    }));
                                }}
                            >
                                {districts.map((district) => (
                                    <option key={district.id} value={district.id}>
                                        {district.name}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Municipality" error={errors.municipality_id}>
                            <select
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={selectedMunicipalityId}
                                onChange={(event) => setData('municipality_id', Number(event.target.value))}
                            >
                                {filteredMunicipalities.map((municipality) => (
                                    <option key={municipality.id} value={municipality.id}>
                                        {municipality.name}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Barangay" error={errors.barangay_id}>
                            <select
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.barangay_id}
                                onChange={(event) => setData('barangay_id', event.target.value === '' ? '' : Number(event.target.value))}
                            >
                                <option value="">None</option>
                                {filteredBarangays.map((barangay) => (
                                    <option key={barangay.id} value={barangay.id}>
                                        {barangay.name}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="School Head" error={errors.school_head}>
                            <input
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.school_head}
                                onChange={(event) => setData('school_head', event.target.value)}
                            />
                        </Field>

                        <Field label="Librarian" error={errors.librarian}>
                            <input
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.librarian}
                                onChange={(event) => setData('librarian', event.target.value)}
                            />
                        </Field>

                        <Field label="Property Custodian" error={errors.property_custodian}>
                            <input
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.property_custodian}
                                onChange={(event) => setData('property_custodian', event.target.value)}
                            />
                        </Field>

                        <Field label="Email" error={errors.email}>
                            <input
                                type="email"
                                className="h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                            />
                        </Field>
                    </div>

                    <div className="mt-6">
                        <button
                            type="button"
                            onClick={submit}
                            className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
                            disabled={processing}
                        >
                            {processing ? 'Saving...' : 'Create School'}
                        </button>
                    </div>
                </div>
            </main>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}
