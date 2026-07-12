import { useForm } from "@inertiajs/react";
import { Save } from "lucide-react";
import type { FormEvent, ReactNode } from "react";
import type { CatalogProduct } from "@/modules/catalog/domain/product";

type ProductFormData = {
  sku: string;
  name: string;
  description: string;
  price: string;
  status: string;
  imageUrl: string;
  image: File | null;
  removeImage: boolean;
};

export function AdminProductForm({ product }: { product?: CatalogProduct }) {
  const form = useForm<ProductFormData>({
    sku: product?.sku ?? "",
    name: product?.name ?? "",
    description: product?.description ?? "",
    price: product ? (product.priceAmount / 100).toFixed(2).replace(".", ",") : "",
    status: product?.status === "active" ? "active" : "draft",
    imageUrl: product?.imageUrl?.startsWith("http") ? product.imageUrl : "",
    image: null,
    removeImage: false,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.transform((data) => product ? { ...data, _method: "put" } : data);
    form.post(
      product ? `/admin/produtos/${product.id}` : "/admin/produtos",
      { forceFormData: true, headers: { "Idempotency-Key": crypto.randomUUID() } },
    );
  };

  return (
    <form onSubmit={submit} className="max-w-3xl space-y-6 rounded-xl border border-border bg-white p-6">
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Nome do produto" error={form.errors.name}><input value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} className="input" placeholder="Camisa Polo Empresarial" /></Field>
        <Field label="SKU" error={form.errors.sku}><input disabled={Boolean(product)} value={form.data.sku} onChange={(event) => form.setData("sku", event.target.value.toUpperCase())} className="input disabled:bg-bg-soft" placeholder="POLO-001" /></Field>
      </div>
      <Field label="Descrição" error={form.errors.description}><textarea rows={5} value={form.data.description} onChange={(event) => form.setData("description", event.target.value)} className="input" placeholder="Características, material e acabamento..." /></Field>
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Preço em reais" error={form.errors.price}><input inputMode="decimal" value={form.data.price} onChange={(event) => form.setData("price", event.target.value)} className="input" placeholder="79,90" /></Field>
        <Field label="Status" error={form.errors.status}><select value={form.data.status} onChange={(event) => form.setData("status", event.target.value)} className="input"><option value="draft">Rascunho</option><option value="active">Ativo no catálogo</option></select></Field>
      </div>
      <Field label="Imagem do produto" error={form.errors.image}>
        <input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => form.setData("image", event.target.files?.[0] ?? null)} className="input" />
        <span className="mt-1 block text-xs font-normal text-text-muted">JPEG, PNG ou WebP, até 4 MB e 4000×4000 px.</span>
      </Field>
      <Field label="Ou URL externa" error={form.errors.imageUrl}><input type="url" value={form.data.imageUrl} onChange={(event) => form.setData("imageUrl", event.target.value)} className="input" placeholder="https://..." /></Field>
      {product?.imageUrl && <label className="flex items-center gap-2 text-sm text-text-muted"><input type="checkbox" checked={form.data.removeImage} onChange={(event) => form.setData("removeImage", event.target.checked)} /> Remover imagem atual</label>}
      <div className="flex justify-end border-t border-border pt-5"><button disabled={form.processing} className="inline-flex items-center gap-2 rounded-lg bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60"><Save className="h-4 w-4" /> {form.processing ? "Salvando..." : "Salvar produto"}</button></div>
    </form>
  );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
  return <label className="block text-sm font-bold text-navy">{label}<div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:outline-none focus-within:[&_.input]:border-navy">{children}</div>{error && <span className="mt-1 block text-xs text-red-700">{error}</span>}</label>;
}
