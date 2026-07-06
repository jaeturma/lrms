import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types/navigation';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            branding?: {
                login_title?: string;
                login_logo_url?: string | null;
                app_title?: string;
                app_logo_url?: string | null;
            };
            auth: Auth;
            breadcrumbs?: BreadcrumbItem[];
            flash?: {
                status?: string;
                generatedPassword?: string;
                generatedEmail?: string;
                otpPending?: boolean;
                otpExpiresAt?: string;
                importSummary?: unknown;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
