import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { resolveBrandingImageUrl } from '@/lib/branding';

type Branding = {
    app_title?: string;
    app_logo_url?: string | null;
};

export default function AppLogo() {
    const { branding } = usePage<{ branding?: Branding }>().props;
    const [logoFailed, setLogoFailed] = useState(false);
    const logoUrl = resolveBrandingImageUrl(branding?.app_logo_url);

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center">
                {logoUrl && !logoFailed ? (
                    <img
                        src={logoUrl}
                        alt="App logo"
                        className="size-8 object-contain"
                        onError={() => setLogoFailed(true)}
                    />
                ) : (
                    <AppLogoIcon className="size-8 fill-current text-[var(--foreground)]" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {branding?.app_title || 'LRMS'}
                </span>
            </div>
        </>
    );
}
