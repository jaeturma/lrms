import { Head, useForm, usePage } from '@inertiajs/react';
import { Cog } from 'lucide-react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { PageHeaderIcon } from '@/components/page-header-icon';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Settings = {
    login_title: string;
    login_logo_url?: string | null;
    app_title: string;
    app_logo_url?: string | null;
    smtp_enabled?: boolean;
    smtp_host?: string | null;
    smtp_port?: string | null;
    smtp_username?: string | null;
    smtp_password?: string | null;
    smtp_encryption?: 'tls' | 'ssl' | '' | null;
    smtp_from_address?: string | null;
    smtp_from_name?: string | null;
};

type Props = {
    settings: Settings;
};

type PageProps = {
    flash?: {
        status?: string;
    };
};

export default function AdminAppSettings({ settings }: Props) {
    const page = usePage<PageProps>();
    const status = page.props.flash?.status;

    const { data, setData, post, processing, errors } = useForm({
        _method: 'put' as const,
        login_title: settings.login_title ?? '',
        login_logo_url: settings.login_logo_url ?? '',
        login_logo_file: null as File | null,
        app_title: settings.app_title ?? '',
        app_logo_url: settings.app_logo_url ?? '',
        app_logo_file: null as File | null,
        smtp_enabled: settings.smtp_enabled ?? false,
        smtp_host: settings.smtp_host ?? '',
        smtp_port: settings.smtp_port ?? '',
        smtp_username: settings.smtp_username ?? '',
        smtp_password: settings.smtp_password ?? '',
        smtp_encryption: settings.smtp_encryption ?? '',
        smtp_from_address: settings.smtp_from_address ?? '',
        smtp_from_name: settings.smtp_from_name ?? '',
    });

    const submit = () => {
        post('/app/admin/settings', {
            forceFormData: true,
        });
    };

    const normalizeLogoUrl = (value?: string | null): string | null => {
        if (!value) {
            return null;
        }

        if (value.startsWith('http://') || value.startsWith('https://')) {
            return value;
        }

        if (value.startsWith('/storage/')) {
            return value;
        }

        if (value.startsWith('storage/')) {
            return `/${value}`;
        }

        if (value.startsWith('/branding/')) {
            return `/storage${value}`;
        }

        if (value.startsWith('branding/')) {
            return `/storage/${value}`;
        }

        return value;
    };

    const loginLogoUrl = normalizeLogoUrl(data.login_logo_url);
    const appLogoUrl = normalizeLogoUrl(data.app_logo_url);

    return (
        <>
            <Head title="App Settings" />

            <main className="min-h-screen bg-background/40 p-4 md:p-8">
                <div className="mx-auto max-w-5xl space-y-6">
                    <header className="flex items-center gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <PageHeaderIcon
                            icon={Cog}
                            className="bg-slate-950 text-slate-400 dark:bg-slate-900/60 dark:text-slate-300"
                        />
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">App Settings</h1>
                            <p className="text-sm text-muted-foreground">Manage branding, logos, and Gmail SMTP configuration for OTP emails.</p>
                        </div>
                    </header>

                    {status && (
                        <p className="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {status}
                        </p>
                    )}

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">Branding</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Login Title" error={errors.login_title}>
                                <Input value={data.login_title} onChange={(event) => setData('login_title', event.target.value)} />
                            </Field>
                            <Field label="Login Logo Upload" error={errors.login_logo_file || errors.login_logo_url}>
                                <Input
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) => setData('login_logo_file', event.target.files?.[0] ?? null)}
                                />
                                {loginLogoUrl && (
                                    <img src={loginLogoUrl} alt="Current login logo" className="mt-2 h-14 w-auto rounded border border-border p-1" />
                                )}
                            </Field>
                            <Field label="App Title" error={errors.app_title}>
                                <Input value={data.app_title} onChange={(event) => setData('app_title', event.target.value)} />
                            </Field>
                            <Field label="App Logo Upload" error={errors.app_logo_file || errors.app_logo_url}>
                                <Input
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) => setData('app_logo_file', event.target.files?.[0] ?? null)}
                                />
                                {appLogoUrl && (
                                    <img src={appLogoUrl} alt="Current app logo" className="mt-2 h-14 w-auto rounded border border-border p-1" />
                                )}
                            </Field>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">Gmail SMTP for OTP</h2>

                        <div className="mb-4 flex items-start gap-3 rounded-lg border border-border bg-muted/40 p-4">
                            <Checkbox
                                id="smtp_enabled"
                                checked={data.smtp_enabled}
                                onCheckedChange={(checked) => setData('smtp_enabled', checked === true)}
                            />
                            <div>
                                <Label htmlFor="smtp_enabled" className="font-medium text-foreground">
                                    Enable SMTP Email Sending
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    When off, no emails are sent (school activation OTP, initial login credentials, or
                                    password-reset OTP) even if the fields below are filled in — pages will show a
                                    message asking to coordinate manually instead.
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="SMTP Host" error={errors.smtp_host}>
                                <Input value={data.smtp_host} onChange={(event) => setData('smtp_host', event.target.value)} placeholder="smtp.gmail.com" />
                            </Field>
                            <Field label="SMTP Port" error={errors.smtp_port}>
                                <Input value={data.smtp_port} onChange={(event) => setData('smtp_port', event.target.value)} placeholder="587" />
                            </Field>
                            <Field label="SMTP Username" error={errors.smtp_username}>
                                <Input value={data.smtp_username} onChange={(event) => setData('smtp_username', event.target.value)} placeholder="your@gmail.com" />
                            </Field>
                            <Field label="SMTP Password / App Password" error={errors.smtp_password}>
                                <PasswordInput
                                    id="smtp_password"
                                    value={data.smtp_password}
                                    onChange={(event) => setData('smtp_password', event.target.value)}
                                />
                            </Field>
                            <Field label="Encryption" error={errors.smtp_encryption}>
                                <select
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    value={data.smtp_encryption}
                                    onChange={(event) => setData('smtp_encryption', event.target.value as 'tls' | 'ssl' | '')}
                                >
                                    <option value="">None</option>
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </Field>
                            <Field label="From Email" error={errors.smtp_from_address}>
                                <Input value={data.smtp_from_address} onChange={(event) => setData('smtp_from_address', event.target.value)} placeholder="your@gmail.com" />
                            </Field>
                            <Field label="From Name" error={errors.smtp_from_name}>
                                <Input value={data.smtp_from_name} onChange={(event) => setData('smtp_from_name', event.target.value)} placeholder="LRMS OTP" />
                            </Field>
                        </div>
                    </section>

                    <div>
                        <Button type="button" onClick={submit} disabled={processing}>
                            {processing ? 'Saving...' : 'Save Settings'}
                        </Button>
                    </div>
                </div>
            </main>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-foreground">{label}</label>
            {children}
            <InputError message={error} />
        </div>
    );
}
