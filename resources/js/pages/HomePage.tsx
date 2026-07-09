import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, LogIn } from 'lucide-react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
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

    const updateSchoolId = (value: string) => {
        setSchoolId(value.slice(0, 10));
    };

    return (
        <>
            <Head title="Learning Resources Monitoring System" />

            <main className="flex min-h-screen flex-col items-center justify-center bg-background/40 px-4 py-10 md:px-8">
                <div className="relative mx-auto w-full max-w-5xl overflow-hidden rounded-3xl border border-border bg-card p-6 shadow-xl md:p-10">
                    <div className="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary via-amber-400 to-primary" />
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
                        <p className="text-xs font-semibold tracking-[0.3em] text-muted-foreground">
                            DEPARTMENT OF EDUCATION
                        </p>
                        <p className="text-sm font-semibold tracking-[0.18em] text-primary">
                            SCHOOLS DIVISION OF DAVAO DE ORO
                        </p>
                        <p className="text-xs tracking-[0.24em] text-muted-foreground">
                            CURRICULUM IMPLEMENTATION DIVISION - LRMDC
                        </p>
                        <h1 className="text-2xl font-bold text-foreground md:text-4xl">
                            Learning Resources Monitoring System
                        </h1>
                        <p className="mx-auto max-w-2xl text-sm text-muted-foreground md:text-base">
                            Enter your School ID to activate your account and report defective learning resources.
                        </p>
                    </div>

                    <form
                        onSubmit={handleContinue}
                        className="mx-auto grid max-w-xl gap-4 rounded-2xl border border-border bg-muted/50 p-5"
                    >
                        <label htmlFor="school_id" className="text-sm font-medium text-foreground">
                            School ID
                        </label>
                        <Input
                            id="school_id"
                            name="school_id"
                            value={schoolId}
                            onChange={(event) => updateSchoolId(event.target.value)}
                            placeholder="e.g. SID-10001"
                            maxLength={10}
                            required
                        />
                        <InputError message={error} />
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Button
                                type="submit"
                                disabled={loading}
                                className="flex-1 bg-red-900 text-white hover:bg-red-950"
                            >
                                <ArrowRight className="size-4" />
                                {loading ? 'Checking...' : 'Activate'}
                            </Button>
                            <Button type="button" asChild className="flex-1">
                                <Link href="/login">
                                    <LogIn className="size-4" />
                                    Login
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </main>
        </>
    );
}
