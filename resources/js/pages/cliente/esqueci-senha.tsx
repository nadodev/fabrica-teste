import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import type { FormEvent } from 'react';

export default function ForgotPassword() {
    const form = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/esqueci-senha');
    };

    return (
        <main className="mx-auto grid min-h-[70vh] max-w-7xl place-items-center px-4 py-12">
            <Head title="Recuperar senha" />
            <section className="w-full max-w-md rounded-2xl border border-border bg-white p-7 shadow-[var(--shadow-card)]">
                <div className="grid h-12 w-12 place-items-center rounded-xl bg-yellow text-navy">
                    <KeyRound className="h-6 w-6" />
                </div>
                <h1 className="mt-5 font-display text-2xl font-black text-navy">
                    Recuperar senha
                </h1>
                <p className="mt-1 text-sm text-text-muted">
                    Informe seu e-mail. Se houver uma conta cadastrada,
                    enviaremos um link seguro.
                </p>
                <form onSubmit={submit} className="mt-6 space-y-4">
                    <label className="block text-sm font-semibold text-navy">
                        E-mail
                        <input
                            type="email"
                            autoComplete="email"
                            required
                            autoFocus
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            className="mt-1 w-full rounded-lg border border-border px-3 py-2.5 outline-none focus:border-navy"
                        />
                        {form.errors.email && (
                            <span className="mt-1 block text-xs text-red-700">
                                {form.errors.email}
                            </span>
                        )}
                    </label>
                    <button
                        disabled={form.processing}
                        className="w-full rounded-lg bg-navy py-3 font-bold text-white disabled:opacity-60"
                    >
                        {form.processing ? 'Enviando...' : 'Enviar link'}
                    </button>
                </form>
                <Link
                    href="/entrar"
                    className="mt-5 block text-center text-sm font-semibold text-navy hover:underline"
                >
                    Voltar para entrar
                </Link>
            </section>
        </main>
    );
}
