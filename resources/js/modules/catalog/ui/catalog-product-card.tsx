import { Link } from "@inertiajs/react";
import { ShoppingCart } from "lucide-react";
import fallbackImage from "@/assets/prod-polo.jpg";
import type { CatalogProduct } from "@/modules/catalog/domain/product";
import { formatMoney } from "@/modules/catalog/domain/product";

export function CatalogProductCard({ product }: { product: CatalogProduct }) {
  return (
    <article className="group flex flex-col overflow-hidden rounded-xl border border-border bg-white transition hover:-translate-y-1 hover:shadow-[var(--shadow-card)]">
      <div className="aspect-[4/5] overflow-hidden bg-bg-soft sm:aspect-square">
        <img src={product.imageUrl ?? fallbackImage} alt={product.name} loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
      </div>
      <div className="flex flex-1 flex-col p-4">
        <div className="text-xs font-semibold uppercase tracking-wider text-text-muted">{product.sku}</div>
        <h2 className="mt-1 font-display text-base font-bold text-navy">{product.name}</h2>
        <p className="mt-1 line-clamp-2 text-sm leading-5 text-text-muted">{product.description}</p>
        <div className="mt-auto pt-4 font-display text-xl font-black text-navy">{formatMoney(product.priceAmount, product.priceCurrency)}</div>
        <Link href={`/produtos/${product.id}`} className="mt-3 inline-flex justify-center rounded-md bg-navy px-3 py-2 text-xs font-semibold text-white">Ver detalhes</Link>
        <Link href="/carrinho" className="mt-2 inline-flex items-center justify-center gap-2 rounded-md bg-yellow px-3 py-2 text-xs font-black text-navy">
          <ShoppingCart className="h-3.5 w-3.5" /> Comprar
        </Link>
      </div>
    </article>
  );
}
