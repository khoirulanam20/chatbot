import { useEffect } from 'react';
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

    useEffect(() => {
        document.body.classList.add('theme-light');

        return () => {
            document.body.classList.remove('theme-light');
        };
    }, []);

    return (
        <>
            <Head title="Login" />
            <div className="admin-theme-light admin-auth-shell flex min-h-screen items-center justify-center bg-canvas p-4">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary font-bold text-2xl text-on-primary shadow-lg shadow-primary/20">
                            AI
                        </div>
                        <p className="mb-2 text-[11px] font-semibold tracking-[0.24em] text-primary uppercase">
                            Admin Workspace
                        </p>
                        <h1 className="font-display text-2xl font-semibold text-ink">AI CS Chatbot</h1>
                        <p className="mt-1 text-sm text-muted">Masuk ke dashboard admin</p>
                    </div>
                    <div className="rounded-[28px] border border-hairline bg-white/90 p-8 shadow-[0_24px_60px_rgba(15,23,42,0.10)] backdrop-blur">
                        {errors.email && (
                            <div className="mb-4 rounded-lg border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
                                {errors.email}
                            </div>
                        )}
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                post('/login');
                            }}
                            className="space-y-5"
                        >
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
                                    className="rounded border-hairline text-primary focus:ring-primary"
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
