import { Head, Link, useForm } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';

export default function CustomerLogin() {
    const form = useForm({ email: '', password: '', remember: true });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/entrar', { onFinish: () => form.reset('password') });
    };

    return (
        <main className="mx-auto grid min-h-[70vh] max-w-7xl place-items-center px-4 py-12">
            <Head title="Entrar" />
            <section className="w-full max-w-md rounded-2xl border border-border bg-white p-7 shadow-[var(--shadow-card)]">
                <div className="grid h-12 w-12 place-items-center rounded-xl bg-yellow text-navy">
                    <LogIn className="h-6 w-6" />
                </div>
                <h1 className="mt-5 font-display text-2xl font-black text-navy">
                    Entrar na conta
                </h1>
                <p className="mt-1 text-sm text-text-muted">
                    Acompanhe seus pedidos e orcamentos.
                </p>
                <form onSubmit={submit} className="mt-6 space-y-4">
                    <Field label="E-mail" error={form.errors.email}>
                        <input
                            type="email"
                            autoComplete="username"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            className="input"
                        />
                    </Field>
                    <Field label="Senha" error={form.errors.password}>
                        <input
                            type="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            className="input"
                        />
                    </Field>
                    <div className="text-right">
                        <Link
                            href="/esqueci-senha"
                            className="text-sm font-semibold text-navy hover:underline"
                        >
                            Esqueci minha senha
                        </Link>
                    </div>
                    <label className="flex items-center gap-2 text-sm text-text-muted">
                        <input
                            type="checkbox"
                            checked={form.data.remember}
                            onChange={(event) =>
                                form.setData('remember', event.target.checked)
                            }
                        />{' '}
                        Manter conectado
                    </label>
                    <button
                        disabled={form.processing}
                        className="w-full rounded-lg bg-navy py-3 font-bold text-white disabled:opacity-60"
                    >
                        {form.processing ? 'Entrando...' : 'Entrar'}
                    </button>
                </form>
                <Link
                    href="/cadastro"
                    className="mt-5 block text-center text-sm font-semibold text-navy hover:underline"
                >
                    Criar conta
                </Link>
            </section>
        </main>
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
        <label className="block text-sm font-semibold text-navy">
            {label}
            <div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:outline-none focus-within:[&_.input]:border-navy">
                {children}
            </div>
            {error && (
                <span className="mt-1 block text-xs text-red-700">{error}</span>
            )}
        </label>
    );
}
