import { Head, useForm, usePage } from '@inertiajs/react';
import { Cog, Send } from 'lucide-react';
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
    website_logo_url?: string | null;
    color_theme?: string;
    recaptcha_enabled?: boolean;
    recaptcha_site_key?: string | null;
    recaptcha_secret_set?: boolean;
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
    colorThemes: Record<string, string>;
};

const THEME_SWATCHES: Record<string, string> = {
    slate: 'oklch(0.34 0.2 259)',
    ocean: 'oklch(0.38 0.15 235)',
    forest: 'oklch(0.34 0.12 155)',
    sunset: 'oklch(0.42 0.18 38)',
    rose: 'oklch(0.42 0.2 358)',
};

type PageProps = {
    flash?: {
        status?: string;
    };
};

export default function AdminAppSettings({ settings, colorThemes }: Props) {
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
        website_logo_url: settings.website_logo_url ?? '',
        website_logo_file: null as File | null,
        color_theme: settings.color_theme ?? 'slate',
        recaptcha_enabled: settings.recaptcha_enabled ?? false,
        recaptcha_site_key: settings.recaptcha_site_key ?? '',
        recaptcha_secret_key: '',
        smtp_enabled: settings.smtp_enabled ?? false,
        smtp_host: settings.smtp_host ?? '',
        smtp_port: settings.smtp_port ?? '',
        smtp_username: settings.smtp_username ?? '',
        smtp_password: settings.smtp_password ?? '',
        smtp_encryption: settings.smtp_encryption ?? '',
        smtp_from_address: settings.smtp_from_address ?? '',
        smtp_from_name: settings.smtp_from_name ?? '',
    });

    const testMail = useForm({ test_email: '' });

    const submit = () => {
        post('/app/admin/settings', {
            forceFormData: true,
            onSuccess: () => setData('recaptcha_secret_key', ''),
        });
    };

    const sendTestMail = () => {
        testMail.post('/app/admin/settings/test-mail', { preserveScroll: true });
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
    const websiteLogoUrl = normalizeLogoUrl(data.website_logo_url);

    return (
        <>
            <Head title="App Settings" />

            <main className="bg-background/40 p-3 md:p-4">
                <div className="space-y-4">
                    <header className="flex items-center gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <PageHeaderIcon
                            icon={Cog}
                            className="bg-slate-100 text-slate-600 dark:bg-slate-900/60 dark:text-slate-300"
                        />
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">App Settings</h1>
                            <p className="text-sm text-muted-foreground">Manage the system color theme, logos, SMTP email sending and Google reCAPTCHA.</p>
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
                            <Field label="Website (Landing Page) Logo Upload" error={errors.website_logo_file || errors.website_logo_url}>
                                <Input
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) => setData('website_logo_file', event.target.files?.[0] ?? null)}
                                />
                                {websiteLogoUrl && (
                                    <img src={websiteLogoUrl} alt="Current website logo" className="mt-2 h-14 w-auto rounded border border-border p-1" />
                                )}
                                <p className="mt-1 text-xs text-muted-foreground">Shown on the public landing page. Falls back to the login logo.</p>
                            </Field>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-1 text-lg font-semibold text-foreground">System Color Theme</h2>
                        <p className="mb-4 text-sm text-muted-foreground">
                            Default colors for every user. Users who pick their own theme under Settings → Appearance keep their choice.
                        </p>
                        <div role="radiogroup" aria-label="System color theme" className="flex flex-wrap gap-3">
                            {Object.entries(colorThemes).map(([value, label]) => (
                                <button
                                    key={value}
                                    type="button"
                                    role="radio"
                                    aria-checked={data.color_theme === value}
                                    onClick={() => setData('color_theme', value)}
                                    className={`flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-medium ${
                                        data.color_theme === value
                                            ? 'border-primary ring-2 ring-primary/40'
                                            : 'border-border text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    <span className="size-5 rounded-full" style={{ backgroundColor: THEME_SWATCHES[value] }} />
                                    {label}
                                </button>
                            ))}
                        </div>
                        <InputError message={errors.color_theme} />
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

                        <div className="mt-5 rounded-lg border border-border bg-muted/40 p-4">
                            <p className="mb-2 text-sm font-medium text-foreground">Send a test email</p>
                            <p className="mb-3 text-sm text-muted-foreground">Uses the saved settings above — save first if you changed them.</p>
                            <div className="flex flex-wrap items-start gap-2">
                                <div className="min-w-64 flex-1">
                                    <Input
                                        type="email"
                                        aria-label="Test email recipient"
                                        value={testMail.data.test_email}
                                        onChange={(event) => testMail.setData('test_email', event.target.value)}
                                        placeholder="recipient@deped.gov.ph"
                                    />
                                    <InputError message={testMail.errors.test_email} />
                                </div>
                                <Button type="button" variant="outline" onClick={sendTestMail} disabled={testMail.processing}>
                                    <Send className="mr-1 size-4" />
                                    {testMail.processing ? 'Sending...' : 'Send Test Email'}
                                </Button>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold text-foreground">Google reCAPTCHA</h2>

                        <div className="mb-4 flex items-start gap-3 rounded-lg border border-border bg-muted/40 p-4">
                            <Checkbox
                                id="recaptcha_enabled"
                                checked={data.recaptcha_enabled}
                                onCheckedChange={(checked) => setData('recaptcha_enabled', checked === true)}
                            />
                            <div>
                                <Label htmlFor="recaptcha_enabled" className="font-medium text-foreground">
                                    Enable reCAPTCHA on sign-in
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    Adds Google's "I'm not a robot" check (reCAPTCHA v2 checkbox) to the school login, admin login and
                                    forgot-password forms. Create v2 checkbox keys for this site's domain in the Google reCAPTCHA admin console.
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <Field label="Site Key" error={errors.recaptcha_site_key}>
                                <Input value={data.recaptcha_site_key} onChange={(event) => setData('recaptcha_site_key', event.target.value)} />
                            </Field>
                            <Field label="Secret Key" error={errors.recaptcha_secret_key}>
                                <PasswordInput
                                    id="recaptcha_secret_key"
                                    value={data.recaptcha_secret_key}
                                    placeholder={settings.recaptcha_secret_set ? 'Saved — leave blank to keep' : ''}
                                    onChange={(event) => setData('recaptcha_secret_key', event.target.value)}
                                />
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
