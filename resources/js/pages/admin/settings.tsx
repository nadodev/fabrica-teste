import { Head, useForm } from "@inertiajs/react";
import { Palette, Save, Upload } from "lucide-react";
import type { FormEvent } from "react";
import { createIdempotencyKey } from "@/lib/idempotency-key";
import { AdminLayout } from "@/modules/admin/ui/admin-layout";

type Settings = {
  store_name: string;
  logo_url: string | null;
  primary_color: string;
  secondary_color: string;
};

export default function SettingsPage({ settings }: { settings: Settings }) {
  const form = useForm({
    storeName: settings.store_name ?? "Fabrica de Fardamentos",
    primaryColor: settings.primary_color ?? "#123a6b",
    secondaryColor: settings.secondary_color ?? "#f5c542",
    logo: null as File | null,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post("/admin/configuracoes", {
      forceFormData: true,
      headers: { "Idempotency-Key": createIdempotencyKey() },
    });
  };

  return (
    <AdminLayout title="Configuracoes">
      <Head title="Configuracoes da loja" />
      <form onSubmit={submit} className="max-w-3xl space-y-6 rounded-xl border border-border bg-white p-6 shadow-[var(--shadow-soft)]">
        <div>
          <h2 className="font-display text-xl font-black text-navy">Identidade da loja</h2>
          <p className="mt-1 text-sm text-text-muted">Altere a logo e as cores usadas no site.</p>
        </div>

        <label className="block text-sm font-bold text-navy">
          Nome da loja
          <input value={form.data.storeName} onChange={(event) => form.setData("storeName", event.target.value)} className="mt-1 w-full rounded-lg border border-border px-3 py-2.5 font-normal outline-none focus:border-navy" />
          {form.errors.storeName && <span className="mt-1 block text-xs text-red-700">{form.errors.storeName}</span>}
        </label>

        <label className="block text-sm font-bold text-navy">
          Cor principal
          <div className="mt-1 flex gap-3">
            <input type="color" value={form.data.primaryColor} onChange={(event) => form.setData("primaryColor", event.target.value)} className="h-12 w-16 rounded-lg border border-border bg-white p-1" />
            <input value={form.data.primaryColor} onChange={(event) => form.setData("primaryColor", event.target.value)} className="w-full rounded-lg border border-border px-3 py-2.5 font-normal outline-none focus:border-navy" placeholder="#123a6b" />
          </div>
          {form.errors.primaryColor && <span className="mt-1 block text-xs text-red-700">{form.errors.primaryColor}</span>}
        </label>

        <label className="block text-sm font-bold text-navy">
          Cor secundaria
          <div className="mt-1 flex gap-3">
            <input type="color" value={form.data.secondaryColor} onChange={(event) => form.setData("secondaryColor", event.target.value)} className="h-12 w-16 rounded-lg border border-border bg-white p-1" />
            <input value={form.data.secondaryColor} onChange={(event) => form.setData("secondaryColor", event.target.value)} className="w-full rounded-lg border border-border px-3 py-2.5 font-normal outline-none focus:border-navy" placeholder="#f5c542" />
          </div>
          {form.errors.secondaryColor && <span className="mt-1 block text-xs text-red-700">{form.errors.secondaryColor}</span>}
        </label>

        <div className="rounded-xl bg-bg-soft p-4">
          <div className="mb-3 text-sm font-bold text-navy">Previa</div>
          <div className="flex flex-wrap items-center gap-3">
            <div className="grid h-14 w-14 place-items-center rounded-lg text-white" style={{ backgroundColor: form.data.primaryColor }}>
              <Palette className="h-6 w-6" />
            </div>
            <button type="button" className="rounded-md px-5 py-3 font-black text-white" style={{ backgroundColor: form.data.primaryColor }}>
              Botao principal
            </button>
            <button type="button" className="rounded-md px-5 py-3 font-black" style={{ backgroundColor: form.data.secondaryColor, color: form.data.primaryColor }}>
              Botao destaque
            </button>
            <span className="rounded-full px-3 py-1 text-xs font-black" style={{ backgroundColor: form.data.secondaryColor, color: form.data.primaryColor }}>
              Destaque
            </span>
          </div>
        </div>

        <label className="block text-sm font-bold text-navy">
          Logo
          {settings.logo_url && <img src={settings.logo_url} alt={settings.store_name} className="mt-2 h-20 w-auto rounded-lg border border-border bg-white p-2" />}
          <span className="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-border px-4 py-2.5 text-sm font-black text-navy hover:bg-bg-soft">
            <Upload className="h-4 w-4" /> Enviar nova logo
            <input type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml" onChange={(event) => form.setData("logo", event.target.files?.[0] ?? null)} className="sr-only" />
          </span>
          {form.data.logo && <span className="ml-3 text-sm text-text-muted">{form.data.logo.name}</span>}
          {form.errors.logo && <span className="mt-1 block text-xs text-red-700">{form.errors.logo}</span>}
        </label>

        <div className="flex justify-end border-t border-border pt-5">
          <button disabled={form.processing} className="inline-flex items-center gap-2 rounded-lg bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60">
            <Save className="h-4 w-4" /> {form.processing ? "Salvando..." : "Salvar configuracoes"}
          </button>
        </div>
      </form>
    </AdminLayout>
  );
}
