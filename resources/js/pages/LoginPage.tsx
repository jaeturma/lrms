import { Form, Head, Link } from '@inertiajs/react';
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

            <Form
                action="/login"
                method="post"
                className="mx-auto mt-2 w-full max-w-md space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm"
            >
                {({ errors, processing }) => (
                    <>
                        <div>
                            <h1 className="text-xl font-semibold text-foreground">School Login</h1>
                            <p className="text-sm text-muted-foreground">Sign in using your activated school email.</p>
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" name="email" required autoFocus autoComplete="email" />
                            <InputError message={errors.email} />
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="password">Password</Label>
                            <PasswordInput id="password" name="password" required autoComplete="current-password" />
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
                            {processing ? 'Signing in...' : 'Log In'}
                        </Button>

                        <p className="text-center text-sm text-muted-foreground">
                            Need to activate? <Link href="/" className="underline">Go to School ID page</Link>
                        </p>
                    </>
                )}
            </Form>

            {status && <p className="mt-4 text-center text-sm text-emerald-600 dark:text-emerald-400">{status}</p>}
        </>
    );
}

LoginPage.layout = {
    title: 'CID - LRMDC',
    description: 'SDO DDO - CID - LRMDC',
};
