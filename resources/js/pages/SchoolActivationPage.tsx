import { Head, Link, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import React, { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type School = {
    school_id: string;
    school_name: string;
    is_activated: boolean;
    district_id?: number | null;
    municipality_id?: number | null;
    barangay_id?: number | null;
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

type Municipality = {
    id: number;
    name: string;
};

type District = {
    id: number;
    municipality_id: number;
    name: string;
};

type Barangay = {
    id: number;
    municipality_id: number;
    name: string;
};

type Props = {
    school: School | { data: School };
    showCredentials: boolean;
    otpEnabled?: boolean;
    otpPending?: boolean;
    otpExpiresAt?: string;
    generatedPassword?: string;
    generatedEmail?: string;
    municipalities: Municipality[];
    districts: District[];
    barangays: Barangay[];
    schoolTypes: string[];
};

export default function SchoolActivationPage({
    school,
    showCredentials,
    otpEnabled = false,
    otpPending = false,
    otpExpiresAt,
    generatedEmail,
    generatedPassword,
    municipalities,
    districts,
    barangays,
}: Props) {
    const schoolData = ('data' in school ? school.data : school) as School;

    const { data, setData, post, processing, errors } = useForm({
        school_head: schoolData.school_head ?? '',
        librarian: schoolData.librarian ?? '',
        property_custodian: schoolData.property_custodian ?? '',
        primary_mobile_no: schoolData.primary_mobile_no ?? '',
        secondary_mobile_no: schoolData.secondary_mobile_no ?? '',
        email: schoolData.email ?? '',
        municipality_id: schoolData.municipality_id ?? '',
        district_id: schoolData.district_id ?? '',
        barangay_id: schoolData.barangay_id ?? '',
        otp: '',
    });

    const [agreeToTerms, setAgreeToTerms] = useState(false);
    const [showManualReviewModal, setShowManualReviewModal] = useState(false);

    const filteredDistricts = districts.filter(
        (district) => district.municipality_id === Number(data.municipality_id),
    );

    const filteredBarangays = barangays.filter(
        (barangay) => barangay.municipality_id === Number(data.municipality_id),
    );

    const activationPath = window.location.pathname.replace(/\/$/, '');
    const inferredSchoolId = activationPath.split('/').filter(Boolean).at(-1) ?? '';
    const schoolIdDisplay = schoolData.school_id || inferredSchoolId;
    const schoolNameDisplay = schoolData.school_name || 'School name not found';

    const sendOtp = () => {
        post(activationPath, {
            onSuccess: () => {
                if (!schoolData.is_activated && !otpEnabled) {
                    setShowManualReviewModal(true);
                }
            },
        });
    };

    const closeManualReviewModal = (open: boolean) => {
        setShowManualReviewModal(open);

        if (!open) {
            window.location.href = '/login';
        }
    };

    const verifyOtp = () => {
        post(`${activationPath}/verify-otp`, {
            preserveScroll: true,
        });
    };

        const canUseOtp = otpEnabled && !schoolData.is_activated;

        const submitLabel = schoolData.is_activated
        ? 'Update Details'
        : canUseOtp
          ? 'Send OTP'
          : 'Submit Activation Request';

    /**
     * Format phone number to 09xxxxxxxxx format
     */
    const formatPhoneNumber = (value: string): string => {
        return value.replace(/[^\d+\-\s()]/g, '').slice(0, 15);
    };

    /**
     * Convert text to uppercase
     */
    const handleUppercaseInput = (
        value: string,
        fieldName: string,
        maxLength = 50,
    ): void => {
        const uppercase = value.toUpperCase().slice(0, maxLength);
        setData(fieldName as any, uppercase);
    };

    return (
        <>
            <Head title="School Activation" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="mx-auto max-w-7xl rounded-2xl border border-border bg-card p-6 shadow-sm md:p-8">
                    <h1 className="text-2xl font-bold text-slate-900">School Activation</h1>
                    <p className="mt-1 text-sm text-slate-600">
                        {schoolData.is_activated
                            ? 'Update your school details before proceeding to learning resources.'
                            : canUseOtp
                              ? 'Confirm your information, receive a 6-digit OTP, then verify within 5 minutes to activate.'
                              : 'Confirm your information and submit an activation request. Please check your email within 24 hours — you will receive your initial login credentials once your account has been activated.'}
                    </p>

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
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();

                                if (!otpPending) {
                                    sendOtp();
                                }
                            }}
                            className="mt-6 space-y-6"
                        >
                            {/* School Info Row - Read Only */}
                            <div className="grid gap-4 rounded-lg border-2 border-blue-200 bg-blue-50 p-4 lg:grid-cols-2">
                                <Field label="School ID">
                                    <Input
                                        value={schoolIdDisplay}
                                        readOnly
                                        tabIndex={-1}
                                        className="border-blue-300 bg-slate-100 font-semibold text-slate-900 focus-visible:ring-0"
                                    />
                                </Field>

                                <Field label="School Name">
                                    <Input
                                        value={schoolNameDisplay}
                                        readOnly
                                        tabIndex={-1}
                                        className="border-blue-300 bg-slate-100 font-semibold text-slate-900 focus-visible:ring-0"
                                    />
                                </Field>
                            </div>

                            {/* Location Dropdowns Row */}
                            <div className="grid gap-4 lg:grid-cols-3">
                                <Field label="Municipality" error={errors.municipality_id}>
                                    <select
                                        className="h-10 w-full rounded-md border border-slate-400 bg-white px-3 text-sm"
                                        value={data.municipality_id}
                                        onChange={(event) => {
                                            const nextMunicipalityId = Number(
                                                event.target.value,
                                            );
                                            const nextDistrict = districts.find(
                                                (district) =>
                                                    district.municipality_id ===
                                                    nextMunicipalityId,
                                            );

                                            setData((currentData) => ({
                                                ...currentData,
                                                municipality_id: nextMunicipalityId,
                                                district_id: nextDistrict?.id ?? '',
                                                barangay_id: '',
                                            }));
                                        }}
                                    >
                                        <option value="">Select Municipality</option>
                                        {municipalities.map((municipality) => (
                                            <option
                                                key={municipality.id}
                                                value={municipality.id}
                                            >
                                                {municipality.name}
                                            </option>
                                        ))}
                                    </select>
                                </Field>

                                <Field label="District" error={errors.district_id}>
                                    <select
                                        className="h-10 w-full rounded-md border border-slate-400 bg-white px-3 text-sm"
                                        value={data.district_id}
                                        onChange={(event) =>
                                            setData('district_id', Number(event.target.value))
                                        }
                                    >
                                        <option value="">Select District</option>
                                        {filteredDistricts.map((district) => (
                                            <option key={district.id} value={district.id}>
                                                {district.name}
                                            </option>
                                        ))}
                                    </select>
                                </Field>

                                <Field label="Barangay" error={errors.barangay_id}>
                                    <select
                                        className="h-10 w-full rounded-md border border-slate-400 bg-white px-3 text-sm"
                                        value={data.barangay_id}
                                        onChange={(event) =>
                                            setData(
                                                'barangay_id',
                                                event.target.value === ''
                                                    ? ''
                                                    : Number(event.target.value),
                                            )
                                        }
                                    >
                                        <option value="">Select Barangay</option>
                                        {filteredBarangays.map((barangay) => (
                                            <option
                                                key={barangay.id}
                                                value={barangay.id}
                                            >
                                                {barangay.name}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                            </div>

                            {/* Personnel Info Row */}
                            <div className="grid gap-4 lg:grid-cols-3">
                                <Field label="School Head *" error={errors.school_head}>
                                    <Input
                                        value={data.school_head}
                                        maxLength={80}
                                        onChange={(event) =>
                                            handleUppercaseInput(event.target.value, 'school_head', 80)
                                        }
                                        placeholder="ENTER SCHOOL HEAD NAME"
                                        required
                                    />
                                </Field>

                                <Field label="Property Custodian" error={errors.property_custodian}>
                                    <Input
                                        value={data.property_custodian}
                                        maxLength={50}
                                        onChange={(event) =>
                                            handleUppercaseInput(
                                                event.target.value,
                                                'property_custodian',
                                            )
                                        }
                                        placeholder="ENTER CUSTODIAN NAME"
                                    />
                                </Field>

                                <Field label="Librarian" error={errors.librarian}>
                                    <Input
                                        value={data.librarian}
                                        maxLength={50}
                                        onChange={(event) =>
                                            handleUppercaseInput(event.target.value, 'librarian')
                                        }
                                        placeholder="ENTER LIBRARIAN NAME"
                                    />
                                </Field>
                            </div>

                            {/* Contact Info Row */}
                            <div className="grid gap-4 lg:grid-cols-3">
                                <Field
                                    label="Primary Mobile No. (09xxxxxxxxx)"
                                    error={errors.primary_mobile_no}
                                >
                                    <Input
                                        type="tel"
                                        value={data.primary_mobile_no}
                                        maxLength={15}
                                        onChange={(event) => {
                                            const formatted = formatPhoneNumber(event.target.value);
                                            setData('primary_mobile_no', formatted);
                                        }}
                                        placeholder="09XXXXXXXXX"
                                    />
                                </Field>

                                <Field
                                    label="Secondary Mobile No. (09xxxxxxxxx)"
                                    error={errors.secondary_mobile_no}
                                >
                                    <Input
                                        type="tel"
                                        value={data.secondary_mobile_no}
                                        maxLength={15}
                                        onChange={(event) => {
                                            const formatted = formatPhoneNumber(event.target.value);
                                            setData('secondary_mobile_no', formatted);
                                        }}
                                        placeholder="09XXXXXXXXX"
                                    />
                                </Field>

                                <Field label="Email Address *" error={errors.email}>
                                    <Input
                                        type="email"
                                        value={data.email}
                                        maxLength={50}
                                        onChange={(event) => setData('email', event.target.value)}
                                        placeholder="EMAIL@EXAMPLE.COM"
                                        required
                                    />
                                </Field>
                            </div>

                            {/* OTP Section */}
                            {!schoolData.is_activated && canUseOtp && otpPending ? (
                                <>
                                    <div className="space-y-4 rounded-xl border border-blue-200 bg-blue-50 p-4">
                                        <div>
                                            <label
                                                htmlFor="otp"
                                                className="mb-1 block text-sm font-medium text-slate-700"
                                            >
                                                6-Digit OTP
                                            </label>
                                            <Input
                                                id="otp"
                                                value={data.otp}
                                                maxLength={6}
                                                onChange={(event) =>
                                                    setData(
                                                        'otp',
                                                        event.target.value
                                                            .replace(/\D/g, '')
                                                            .slice(0, 6),
                                                    )
                                                }
                                                placeholder="ENTER 6-DIGIT OTP"
                                                required
                                            />
                                            <InputError message={errors.otp} />
                                            {otpExpiresAt && (
                                                <p className="mt-1 text-xs text-slate-600">
                                                    OTP expires at:{' '}
                                                    {new Date(otpExpiresAt).toLocaleTimeString()}
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                onClick={verifyOtp}
                                                disabled={processing}
                                            >
                                                {processing ? 'Verifying...' : 'Verify OTP and Activate'}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={sendOtp}
                                                disabled={processing}
                                            >
                                                Resend OTP
                                            </Button>
                                        </div>
                                    </div>
                                </>
                                ) : (
                                    <>
                                        <div className="grid gap-4 lg:grid-cols-3 lg:items-end">
                                            {!schoolData.is_activated && (
                                                <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 lg:col-span-2">
                                                    <input
                                                        type="checkbox"
                                                        id="agree-terms"
                                                        checked={agreeToTerms}
                                                        onChange={(e) => setAgreeToTerms(e.target.checked)}
                                                        className="h-5 w-5 rounded border-slate-400 text-slate-900 mt-0.5"
                                                    />
                                                    <label
                                                        htmlFor="agree-terms"
                                                        className="text-xs text-slate-700 leading-relaxed cursor-pointer"
                                                    >
                                                        By activating, you agree to send data for the purpose of collecting information about your school and learning resources.
                                                    </label>
                                                </div>
                                            )}

                                            <Button
                                                type="submit"
                                                disabled={processing || (!schoolData.is_activated && !agreeToTerms)}
                                                className="h-12 lg:col-span-1"
                                            >
                                                {processing ? (
                                                    <span className="inline-flex items-center gap-2">
                                                        <Loader2 className="h-4 w-4 animate-spin" />
                                                        Processing...
                                                    </span>
                                                ) : (
                                                    submitLabel
                                                )}
                                            </Button>
                                        </div>
                                    </>
                            )}
                        </form>
                    )}
                </div>
            </main>

            <Dialog open={showManualReviewModal} onOpenChange={closeManualReviewModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Activation Request Submitted</DialogTitle>
                        <DialogDescription>
                            Please check your email within 24 hours. Your school account will be reviewed by the
                            division office, and you will receive your initial login credentials by email once it
                            has been activated.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" onClick={() => closeManualReviewModal(false)}>
                            Got it
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SchoolActivationPage.layout = {
    breadcrumbs: [
        {
            title: 'Update School Details',
            href: '/dashboard',
        },
    ],
};

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
