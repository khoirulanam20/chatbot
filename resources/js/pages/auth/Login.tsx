import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login" />
            <div className="flex min-h-screen items-center justify-center bg-surface-soft p-4">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-xl bg-primary font-bold text-2xl text-on-primary">
                            AI
                        </div>
                        <h1 className="font-display text-2xl font-semibold text-ink">AI CS Chatbot</h1>
                        <p className="mt-1 text-sm text-muted">Masuk ke dashboard admin</p>
                    </div>
                    <div className="rounded-xl border border-hairline bg-canvas p-8 shadow-sm">
                        {errors.email && (
                            <div className="mb-4 rounded-lg border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
                                {errors.email}
                            </div>
                        )}
                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1.5"
                                    placeholder="admin@example.com"
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="mt-1.5"
                                    required
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded border-hairline"
                                />
                                <label htmlFor="remember" className="text-sm text-muted">
                                    Ingat saya
                                </label>
                            </div>
                            <Button type="submit" className="w-full" disabled={processing}>
                                Masuk
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
