import { Head, usePage } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { GlobalFooter } from '@/components/global-footer';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { resolveBrandingImageUrl } from '@/lib/branding';
import http from '@/lib/http';

type FindSchoolResponse = {
    next_url?: string;
    redirect_url?: string;
    message?: string;
    is_activated?: boolean;
};

type Branding = {
    login_logo_url?: string | null;
    app_logo_url?: string | null;
};

export default function HomePage() {
    const { branding } = usePage<{ branding?: Branding }>().props;
    const [schoolId, setSchoolId] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [loading, setLoading] = useState(false);
    const [logoFailed, setLogoFailed] = useState(false);
    const logoUrl = resolveBrandingImageUrl(
        branding?.login_logo_url ?? branding?.app_logo_url,
    );

    const handleContinue = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setError(undefined);
        setLoading(true);

        try {
            const response = await http.post<FindSchoolResponse>('/school/find', {
                school_id: schoolId,
            });

            if (response.data.message) {
                alert(response.data.message);
            }

            const redirectUrl = response.data.is_activated
                ? response.data.redirect_url
                : response.data.next_url;

            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        } catch {
            setError('Invalid School ID. Please verify and try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Learning Resources Monitoring System" />

            <main className="flex min-h-screen flex-col bg-background/40 px-4 py-10 md:px-8">
                <div className="mx-auto w-full max-w-5xl rounded-3xl border border-border bg-card p-6 shadow-xl md:p-10">
                    <div className="mb-8 space-y-2 text-center">
                        <div className="mb-3 flex justify-center">
                            {logoUrl && !logoFailed ? (
                                <img
                                    src={logoUrl}
                                    alt="Login logo"
                                    className="h-20 w-20 object-contain"
                                    onError={() => setLogoFailed(true)}
                                />
                            ) : (
                                <AppLogoIcon className="h-20 w-20 fill-current text-[var(--foreground)]" />
                            )}
                        </div>
                        <p className="text-xs font-semibold tracking-[0.3em] text-slate-500">
                            DEPARTMENT OF EDUCATION
                        </p>
                        <p className="text-sm font-semibold tracking-[0.18em] text-foreground">
                            SCHOOLS DIVISION OF DAVAO DE ORO
                        </p>
                        <p className="text-xs tracking-[0.24em] text-muted-foreground">
                            CURRICULUM IMPLEMENTATION DIVISION - LRMDC
                        </p>
                        <h1 className="text-2xl font-bold text-slate-900 md:text-4xl">
                            Learning Resources Monitoring System
                        </h1>
                        <p className="mx-auto max-w-2xl text-sm text-slate-600 md:text-base">
                            Enter your School ID to activate your account and report defective learning resources.
                        </p>
                    </div>

                    <form
                        onSubmit={handleContinue}
                        className="mx-auto grid max-w-xl gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <label htmlFor="school_id" className="text-sm font-medium text-slate-700">
                            School ID
                        </label>
                        <Input
                            id="school_id"
                            name="school_id"
                            value={schoolId}
                            onChange={(event) => setSchoolId(event.target.value)}
                            placeholder="e.g. SID-10001"
                            required
                        />
                        <InputError message={error} />
                        <Button type="submit" disabled={loading}>
                            {loading ? 'Checking...' : 'Continue'}
                        </Button>
                    </form>
                </div>
                <div className="mt-6">
                    <GlobalFooter />
                </div>
            </main>
        </>
    );
}
