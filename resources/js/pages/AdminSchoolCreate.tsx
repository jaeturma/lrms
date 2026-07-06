import { Head, Link, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';

type District = {
    id: number;
    municipality_id: number;
    name: string;
};

type Municipality = {
    id: number;
    name: string;
};

type Barangay = {
    id: number;
    municipality_id: number;
    name: string;
};

type Props = {
    schoolTypes: string[];
    districts: District[];
    municipalities: Municipality[];
    barangays: Barangay[];
};

export default function AdminSchoolCreate({ schoolTypes, districts, municipalities, barangays }: Props) {
    const defaultMunicipalityId = municipalities[0]?.id ?? 0;
    const defaultDistrictId = districts.find(
        (district) => district.municipality_id === defaultMunicipalityId,
    )?.id ?? 0;

    const { data, setData, post, processing, errors } = useForm({
        school_id: '',
        school_name: '',
        school_type: schoolTypes[0] ?? '',
        municipality_id: defaultMunicipalityId,
        district_id: defaultDistrictId,
        barangay_id: '',
        school_head: '',
        librarian: '',
        property_custodian: '',
        primary_mobile_no: '',
        secondary_mobile_no: '',
        email: '',
    });

    const selectedMunicipalityId = Number(data.municipality_id) > 0 ? Number(data.municipality_id) : defaultMunicipalityId;

    const filteredDistricts = districts.filter(
        (district) => district.municipality_id === selectedMunicipalityId,
    );

    const selectedDistrictId =
        Number(data.district_id) > 0
            ? Number(data.district_id)
            : (filteredDistricts[0]?.id ?? 0);

    const filteredBarangays = barangays.filter(
        (barangay) => barangay.municipality_id === selectedMunicipalityId,
    );

    const submit = () => {
        post('/app/admin/schools', {
            onSuccess: () => {
                toast.success('School created successfully.');
            },
            onError: () => {
                toast.error('Unable to create school. Please review the form fields.');
            },
        });
    };

    return (
        <>
            <Head title="Add School" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-7xl rounded-2xl border border-border bg-card/95 p-6 shadow-md md:p-8">
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-xl font-bold text-foreground">Add School</h1>
                        <Link href="/app/admin/schools" className="text-sm text-foreground underline">
                            Cancel
                        </Link>
                    </div>

                    <div className="grid gap-4 lg:grid-cols-3">
                        <Field label="School Name" error={errors.school_name}>
                            <input
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.school_name}
                                onChange={(event) => setData('school_name', event.target.value)}
                            />
                        </Field>

                        <Field label="School ID" error={errors.school_id}>
                            <input
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.school_id}
                                onChange={(event) => setData('school_id', event.target.value)}
                            />
                        </Field>

                        <Field label="Type" error={errors.school_type}>
                            <select
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.school_type}
                                onChange={(event) => setData('school_type', event.target.value)}
                            >
                                {schoolTypes.map((schoolType) => (
                                    <option key={schoolType} value={schoolType}>
                                        {schoolType}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Municipality" error={errors.municipality_id}>
                            <select
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={selectedMunicipalityId}
                                onChange={(event) => {
                                    const nextMunicipalityId = Number(event.target.value);
                                    const nextDistrict = districts.find((district) => district.municipality_id === nextMunicipalityId);

                                    setData((currentData) => ({
                                        ...currentData,
                                        municipality_id: nextMunicipalityId,
                                        district_id: nextDistrict?.id ?? 0,
                                        barangay_id: '',
                                    }));
                                }}
                            >
                                {municipalities.map((municipality) => (
                                    <option key={municipality.id} value={municipality.id}>
                                        {municipality.name}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="District" error={errors.district_id}>
                            <select
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={selectedDistrictId}
                                onChange={(event) => {
                                    const nextDistrictId = Number(event.target.value);
                                    const nextDistrict = districts.find((district) => district.id === nextDistrictId);
                                    setData((currentData) => ({
                                        ...currentData,
                                        district_id: nextDistrictId,
                                        municipality_id: nextDistrict?.municipality_id ?? 0,
                                        barangay_id: '',
                                    }));
                                }}
                            >
                                {filteredDistricts.map((district) => (
                                    <option key={district.id} value={district.id}>
                                        {district.name}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Barangay" error={errors.barangay_id}>
                            <select
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.barangay_id}
                                onChange={(event) => setData('barangay_id', event.target.value)}
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
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.school_head}
                                onChange={(event) => setData('school_head', event.target.value)}
                            />
                        </Field>

                        <Field label="Property Custodian" error={errors.property_custodian}>
                            <input
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.property_custodian}
                                onChange={(event) => setData('property_custodian', event.target.value)}
                            />
                        </Field>

                        <Field label="Librarian" error={errors.librarian}>
                            <input
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.librarian}
                                onChange={(event) => setData('librarian', event.target.value)}
                            />
                        </Field>

                        <Field label="Primary Mobile No." error={errors.primary_mobile_no}>
                            <input
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.primary_mobile_no}
                                onChange={(event) => setData('primary_mobile_no', event.target.value)}
                            />
                        </Field>

                        <Field label="Secondary No." error={errors.secondary_mobile_no}>
                            <input
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.secondary_mobile_no}
                                onChange={(event) => setData('secondary_mobile_no', event.target.value)}
                            />
                        </Field>

                        <Field label="Email" error={errors.email}>
                            <input
                                type="email"
                                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                            />
                        </Field>
                    </div>

                    <div className="mt-6">
                        <button
                            type="button"
                            onClick={submit}
                            className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
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
            <label className="mb-1 block text-sm font-medium text-foreground">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}
