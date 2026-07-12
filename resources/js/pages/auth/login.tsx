import { Head, Link, useForm } from "@inertiajs/react";
import { LockKeyhole } from "lucide-react";
import type { FormEvent } from "react";

export default function AdminLogin() {
  const form = useForm({ email: "", password: "", remember: false });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post("/admin/login", { onFinish: () => form.reset("password") });
  };

  return (
    <main className="grid min-h-screen place-items-center bg-bg-soft px-4">
      <Head title="Acesso administrativo" />
      <div className="w-full max-w-md rounded-2xl border border-border bg-white p-7 shadow-[var(--shadow-card)]">
        <div className="grid h-12 w-12 place-items-center rounded-xl bg-yellow text-navy"><LockKeyhole className="h-6 w-6" /></div>
        <h1 className="mt-5 font-display text-2xl font-black text-navy">Painel administrativo</h1>
        <p className="mt-1 text-sm text-text-muted">Entre com uma conta autorizada para gerenciar a loja.</p>

        <form onSubmit={submit} className="mt-6 space-y-4">
          <Field label="E-mail" error={form.errors.email}>
            <input type="email" autoComplete="username" value={form.data.email} onChange={(event) => form.setData("email", event.target.value)} className="w-full rounded-lg border border-border px-3 py-2.5 outline-none focus:border-navy" />
          </Field>
          <Field label="Senha" error={form.errors.password}>
            <input type="password" autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData("password", event.target.value)} className="w-full rounded-lg border border-border px-3 py-2.5 outline-none focus:border-navy" />
          </Field>
          <label className="flex items-center gap-2 text-sm text-text-muted"><input type="checkbox" checked={form.data.remember} onChange={(event) => form.setData("remember", event.target.checked)} /> Manter conectado</label>
          <button disabled={form.processing} className="w-full rounded-lg bg-navy py-3 font-bold text-white disabled:opacity-60">{form.processing ? "Entrando..." : "Entrar"}</button>
        </form>
        <Link href="/" className="mt-5 block text-center text-sm font-semibold text-navy hover:underline">Voltar para a loja</Link>
      </div>
    </main>
  );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
  return <label className="block text-sm font-semibold text-navy">{label}<div className="mt-1">{children}</div>{error && <span className="mt-1 block text-xs text-red-700">{error}</span>}</label>;
}
