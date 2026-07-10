import { Link, usePage } from '@inertiajs/react';
import { BookOpenCheck, ClipboardCheck, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { GlobalFooter } from '@/components/global-footer';
import LearningResourcesIllustration from '@/components/illustrations/learning-resources-illustration';
import { resolveBrandingImageUrl } from '@/lib/branding';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

type Branding = {
    login_title?: string;
    login_logo_url?: string | null;
    app_logo_url?: string | null;
};

const FEATURES = [
    { icon: BookOpenCheck, label: 'Track learning resource inventory in real time' },
    { icon: ClipboardCheck, label: 'Report and monitor defective materials fast' },
    { icon: ShieldCheck, label: 'Secure, role-based access for every school' },
];

export default function AuthResourcesLayout({ children }: AuthLayoutProps) {
    const { branding } = usePage<{ branding?: Branding }>().props;
    const [logoFailed, setLogoFailed] = useState(false);
    const logoUrl = resolveBrandingImageUrl(branding?.login_logo_url ?? branding?.app_logo_url);

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <div className="grid flex-1 lg:grid-cols-2">
                <div className="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-[#0b1c3d] via-[#132a63] to-[#1d3f8f] p-10 text-white lg:flex xl:p-14">
                    <div aria-hidden className="pointer-events-none absolute inset-0">
                        <div className="absolute -top-24 -left-20 h-72 w-72 rounded-full bg-white/10 blur-3xl" />
                        <div className="absolute -right-16 -bottom-32 h-96 w-96 rounded-full bg-amber-400/20 blur-3xl" />
                        <div
                            className="absolute inset-0 opacity-[0.06]"
                            style={{
                                backgroundImage: 'radial-gradient(currentColor 1px, transparent 1px)',
                                backgroundSize: '22px 22px',
                            }}
                        />
                    </div>

                    <Link href={home()} className="relative z-10 flex items-center gap-3">
                        {logoUrl && !logoFailed ? (
                            <img
                                src={logoUrl}
                                alt="App logo"
                                className="size-10 object-contain"
                                onError={() => setLogoFailed(true)}
                            />
                        ) : (
                            <AppLogoIcon className="size-10 fill-current text-white" />
                        )}
                        <div className="leading-tight">
                            <p className="text-[10px] font-semibold tracking-[0.25em] text-white/60">DEPARTMENT OF EDUCATION</p>
                            <p className="text-sm font-semibold tracking-wide text-white">
                                {branding?.login_title || 'Learning Resources Monitoring System'}
                            </p>
                        </div>
                    </Link>

                    <div className="relative z-10 space-y-8">
                        <LearningResourcesIllustration className="h-52 w-full max-w-md text-white" />

                        <div className="space-y-3">
                            <h2 className="text-3xl leading-tight font-bold text-balance text-white">
                                Empowering every learning resource, division-wide.
                            </h2>
                            <p className="max-w-md text-sm text-white/75">
                                A unified portal for schools and the Curriculum Implementation Division to monitor,
                                report, and manage learning resources with confidence.
                            </p>
                        </div>

                        <ul className="space-y-3">
                            {FEATURES.map(({ icon: Icon, label }) => (
                                <li key={label} className="flex items-center gap-3 text-sm text-white/90">
                                    <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-white/10">
                                        <Icon className="size-4" />
                                    </span>
                                    {label}
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="relative z-10 text-xs text-white/50">
                        Schools Division of Davao de Oro &middot; Curriculum Implementation Division - LRMDC
                    </p>
                </div>

                <div className="flex flex-col items-center justify-center gap-6 px-6 py-10 sm:px-10">
                    <Link href={home()} className="flex items-center gap-2 lg:hidden">
                        {logoUrl && !logoFailed ? (
                            <img
                                src={logoUrl}
                                alt="App logo"
                                className="size-10 object-contain"
                                onError={() => setLogoFailed(true)}
                            />
                        ) : (
                            <AppLogoIcon className="size-10 fill-current text-foreground" />
                        )}
                    </Link>
                    <div className="w-full max-w-sm">{children}</div>
                </div>
            </div>
            <GlobalFooter />
        </div>
    );
}
