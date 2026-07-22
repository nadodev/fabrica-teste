import { Head, Link, useForm } from '@inertiajs/react';
import { LockKeyhole } from 'lucide-react';
import type { FormEvent } from 'react';

type Props = { email: string; token: string };

export default function ResetPassword({ email, token }: Props) {
    const form = useForm({
        email,
        token,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/redefinir-senha', {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <main className="mx-auto grid min-h-[70vh] max-w-7xl place-items-center px-4 py-12">
            <Head title="Criar nova senha" />
            <section className="w-full max-w-md rounded-2xl border border-border bg-white p-7 shadow-[var(--shadow-card)]">
                <div className="grid h-12 w-12 place-items-center rounded-xl bg-yellow text-navy">
                    <LockKeyhole className="h-6 w-6" />
                </div>
                <h1 className="mt-5 font-display text-2xl font-black text-navy">
                    Criar nova senha
                </h1>
                <p className="mt-1 text-sm text-text-muted">
                    Escolha uma senha nova e diferente da utilizada em outros
                    sites.
                </p>
                <form onSubmit={submit} className="mt-6 space-y-4">
                    <PasswordField
                        label="Nova senha"
                        value={form.data.password}
                        error={form.errors.password}
                        autoComplete="new-password"
                        onChange={(value) => form.setData('password', value)}
                    />
                    <p className="text-xs text-text-muted">
                        Use pelo menos 12 caracteres, com maiúscula, minúscula e
                        número.
                    </p>
                    <PasswordField
                        label="Confirmar nova senha"
                        value={form.data.password_confirmation}
                        error={form.errors.password_confirmation}
                        autoComplete="new-password"
                        onChange={(value) =>
                            form.setData('password_confirmation', value)
                        }
                    />
                    {form.errors.email && (
                        <p role="alert" className="text-sm text-red-700">
                            {form.errors.email}
                        </p>
                    )}
                    <button
                        disabled={form.processing}
                        className="w-full rounded-lg bg-navy py-3 font-bold text-white disabled:opacity-60"
                    >
                        {form.processing ? 'Salvando...' : 'Salvar nova senha'}
                    </button>
                </form>
                <Link
                    href="/esqueci-senha"
                    className="mt-5 block text-center text-sm font-semibold text-navy hover:underline"
                >
                    Solicitar outro link
                </Link>
            </section>
        </main>
    );
}

function PasswordField({
    label,
    value,
    error,
    autoComplete,
    onChange,
}: {
    label: string;
    value: string;
    error?: string;
    autoComplete: string;
    onChange: (value: string) => void;
}) {
    return (
        <label className="block text-sm font-semibold text-navy">
            {label}
            <input
                type="password"
                required
                value={value}
                autoComplete={autoComplete}
                onChange={(event) => onChange(event.target.value)}
                className="mt-1 w-full rounded-lg border border-border px-3 py-2.5 outline-none focus:border-navy"
            />
            {error && (
                <span className="mt-1 block text-xs text-red-700">{error}</span>
            )}
        </label>
    );
}
