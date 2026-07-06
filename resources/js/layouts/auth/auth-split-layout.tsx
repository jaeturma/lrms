import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { GlobalFooter } from '@/components/global-footer';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

type Branding = {
    login_title?: string;
    login_logo_url?: string | null;
    app_title?: string;
    app_logo_url?: string | null;
};

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name, branding } = usePage<{ name: string; branding?: Branding }>().props;

    return (
        <div className="flex min-h-svh flex-col">
            <div className="relative grid flex-1 flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
                <div className="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r">
                    <div className="absolute inset-0 bg-zinc-900" />
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center text-lg font-medium"
                    >
                        {branding?.app_logo_url ? (
                            <img src={branding.app_logo_url} alt="App logo" className="mr-2 size-8 object-contain" />
                        ) : (
                            <AppLogoIcon className="mr-2 size-8 fill-current text-white" />
                        )}
                        {branding?.app_title || name}
                    </Link>
                </div>
                <div className="w-full lg:p-8">
                    <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                        <Link
                            href={home()}
                            className="relative z-20 flex items-center justify-center lg:hidden"
                        >
                            {branding?.login_logo_url ? (
                                <img src={branding.login_logo_url} alt="Login logo" className="h-10 object-contain sm:h-12" />
                            ) : (
                                <AppLogoIcon className="h-10 fill-current text-black sm:h-12" />
                            )}
                        </Link>
                        <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                            <h1 className="text-xl font-medium">{branding?.login_title || title}</h1>
                            <p className="text-sm text-balance text-muted-foreground">
                                {description}
                            </p>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
            <GlobalFooter />
        </div>
    );
}
