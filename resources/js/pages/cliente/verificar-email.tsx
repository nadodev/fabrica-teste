import { Head, router, useForm } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';

type Props = { email: string };

export default function VerifyEmail({ email }: Props) {
    const resend = useForm({ email: '' });

    return (
        <main className="mx-auto grid min-h-[70vh] max-w-7xl place-items-center px-4 py-12">
            <Head title="Confirmar e-mail" />
            <section className="w-full max-w-md rounded-2xl border border-border bg-white p-7 shadow-[var(--shadow-card)]">
                <div className="grid h-12 w-12 place-items-center rounded-xl bg-yellow text-navy">
                    <MailCheck className="h-6 w-6" />
                </div>
                <h1 className="mt-5 font-display text-2xl font-black text-navy">
                    Confirme seu e-mail
                </h1>
                <p className="mt-2 text-sm leading-6 text-text-muted">
                    Enviamos um link para <strong>{email}</strong>. A
                    confirmação protege seus pedidos, endereços e dados
                    pessoais.
                </p>
                <button
                    type="button"
                    disabled={resend.processing}
                    onClick={() => resend.post('/email/verificacao')}
                    className="mt-6 w-full rounded-lg bg-navy py-3 font-bold text-white disabled:opacity-60"
                >
                    {resend.processing ? 'Enviando...' : 'Reenviar confirmação'}
                </button>
                {resend.errors.email && (
                    <p role="alert" className="mt-2 text-sm text-red-700">
                        {resend.errors.email}
                    </p>
                )}
                <button
                    type="button"
                    onClick={() => router.post('/sair')}
                    className="mt-4 w-full text-sm font-semibold text-navy hover:underline"
                >
                    Sair e usar outra conta
                </button>
            </section>
        </main>
    );
}
