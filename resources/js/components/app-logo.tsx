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
    // "iLRMS - Integrated ..." shows as a short name with the rest underneath.
    const [appName, ...taglineParts] = (branding?.app_title || 'iLRMS').split(
        ' - ',
    );
    const tagline = taglineParts.join(' - ');

    return (
        <div className="flex min-w-0 items-center gap-2">
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
            <div className="ml-1 grid flex-1 min-w-0 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {appName}
                </span>
                {tagline && (
                    <span
                        className="truncate text-[10px] leading-tight text-muted-foreground"
                        title={tagline}
                    >
                        {tagline}
                    </span>
                )}
            </div>
        </div>
    );
}
