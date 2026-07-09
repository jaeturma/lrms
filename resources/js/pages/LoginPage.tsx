import { Form, Head, Link } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    status?: string;
};

export default function LoginPage({ status }: Props) {
    return (
        <>
            <Head title="School Login" />

            <div className="relative mx-auto mt-2 w-full max-w-md overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-lg">
                <div className="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary via-amber-400 to-primary" />

                <div className="mb-1">
                    <h1 className="text-xl font-semibold text-foreground">School Login</h1>
                    <p className="text-sm text-muted-foreground">Sign in using your activated school email.</p>
                </div>

                <Form action="/login" method="post" className="mt-5 space-y-4">
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-1">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" name="email" maxLength={50} required autoFocus autoComplete="email" />
                                <InputError message={errors.email} />
                            </div>

                            <div className="space-y-1">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput id="password" name="password" maxLength={30} required autoComplete="current-password" />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <Checkbox id="remember" name="remember" />
                                    <Label htmlFor="remember">Remember me</Label>
                                </div>
                                <Link
                                    href="/forgot-password"
                                    className="text-sm text-primary underline underline-offset-4"
                                >
                                    Forgot Password?
                                </Link>
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                <LogIn className="size-4" />
                                {processing ? 'Signing in...' : 'Log In'}
                            </Button>
                        </>
                    )}
                </Form>

                {status && <p className="mt-4 text-center text-sm text-emerald-600 dark:text-emerald-400">{status}</p>}

                <div className="mt-6 flex items-center justify-center gap-2 border-t border-border pt-4 text-sm">
                    <Link href="/" className="text-primary underline underline-offset-4">
                        Activate using School ID
                    </Link>
                    <span className="text-muted-foreground">|</span>
                    <Link href="/app/admin/login" className="text-primary underline underline-offset-4">
                        Admin Login
                    </Link>
                </div>
            </div>
        </>
    );
}

LoginPage.layout = {
    title: 'CID - LRMDC',
    description: 'SDO DDO - CID - LRMDC',
};
