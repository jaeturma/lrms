import { Form, Head, Link } from '@inertiajs/react';
import { LogIn, ShieldCheck } from 'lucide-react';
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

            <div className="relative overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-lg sm:p-8">
                <div className="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary via-amber-400 to-primary" />

                <div className="mb-6 space-y-3">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold tracking-wide text-primary">
                        <ShieldCheck className="size-3.5" />
                        ADMIN PORTAL
                    </span>
                    <div>
                        <h1 className="text-xl font-semibold text-foreground">Admin Login</h1>
                        <p className="text-sm text-muted-foreground">Access LRMS dashboard and reports.</p>
                    </div>
                </div>

                <Form action="/app/admin/login" method="post" className="space-y-4">
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
                                <div className="flex items-center gap-2">
                                    <Checkbox id="remember" name="remember" />
                                    <Label htmlFor="remember" className="font-normal text-muted-foreground">
                                        Remember me
                                    </Label>
                                </div>
                                <Link href="/forgot-password" className="text-sm text-primary underline-offset-4 hover:underline">
                                    Forgot Password?
                                </Link>
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                <LogIn className="size-4" />
                                {processing ? 'Signing in...' : 'Log In as Admin'}
                            </Button>
                        </>
                    )}
                </Form>

                <div className="mt-6 flex items-center justify-center border-t border-border pt-4 text-sm text-muted-foreground">
                    <Link href="/login" className="text-primary underline-offset-4 hover:underline">
                        Back to School Login
                    </Link>
                </div>
            </div>
        </>
    );
}

AdminLoginPage.layout = {
    title: 'Admin Login',
    description: 'Login with admin credentials to manage LRMS',
};
