import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type School = {
    school_id: string;
    school_name: string;
    is_activated: boolean;
    district?: string | null;
    municipality?: string | null;
    barangay?: string | null;
    school_head?: string | null;
    librarian?: string | null;
    property_custodian?: string | null;
    email?: string | null;
};

type Props = {
    school: School;
    showCredentials: boolean;
    generatedPassword?: string;
    generatedEmail?: string;
};

export default function SchoolActivationPage({
    school,
    showCredentials,
    generatedEmail,
    generatedPassword,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        school_head: school.school_head ?? '',
        librarian: school.librarian ?? '',
        property_custodian: school.property_custodian ?? '',
        email: school.email ?? '',
    });

    const submit = () => {
        post(`/school/activate/${school.school_id}`);
    };

    return (
        <>
            <Head title="School Activation" />

            <main className="min-h-screen bg-slate-50 px-4 py-8 md:px-8 md:py-12">
                <div className="mx-auto max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                    <h1 className="text-2xl font-bold text-slate-900">School Activation</h1>
                    <p className="mt-1 text-sm text-slate-600">
                        {school.is_activated
                            ? 'Update your school details before proceeding to learning resources.'
                            : 'Confirm your information to activate your account.'}
                    </p>

                    <div className="mt-6 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:grid-cols-2">
                        <p>
                            <span className="font-semibold">School ID:</span> {school.school_id}
                        </p>
                        <p>
                            <span className="font-semibold">School:</span> {school.school_name}
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
                    </div>

                    {showCredentials ? (
                        <div className="mt-6 rounded-xl border border-emerald-300 bg-emerald-50 p-5">
                            <h2 className="text-lg font-semibold text-emerald-900">Account Activated</h2>
                            <p className="mt-2 text-sm text-emerald-800">
                                Save these credentials now. The generated password is shown only once.
                            </p>
                            <div className="mt-4 space-y-2 text-sm text-emerald-900">
                                <p>
                                    <span className="font-semibold">Email:</span> {generatedEmail}
                                </p>
                                <p>
                                    <span className="font-semibold">Password:</span> {generatedPassword}
                                </p>
                            </div>
                            <Link href="/login" className="mt-5 inline-block text-sm font-semibold underline">
                                Proceed to School Login
                            </Link>
                        </div>
                    ) : (
                        <div className="mt-6 grid gap-4">
                            <div>
                                <label htmlFor="school_head" className="mb-1 block text-sm font-medium text-slate-700">
                                    School Head
                                </label>
                                <Input
                                    id="school_head"
                                    value={data.school_head}
                                    onChange={(event) => setData('school_head', event.target.value)}
                                    required
                                />
                                <InputError message={errors.school_head} />
                            </div>

                            <div>
                                <label htmlFor="librarian" className="mb-1 block text-sm font-medium text-slate-700">
                                    Librarian
                                </label>
                                <Input
                                    id="librarian"
                                    value={data.librarian}
                                    onChange={(event) => setData('librarian', event.target.value)}
                                />
                                <InputError message={errors.librarian} />
                            </div>

                            <div>
                                <label
                                    htmlFor="property_custodian"
                                    className="mb-1 block text-sm font-medium text-slate-700"
                                >
                                    Property Custodian
                                </label>
                                <Input
                                    id="property_custodian"
                                    value={data.property_custodian}
                                    onChange={(event) => setData('property_custodian', event.target.value)}
                                />
                                <InputError message={errors.property_custodian} />
                            </div>

                            <div>
                                <label htmlFor="email" className="mb-1 block text-sm font-medium text-slate-700">
                                    Email Address
                                </label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <Button type="button" onClick={submit} disabled={processing}>
                                {processing ? 'Saving...' : school.is_activated ? 'Update Details' : 'Activate Account'}
                            </Button>
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}
