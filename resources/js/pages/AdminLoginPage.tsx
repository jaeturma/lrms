import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function AdminLoginPage() {
    return (
        <>
            <Head title="Admin Login" />

            <Form
                action="/app/admin/login"
                method="post"
                className="mx-auto mt-6 w-full max-w-md space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm"
            >
                {({ errors, processing }) => (
                    <>
                        <div>
                            <h1 className="text-xl font-semibold text-foreground">Admin Login</h1>
                            <p className="text-sm text-muted-foreground">Access LRMS dashboard and reports.</p>
                        </div>

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
                            {processing ? 'Signing in...' : 'Log In as Admin'}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

AdminLoginPage.layout = {
    title: 'Admin Login',
    description: 'Login with admin credentials to manage LRMS',
};
