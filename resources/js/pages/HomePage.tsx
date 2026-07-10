import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookText,
    FlaskConical,
    LayoutGrid,
    LogIn,
    MonitorPlay,
    MonitorSmartphone,
    Search,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { GlobalFooter } from '@/components/global-footer';
import HeroResourcesIllustration from '@/components/illustrations/hero-resources-illustration';
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

const COVERAGE = [
    {
        icon: BookText,
        title: 'Learning Resources',
        description: 'Track books, modules, and instructional materials issued to your school.',
        color: 'text-amber-600 bg-amber-500/10 dark:text-amber-400',
    },
    {
        icon: MonitorSmartphone,
        title: 'ICT Equipment',
        description: 'Monitor computers, printers, and other ICT devices in your inventory.',
        color: 'text-blue-600 bg-blue-500/10 dark:text-blue-400',
    },
    {
        icon: FlaskConical,
        title: 'Science & Math Equipment',
        description: 'Register and report the condition of SME kits and laboratory tools.',
        color: 'text-emerald-600 bg-emerald-500/10 dark:text-emerald-400',
    },
    {
        icon: MonitorPlay,
        title: 'Digital Learning Materials',
        description: 'Keep tabs on e-learning media, software, and digital content assets.',
        color: 'text-purple-600 bg-purple-500/10 dark:text-purple-400',
    },
];

const STEPS = [
    {
        icon: Search,
        title: 'Enter your School ID',
        description: 'Type your 10-character School ID to look up your school record.',
    },
    {
        icon: ShieldCheck,
        title: 'Verify & set your password',
        description: 'Confirm your details and create a secure password for your account.',
    },
    {
        icon: LayoutGrid,
        title: 'Access your dashboard',
        description: 'Start tracking and reporting your learning resources division-wide.',
    },
];

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

            <div className="relative overflow-hidden">
                <div aria-hidden className="pointer-events-none absolute inset-0 -z-10">
                    <div className="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute top-1/3 -right-24 h-96 w-96 rounded-full bg-amber-400/10 blur-3xl" />
                    <div
                        className="absolute inset-0 opacity-[0.35] dark:opacity-[0.15]"
                        style={{
                            backgroundImage: 'radial-gradient(currentColor 1px, transparent 1px)',
                            backgroundSize: '24px 24px',
                            color: 'var(--border)',
                            maskImage: 'linear-gradient(180deg, black, transparent 70%)',
                        }}
                    />
                </div>

                <main className="mx-auto w-full max-w-6xl px-4 py-10 md:px-8 md:py-14">
                    <div className="mb-10 flex flex-col items-center gap-2 text-center">
                        {logoUrl && !logoFailed ? (
                            <img
                                src={logoUrl}
                                alt="Login logo"
                                className="h-14 w-14 object-contain"
                                onError={() => setLogoFailed(true)}
                            />
                        ) : (
                            <AppLogoIcon className="h-14 w-14 fill-current text-foreground" />
                        )}
                        <p className="text-xs font-semibold tracking-[0.3em] text-muted-foreground">
                            DEPARTMENT OF EDUCATION
                        </p>
                        <p className="text-sm font-semibold tracking-[0.18em] text-primary">
                            SCHOOLS DIVISION OF DAVAO DE ORO
                        </p>
                        <p className="text-xs tracking-[0.24em] text-muted-foreground">
                            CURRICULUM IMPLEMENTATION DIVISION - LRMDC
                        </p>
                    </div>

                    <div className="grid items-center gap-12 lg:grid-cols-2">
                        <div>
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold tracking-wide text-primary">
                                <Sparkles className="size-3.5" />
                                DIVISION LEARNING RESOURCE PORTAL
                            </span>

                            <h1 className="mt-4 text-3xl leading-tight font-bold text-balance text-foreground md:text-5xl">
                                Learning Resources Monitoring System
                            </h1>
                            <p className="mt-4 max-w-xl text-base text-muted-foreground md:text-lg">
                                One portal to activate your school account, track learning resources and
                                equipment, and report defective materials across the division.
                            </p>

                            <form
                                onSubmit={handleContinue}
                                className="mt-8 max-w-xl space-y-4 rounded-2xl border border-border bg-card p-5 shadow-lg sm:p-6"
                            >
                                <div>
                                    <label htmlFor="school_id" className="text-sm font-medium text-foreground">
                                        School ID
                                    </label>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Enter your 10-character School ID to get started.
                                    </p>
                                </div>
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
                                    <Button type="button" asChild variant="outline" className="flex-1">
                                        <Link href="/login">
                                            <LogIn className="size-4" />
                                            Login
                                        </Link>
                                    </Button>
                                </div>
                            </form>
                        </div>

                        <div className="mx-auto w-full max-w-lg text-foreground/80">
                            <HeroResourcesIllustration className="w-full drop-shadow-xl" />
                        </div>
                    </div>

                    <section className="mt-20">
                        <div className="mx-auto max-w-2xl text-center">
                            <span className="text-xs font-semibold tracking-[0.2em] text-primary">
                                SYSTEM COVERAGE
                            </span>
                            <h2 className="mt-2 text-2xl font-bold text-foreground md:text-3xl">
                                Everything your school needs to track
                            </h2>
                        </div>

                        <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {COVERAGE.map(({ icon: Icon, title, description, color }) => (
                                <div
                                    key={title}
                                    className="rounded-2xl border border-border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <span className={`inline-flex size-11 items-center justify-center rounded-xl ${color}`}>
                                        <Icon className="size-5" />
                                    </span>
                                    <h3 className="mt-4 text-sm font-semibold text-foreground">{title}</h3>
                                    <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="mt-20">
                        <div className="mx-auto max-w-2xl text-center">
                            <span className="text-xs font-semibold tracking-[0.2em] text-primary">
                                GETTING STARTED
                            </span>
                            <h2 className="mt-2 text-2xl font-bold text-foreground md:text-3xl">
                                Three steps to your dashboard
                            </h2>
                        </div>

                        <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
                            {STEPS.map(({ icon: Icon, title, description }, index) => (
                                <div key={title} className="relative rounded-2xl border border-border bg-card/60 p-5">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                                            {index + 1}
                                        </span>
                                        <Icon className="size-5 text-primary" />
                                    </div>
                                    <h3 className="mt-3 text-sm font-semibold text-foreground">{title}</h3>
                                    <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                                </div>
                            ))}
                        </div>
                    </section>
                </main>

                <div className="mt-16">
                    <GlobalFooter />
                </div>
            </div>
        </>
    );
}
