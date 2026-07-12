import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import type { FormEvent } from "react";
import { AdminLayout } from "@/modules/admin/ui/admin-layout";

export default function CreateProduct() {
  const form = useForm({ sku: "", name: "", description: "", price: "", status: "draft", imageUrl: "" });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post("/admin/produtos", { headers: { "Idempotency-Key": crypto.randomUUID() } });
  };

  return (
    <AdminLayout title="Cadastrar produto">
      <Head title="Cadastrar produto" />
      <Link href="/admin/produtos" className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline"><ArrowLeft className="h-4 w-4" /> Voltar para produtos</Link>
      <form onSubmit={submit} className="max-w-3xl space-y-6 rounded-xl border border-border bg-white p-6">
        <div className="grid gap-5 sm:grid-cols-2">
          <Field label="Nome do produto" error={form.errors.name}><input value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} className="input" placeholder="Camisa Polo Empresarial" /></Field>
          <Field label="SKU" error={form.errors.sku}><input value={form.data.sku} onChange={(event) => form.setData("sku", event.target.value.toUpperCase())} className="input" placeholder="POLO-001" /></Field>
        </div>
        <Field label="Descrição" error={form.errors.description}><textarea rows={5} value={form.data.description} onChange={(event) => form.setData("description", event.target.value)} className="input" placeholder="Características, material e acabamento..." /></Field>
        <div className="grid gap-5 sm:grid-cols-2">
          <Field label="Preço em reais" error={form.errors.price}><input inputMode="decimal" value={form.data.price} onChange={(event) => form.setData("price", event.target.value)} className="input" placeholder="79,90" /></Field>
          <Field label="Status" error={form.errors.status}><select value={form.data.status} onChange={(event) => form.setData("status", event.target.value)} className="input"><option value="draft">Rascunho</option><option value="active">Ativo no catálogo</option></select></Field>
        </div>
        <Field label="URL da imagem (temporário)" error={form.errors.imageUrl}><input type="url" value={form.data.imageUrl} onChange={(event) => form.setData("imageUrl", event.target.value)} className="input" placeholder="https://..." /><span className="mt-1 block text-xs font-normal text-text-muted">Upload de imagens será a próxima evolução do painel.</span></Field>
        <div className="flex justify-end border-t border-border pt-5"><button disabled={form.processing} className="inline-flex items-center gap-2 rounded-lg bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60"><Save className="h-4 w-4" /> {form.processing ? "Salvando..." : "Salvar produto"}</button></div>
      </form>
    </AdminLayout>
  );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
  return <label className="block text-sm font-bold text-navy">{label}<div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:outline-none focus-within:[&_.input]:border-navy">{children}</div>{error && <span className="mt-1 block text-xs text-red-700">{error}</span>}</label>;
}
