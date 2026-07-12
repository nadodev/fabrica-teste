import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import type { FormEvent, ReactNode } from "react";
import { createIdempotencyKey } from "@/lib/idempotency-key";
import { AdminLayout } from "@/modules/admin/ui/admin-layout";

type Coupon = {
  id: string;
  code: string;
  description: string;
  discount_type: "percent" | "fixed";
  discount_value: number;
  starts_at: string | null;
  ends_at: string | null;
  is_active: boolean;
};

const formatDate = (value: string | null) => value ? value.slice(0, 16) : "";

export default function CouponForm({ coupon }: { coupon: Coupon | null }) {
  const form = useForm({
    code: coupon?.code ?? "",
    description: coupon?.description ?? "",
    discount_type: coupon?.discount_type ?? "percent",
    discount_value: coupon?.discount_type === "fixed" ? coupon.discount_value / 100 : coupon?.discount_value ?? 10,
    starts_at: formatDate(coupon?.starts_at ?? null),
    ends_at: formatDate(coupon?.ends_at ?? null),
    is_active: coupon?.is_active ?? true,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => ({
      ...data,
      discount_value: data.discount_type === "fixed" ? Math.round(Number(data.discount_value) * 100) : Number(data.discount_value),
    }));
    form.post(coupon ? `/admin/cupons/${coupon.id}` : "/admin/cupons", {
      headers: { "Idempotency-Key": createIdempotencyKey() },
    });
  };

  return (
    <AdminLayout title={coupon ? "Editar cupom" : "Novo cupom"}>
      <Head title={coupon ? "Editar cupom" : "Novo cupom"} />
      <Link href="/admin/cupons" className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline">
        <ArrowLeft className="h-4 w-4" /> Voltar
      </Link>
      <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-border bg-white p-6">
        <div className="grid gap-5 sm:grid-cols-2">
          <Field label="Codigo" error={form.errors.code}>
            <input className="input uppercase" value={form.data.code} onChange={(event) => form.setData("code", event.target.value)} placeholder="PROMO10" />
          </Field>
          <Field label="Tipo de desconto" error={form.errors.discount_type}>
            <select className="input" value={form.data.discount_type} onChange={(event) => form.setData("discount_type", event.target.value as "percent" | "fixed")}>
              <option value="percent">Porcentagem</option>
              <option value="fixed">Valor fixo</option>
            </select>
          </Field>
          <Field label={form.data.discount_type === "percent" ? "Porcentagem" : "Valor em R$"} error={form.errors.discount_value}>
            <input type="number" min="1" step={form.data.discount_type === "fixed" ? "0.01" : "1"} className="input" value={form.data.discount_value} onChange={(event) => form.setData("discount_value", Number(event.target.value))} />
          </Field>
          <Field label="Descricao" error={form.errors.description}>
            <input className="input" value={form.data.description} onChange={(event) => form.setData("description", event.target.value)} />
          </Field>
          <Field label="Inicio" error={form.errors.starts_at}>
            <input type="datetime-local" className="input" value={form.data.starts_at} onChange={(event) => form.setData("starts_at", event.target.value)} />
          </Field>
          <Field label="Fim" error={form.errors.ends_at}>
            <input type="datetime-local" className="input" value={form.data.ends_at} onChange={(event) => form.setData("ends_at", event.target.value)} />
          </Field>
        </div>
        <label className="flex items-center gap-2 text-sm font-bold text-navy">
          <input type="checkbox" checked={Boolean(form.data.is_active)} onChange={(event) => form.setData("is_active", event.target.checked)} /> Ativo
        </label>
        <div className="flex justify-end border-t border-border pt-5">
          <button disabled={form.processing} className="inline-flex items-center gap-2 rounded-lg bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60">
            <Save className="h-4 w-4" /> {form.processing ? "Salvando..." : "Salvar cupom"}
          </button>
        </div>
      </form>
    </AdminLayout>
  );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
  return (
    <label className="block text-sm font-bold text-navy">
      {label}
      <div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:outline-none focus-within:[&_.input]:border-navy">{children}</div>
      {error && <span className="mt-1 block text-xs text-red-700">{error}</span>}
    </label>
  );
}
