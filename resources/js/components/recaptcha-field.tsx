import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import InputError from '@/components/input-error';

type Grecaptcha = {
    render: (
        container: HTMLElement,
        options: {
            sitekey: string;
            callback?: (token: string) => void;
            'expired-callback'?: () => void;
        },
    ) => number;
    reset: (widgetId?: number) => void;
};

declare global {
    interface Window {
        grecaptcha?: Grecaptcha;
        onLrmsRecaptchaLoad?: () => void;
    }
}

const SCRIPT_ID = 'google-recaptcha-script';
const loadCallbacks: Array<() => void> = [];

function whenRecaptchaReady(callback: () => void): void {
    if (window.grecaptcha?.render) {
        callback();

        return;
    }

    loadCallbacks.push(callback);

    if (document.getElementById(SCRIPT_ID)) {
        return;
    }

    window.onLrmsRecaptchaLoad = () => {
        loadCallbacks.splice(0).forEach((pending) => pending());
    };

    const script = document.createElement('script');
    script.id = SCRIPT_ID;
    script.src =
        'https://www.google.com/recaptcha/api.js?onload=onLrmsRecaptchaLoad&render=explicit';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
}

/**
 * Google reCAPTCHA v2 checkbox, shown only when an admin has enabled it in
 * App Settings. The widget adds a `g-recaptcha-response` field to the
 * surrounding form (forms using useForm get the token via `onToken`). It
 * resets whenever `resetKey` changes, e.g. the errors object after a failed
 * submit, since each token can only be checked once.
 */
export function RecaptchaField({
    error,
    resetKey,
    onToken,
}: {
    error?: string;
    resetKey?: unknown;
    onToken?: (token: string) => void;
}) {
    const { recaptcha } = usePage<{
        recaptcha?: { enabled: boolean; site_key: string | null };
    }>().props;
    const containerRef = useRef<HTMLDivElement>(null);
    const widgetId = useRef<number | null>(null);
    const onTokenRef = useRef(onToken);

    useEffect(() => {
        onTokenRef.current = onToken;
    }, [onToken]);
    const siteKey = recaptcha?.enabled ? recaptcha.site_key : null;

    useEffect(() => {
        if (!siteKey || !containerRef.current) {
            return;
        }

        whenRecaptchaReady(() => {
            if (containerRef.current && widgetId.current === null) {
                widgetId.current = window.grecaptcha!.render(
                    containerRef.current,
                    {
                        sitekey: siteKey,
                        callback: (token) => onTokenRef.current?.(token),
                        'expired-callback': () => onTokenRef.current?.(''),
                    },
                );
            }
        });
    }, [siteKey]);

    useEffect(() => {
        if (widgetId.current !== null) {
            window.grecaptcha?.reset(widgetId.current);
            onTokenRef.current?.('');
        }
    }, [resetKey]);

    if (!siteKey) {
        return null;
    }

    return (
        <div className="space-y-1">
            <div ref={containerRef} />
            <InputError message={error} />
        </div>
    );
}
