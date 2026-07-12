import { Link } from "@inertiajs/react";
import { ShoppingCart, Sparkles } from "lucide-react";
import type { Product } from "@/lib/store-data";
import { formatBRL } from "@/lib/store-data";

export function ProductCard({ p }: { p: Product }) {
  return (
    <div className="group flex flex-col overflow-hidden rounded-xl border border-border bg-white transition hover:-translate-y-1 hover:shadow-[var(--shadow-card)]">
      <div className="relative aspect-[4/5] overflow-hidden bg-bg-soft sm:aspect-square">
        <img src={p.image} alt={p.name} loading="lazy" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
        {p.personalizable && (
          <span className="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-yellow px-2 py-1 text-[10px] font-bold text-navy sm:left-3 sm:top-3 sm:px-2.5 sm:text-[11px]">
            <Sparkles className="h-3 w-3" /> Personalizável
          </span>
        )}
      </div>
      <div className="flex flex-1 flex-col p-3 sm:p-4">
        <div className="text-[11px] font-semibold uppercase tracking-wider text-text-muted">{p.segment}</div>
        <h3 className="mt-1 line-clamp-2 font-display text-sm font-bold leading-tight text-navy sm:text-base">{p.name}</h3>
        <p className="mt-1 line-clamp-2 text-xs leading-5 text-text-muted sm:text-sm">{p.description}</p>
        <div className="mt-3 flex items-center gap-1.5">
          {p.colors.slice(0, 4).map((c, i) => (
            <span key={i} className="h-4 w-4 rounded-full border border-border" style={{ background: c }} />
          ))}
        </div>
        <div className="mt-auto pt-4">
          <div>
            <div className="text-[11px] text-text-muted">a partir de</div>
            <div className="font-display text-lg font-black leading-none text-navy sm:text-xl">{formatBRL(p.price)}</div>
          </div>
          <Link href={`/produtos/${p.id}`} className="mt-3 inline-flex w-full items-center justify-center rounded-md bg-navy px-3 py-2 text-xs font-semibold text-white transition hover:bg-navy-deep">
            Ver detalhes
          </Link>
        </div>
        <Link href="/carrinho" className="mt-2 inline-flex items-center justify-center gap-2 rounded-md bg-yellow px-3 py-2 text-xs font-black text-navy transition hover:brightness-95">
          <ShoppingCart className="h-3.5 w-3.5" /> Comprar
        </Link>
      </div>
    </div>
  );
}
