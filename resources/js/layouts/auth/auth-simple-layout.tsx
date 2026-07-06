import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { GlobalFooter } from '@/components/global-footer';
import { resolveBrandingImageUrl } from '@/lib/branding';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

type Branding = {
    login_title?: string;
    login_logo_url?: string | null;
    app_logo_url?: string | null;
};

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { branding } = usePage<{ branding?: Branding }>().props;
    const [logoFailed, setLogoFailed] = useState(false);
    const logoUrl = resolveBrandingImageUrl(
        branding?.login_logo_url ?? branding?.app_logo_url,
    );

    return (
        <div
            className="flex min-h-svh flex-col"
            style={{
                backgroundImage:
                    'linear-gradient(180deg, var(--page-background-start) 0%, var(--background) 55%, var(--page-background-end) 100%)',
            }}
        >
            <div className="flex flex-1 items-center justify-center gap-3 p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <div className="flex flex-col gap-3">
                        <div className="flex flex-col items-center gap-2">
                            <Link
                                href={home()}
                                className="flex flex-col items-center gap-2 font-medium"
                            >
                                <div className="mb-1 flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-md">
                                    {logoUrl && !logoFailed ? (
                                        <img
                                            src={logoUrl}
                                            alt="Login logo"
                                            className="size-[4.5rem] object-contain"
                                            onError={() => setLogoFailed(true)}
                                        />
                                    ) : (
                                        <AppLogoIcon className="size-[4.5rem] fill-current text-[var(--foreground)] dark:text-white" />
                                    )}
                                </div>
                                <span className="sr-only">{branding?.login_title || title}</span>
                            </Link>

                            <div className="space-y-2 text-center">
                                <h1 className="text-xl font-medium">{branding?.login_title || title}</h1>
                                <p className="text-center text-sm text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
            <GlobalFooter />
        </div>
    );
}
