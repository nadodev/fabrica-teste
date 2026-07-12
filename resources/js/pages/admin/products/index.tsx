import { Head, Link, router } from "@inertiajs/react";
import { Archive, Pencil, Plus } from "lucide-react";
import { AdminLayout } from "@/modules/admin/ui/admin-layout";
import type { CatalogProduct } from "@/modules/catalog/domain/product";
import { formatMoney } from "@/modules/catalog/domain/product";

export default function AdminProducts({ products }: { products: CatalogProduct[] }) {
  const archiveProduct = (product: CatalogProduct) => {
    if (window.confirm(`Arquivar ${product.name}? O produto deixará de aparecer na loja.`)) {
      router.delete(`/admin/produtos/${product.id}`, { headers: { "Idempotency-Key": crypto.randomUUID() }, preserveScroll: true });
    }
  };

  return (
    <AdminLayout title="Produtos">
      <Head title="Produtos do painel" />
      <div className="mb-5 flex items-center justify-between"><p className="text-sm text-text-muted">{products.length} produtos cadastrados</p><Link href="/admin/produtos/novo" className="inline-flex items-center gap-2 rounded-lg bg-yellow px-4 py-2.5 text-sm font-black text-navy"><Plus className="h-4 w-4" /> Cadastrar produto</Link></div>
      <div className="overflow-hidden rounded-xl border border-border bg-white">
        {products.length === 0 ? <div className="p-12 text-center text-text-muted">Nenhum produto cadastrado.</div> : (
          <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead className="bg-bg-soft text-xs uppercase text-text-muted"><tr><th className="px-5 py-3">Produto</th><th className="px-5 py-3">SKU</th><th className="px-5 py-3">Preço</th><th className="px-5 py-3">Status</th><th className="px-5 py-3 text-right">Ações</th></tr></thead><tbody className="divide-y divide-border">{products.map((product) => <tr key={product.id} className={product.status === "archived" ? "opacity-60" : ""}><td className="px-5 py-4 font-bold text-navy">{product.name}</td><td className="px-5 py-4 text-text-muted">{product.sku}</td><td className="px-5 py-4">{formatMoney(product.priceAmount, product.priceCurrency)}</td><td className="px-5 py-4"><span className={`rounded-full px-2.5 py-1 text-xs font-bold ${product.status === "active" ? "bg-green-100 text-green-800" : "bg-slate-100 text-slate-700"}`}>{product.status === "active" ? "Ativo" : product.status === "archived" ? "Arquivado" : "Rascunho"}</span></td><td className="px-5 py-4"><div className="flex justify-end gap-1"><Link href={`/admin/produtos/${product.id}/editar`} className="rounded-md p-2 text-navy hover:bg-bg-soft" aria-label={`Editar ${product.name}`}><Pencil className="h-4 w-4" /></Link>{product.status !== "archived" && <button onClick={() => archiveProduct(product)} className="rounded-md p-2 text-text-muted hover:bg-red-50 hover:text-red-700" aria-label={`Arquivar ${product.name}`}><Archive className="h-4 w-4" /></button>}</div></td></tr>)}</tbody></table></div>
        )}
      </div>
    </AdminLayout>
  );
}
