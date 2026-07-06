import { Head, Link } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import http from '@/lib/http';

type FindSchoolResponse = {
    next_url: string;
    message?: string;
};

export default function HomePage() {
    const [schoolId, setSchoolId] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [loading, setLoading] = useState(false);

    const handleContinue = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setError(undefined);
        setLoading(true);

        try {
            const response = await http.post<FindSchoolResponse>('/school/find', {
                school_id: schoolId,
            });

            if (response.data.message) {
                alert(response.data.message);
            }

            window.location.href = response.data.next_url;
        } catch {
            setError('Invalid School ID. Please verify and try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Learning Resources Monitoring System" />

            <main className="min-h-screen bg-gradient-to-b from-slate-100 via-slate-50 to-white px-4 py-10 md:px-8">
                <div className="mx-auto max-w-5xl rounded-3xl border border-slate-200 bg-white p-6 shadow-xl md:p-10">
                    <div className="mb-8 space-y-2 text-center">
                        <p className="text-xs font-semibold tracking-[0.3em] text-slate-500">
                            DEPARTMENT OF EDUCATION
                        </p>
                        <h1 className="text-2xl font-bold text-slate-900 md:text-4xl">
                            Learning Resources Monitoring System
                        </h1>
                        <p className="mx-auto max-w-2xl text-sm text-slate-600 md:text-base">
                            Enter your School ID to activate your account and report defective learning resources.
                        </p>
                    </div>

                    <form
                        onSubmit={handleContinue}
                        className="mx-auto grid max-w-xl gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5"
                    >
                        <label htmlFor="school_id" className="text-sm font-medium text-slate-700">
                            School ID
                        </label>
                        <Input
                            id="school_id"
                            name="school_id"
                            value={schoolId}
                            onChange={(event) => setSchoolId(event.target.value)}
                            placeholder="e.g. SID-10001"
                            required
                        />
                        <InputError message={error} />
                        <Button type="submit" disabled={loading}>
                            {loading ? 'Checking...' : 'Continue'}
                        </Button>
                    </form>

                    <div className="mt-8 text-center text-sm text-slate-600">
                        Administrator?{' '}
                        <Link href="/app/admin/login" className="font-semibold text-slate-900 underline">
                            Go to Admin Login
                        </Link>
                    </div>
                </div>
            </main>
        </>
    );
}
