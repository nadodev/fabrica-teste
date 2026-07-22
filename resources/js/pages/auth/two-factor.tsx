import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound, MailCheck } from 'lucide-react';
import type { FormEvent } from 'react';

export default function AdminTwoFactor({ expiresAt }: { expiresAt?: string }) {
    const form = useForm({ code: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/verificar-acesso', {
            preserveScroll: true,
            onFinish: () => form.reset('code'),
        });
    };

    return (
        <main className="grid min-h-screen place-items-center bg-bg-soft px-4">
            <Head title="Confirmar acesso administrativo" />
            <div className="w-full max-w-md rounded-2xl border border-border bg-white p-7 shadow-[var(--shadow-card)]">
                <div className="grid h-12 w-12 place-items-center rounded-xl bg-yellow text-navy">
                    <MailCheck className="h-6 w-6" />
                </div>
                <h1 className="mt-5 font-display text-2xl font-black text-navy">
                    Confirme seu acesso
                </h1>
                <p className="mt-1 text-sm text-text-muted">
                    Enviamos um código de seis números para o e-mail da conta.
                    Ele pode ser utilizado apenas uma vez.
                </p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <label className="block text-sm font-semibold text-navy">
                        Código de acesso
                        <div className="relative mt-1">
                            <KeyRound className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-text-muted" />
                            <input
                                type="text"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                required
                                autoFocus
                                value={form.data.code}
                                onChange={(event) =>
                                    form.setData(
                                        'code',
                                        event.target.value
                                            .replace(/\D/g, '')
                                            .slice(0, 6),
                                    )
                                }
                                aria-invalid={Boolean(form.errors.code)}
                                className="w-full rounded-lg border border-border py-3 pr-3 pl-10 text-center font-mono text-xl tracking-[0.35em] outline-none focus:border-navy"
                            />
                        </div>
                        {form.errors.code && (
                            <span className="mt-1 block text-xs text-red-700">
                                {form.errors.code}
                            </span>
                        )}
                    </label>

                    {expiresAt && (
                        <p className="text-xs text-text-muted">
                            Por segurança, o código expira em poucos minutos.
                        </p>
                    )}

                    <button
                        disabled={
                            form.processing || form.data.code.length !== 6
                        }
                        className="w-full rounded-lg bg-navy py-3 font-bold text-white disabled:opacity-60"
                    >
                        {form.processing
                            ? 'Verificando...'
                            : 'Confirmar acesso'}
                    </button>
                </form>

                <Link
                    href="/admin/login"
                    className="mt-5 block text-center text-sm font-semibold text-navy hover:underline"
                >
                    Voltar e entrar novamente
                </Link>
            </div>
        </main>
    );
}
