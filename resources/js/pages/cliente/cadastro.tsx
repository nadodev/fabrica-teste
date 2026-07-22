import { Head, Link, useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';

export default function CustomerRegister() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/cadastro', {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <main className="mx-auto grid min-h-[70vh] max-w-7xl place-items-center px-4 py-12">
            <Head title="Criar conta" />
            <section className="w-full max-w-md rounded-2xl border border-border bg-white p-7 shadow-[var(--shadow-card)]">
                <div className="grid h-12 w-12 place-items-center rounded-xl bg-yellow text-navy">
                    <UserPlus className="h-6 w-6" />
                </div>
                <h1 className="mt-5 font-display text-2xl font-black text-navy">
                    Criar conta
                </h1>
                <p className="mt-1 text-sm text-text-muted">
                    Use o mesmo e-mail dos pedidos para acompanhar tudo.
                </p>
                <form onSubmit={submit} className="mt-6 space-y-4">
                    <Field label="Nome" error={form.errors.name}>
                        <input
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            className="input"
                        />
                    </Field>
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
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            className="input"
                        />
                        <span className="mt-1 block text-xs font-normal text-text-muted">
                            Use pelo menos 12 caracteres, com maiúscula,
                            minúscula e número.
                        </span>
                    </Field>
                    <Field label="Confirmar senha">
                        <input
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password_confirmation}
                            onChange={(event) =>
                                form.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            className="input"
                        />
                    </Field>
                    <button
                        disabled={form.processing}
                        className="w-full rounded-lg bg-navy py-3 font-bold text-white disabled:opacity-60"
                    >
                        {form.processing ? 'Criando...' : 'Criar conta'}
                    </button>
                </form>
                <Link
                    href="/entrar"
                    className="mt-5 block text-center text-sm font-semibold text-navy hover:underline"
                >
                    Ja tenho conta
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
