import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import type { FormEvent, ReactNode } from "react";
import { createIdempotencyKey } from "@/lib/idempotency-key";
import { AdminLayout } from "@/modules/admin/ui/admin-layout";

type Item = Record<string, string | number | boolean | null> & { id?: string; image_url?: string | null };

const fieldsByType: Record<string, { name: string; label: string; textarea?: boolean }[]> = {
  categorias: [
    { name: "name", label: "Nome" },
    { name: "description", label: "Descricao" },
    { name: "link_url", label: "Link" },
    { name: "sort_order", label: "Ordem" },
  ],
  banners: [
    { name: "eyebrow", label: "Etiqueta" },
    { name: "title", label: "Titulo" },
    { name: "subtitle", label: "Subtitulo", textarea: true },
    { name: "button_label", label: "Texto do botao" },
    { name: "link_url", label: "Link" },
    { name: "sort_order", label: "Ordem" },
  ],
  lojas: [
    { name: "type", label: "Tipo" },
    { name: "city", label: "Cidade/Unidade" },
    { name: "address", label: "Endereco" },
    { name: "phone", label: "Telefone" },
    { name: "hours", label: "Horario" },
    { name: "map_url", label: "Link do mapa" },
    { name: "sort_order", label: "Ordem" },
  ],
  historia: [
    { name: "eyebrow", label: "Etiqueta" },
    { name: "title", label: "Titulo" },
    { name: "body", label: "Texto", textarea: true },
    { name: "mission", label: "Missao" },
    { name: "vision", label: "Visao" },
    { name: "values", label: "Valores" },
  ],
};

export default function ContentForm({ type, title, item, hasImage }: { type: string; title: string; item: Item | null; hasImage: boolean }) {
  const form = useForm<Record<string, string | number | boolean | File | null>>({
    ...(Object.fromEntries((fieldsByType[type] ?? []).map((field) => [field.name, String(item?.[field.name] ?? "")])) as Record<string, string>),
    is_active: item?.is_active ?? true,
    image: null,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post(item?.id ? `/admin/conteudo/${type}/${item.id}` : `/admin/conteudo/${type}`, {
      forceFormData: true,
      headers: { "Idempotency-Key": createIdempotencyKey() },
    });
  };

  return (
    <AdminLayout title={title}>
      <Head title={title} />
      <Link href={`/admin/conteudo/${type}`} className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline">
        <ArrowLeft className="h-4 w-4" /> Voltar
      </Link>
      <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-border bg-white p-6">
        <div className="grid gap-5 sm:grid-cols-2">
          {(fieldsByType[type] ?? []).map((field) => (
            <Field key={field.name} label={field.label} error={form.errors[field.name]}>
              {field.textarea ? (
                <textarea rows={5} className="input" value={String(form.data[field.name] ?? "")} onChange={(event) => form.setData(field.name, event.target.value)} />
              ) : (
                <input className="input" value={String(form.data[field.name] ?? "")} onChange={(event) => form.setData(field.name, event.target.value)} />
              )}
            </Field>
          ))}
        </div>
        {hasImage && (
          <Field label="Imagem" error={form.errors.image}>
            {item?.image_url && <img src={item.image_url} alt="" className="mb-3 h-32 w-56 rounded-lg object-cover" />}
            <input type="file" accept="image/jpeg,image/png,image/webp" className="input" onChange={(event) => form.setData("image", event.target.files?.[0] ?? null)} />
          </Field>
        )}
        <label className="flex items-center gap-2 text-sm font-bold text-navy">
          <input type="checkbox" checked={Boolean(form.data.is_active)} onChange={(event) => form.setData("is_active", event.target.checked)} /> Ativo
        </label>
        <div className="flex justify-end border-t border-border pt-5">
          <button disabled={form.processing} className="inline-flex items-center gap-2 rounded-lg bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60">
            <Save className="h-4 w-4" /> {form.processing ? "Salvando..." : "Salvar"}
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
