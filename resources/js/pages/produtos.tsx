import { Link } from "@inertiajs/react";
import { LayoutGrid, List, ChevronRight } from "lucide-react";
import { useState } from "react";
import type { ReactNode } from "react";
import { ProductCard } from "@/components/product-card";
import type { CatalogProduct } from "@/modules/catalog/domain/product";

const segments = ["Empresarial", "Escolar", "Profissional", "Industrial"];
const types = ["Camisas", "Camisas Polo", "Calças", "Jalecos", "Aventais", "Conjuntos"];
const publics = ["Masculino", "Feminino", "Infantil", "Unissex"];
const sizes = ["PP", "P", "M", "G", "GG", "XGG"];

export default function ProductsPage({ products }: { products: CatalogProduct[] }) {
  const [activeSeg, setActiveSeg] = useState<string | null>(null);
  const [view, setView] = useState<"grid" | "list">("grid");

  const filtered = products;

  return (
    <div>
      {/* Header banner */}
      <div className="bg-navy text-white">
        <div className="mx-auto max-w-7xl px-4 py-10">
          <div className="flex items-center gap-2 text-xs text-white/70">
            <Link href="/" className="hover:text-yellow">Início</Link>
            <ChevronRight className="h-3 w-3" />
            <span className="text-yellow">Produtos</span>
          </div>
          <h1 className="mt-3 font-display text-4xl font-black md:text-5xl">Todos os fardamentos</h1>
          <p className="mt-3 max-w-2xl text-white/80">
            Encontre o uniforme ideal para sua empresa, escola ou equipe.
          </p>
        </div>
      </div>

      <div className="mx-auto grid max-w-7xl gap-6 px-4 py-6 md:py-10 lg:grid-cols-[260px_1fr] lg:gap-8">
        {/* Sidebar */}
        <aside className="hidden space-y-6 lg:block">
          <FilterGroup title="Segmento">
            {segments.map((s) => (
              <button
                key={s}
                onClick={() => setActiveSeg(activeSeg === s ? null : s)}
                className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition ${
                  activeSeg === s ? "bg-navy text-white font-semibold" : "hover:bg-bg-soft"
                }`}
              >
                {s}
              </button>
            ))}
          </FilterGroup>
          <FilterGroup title="Tipo de produto">
            {types.map((t) => (
              <Check key={t} label={t} />
            ))}
          </FilterGroup>
          <FilterGroup title="Público">
            {publics.map((p) => <Check key={p} label={p} />)}
          </FilterGroup>
          <FilterGroup title="Tamanho">
            <div className="flex flex-wrap gap-2">
              {sizes.map((s) => (
                <button key={s} className="rounded-md border border-border px-3 py-1 text-xs font-semibold hover:border-navy hover:text-navy">{s}</button>
              ))}
            </div>
          </FilterGroup>
          <FilterGroup title="Faixa de preço">
            <input type="range" min={0} max={500} defaultValue={250} className="w-full accent-navy" />
            <div className="flex justify-between text-xs text-text-muted"><span>R$ 0</span><span>R$ 500</span></div>
          </FilterGroup>
          <label className="flex items-center gap-2 text-sm">
            <input type="checkbox" defaultChecked className="h-4 w-4 accent-navy" /> Produto personalizável
          </label>
        </aside>

        {/* Grid */}
        <div>
          <div className="mb-4 flex gap-2 overflow-x-auto pb-1 lg:hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <button
              onClick={() => setActiveSeg(null)}
              className={`shrink-0 rounded-full border px-4 py-2 text-xs font-black ${activeSeg === null ? "border-navy bg-navy text-white" : "border-border bg-white text-navy"}`}
            >
              Todos
            </button>
            {segments.map((s) => (
              <button
                key={s}
                onClick={() => setActiveSeg(activeSeg === s ? null : s)}
                className={`shrink-0 rounded-full border px-4 py-2 text-xs font-black ${activeSeg === s ? "border-navy bg-navy text-white" : "border-border bg-white text-navy"}`}
              >
                {s}
              </button>
            ))}
          </div>
          <div className="mb-5 flex items-center justify-between gap-3">
            <div className="text-sm text-text-muted"><span className="font-semibold text-text-dark">{filtered.length}</span> produtos encontrados</div>
            <div className="flex shrink-0 items-center gap-2">
              <select className="max-w-36 rounded-md border border-border bg-white px-2 py-2 text-xs sm:max-w-none sm:px-3 sm:text-sm">
                <option>Relevância</option>
                <option>Menor preço</option>
                <option>Maior preço</option>
                <option>Lançamentos</option>
              </select>
              <div className="flex overflow-hidden rounded-md border border-border">
                <button aria-label="Grade" onClick={() => setView("grid")} className={`p-2 ${view === "grid" ? "bg-navy text-white" : "bg-white text-navy"}`}><LayoutGrid className="h-4 w-4" /></button>
                <button aria-label="Lista" onClick={() => setView("list")} className={`p-2 ${view === "list" ? "bg-navy text-white" : "bg-white text-navy"}`}><List className="h-4 w-4" /></button>
              </div>
            </div>
          </div>
          <div className={view === "grid" ? "grid grid-cols-2 gap-3 sm:gap-6 xl:grid-cols-3" : "space-y-4"}>
            {filtered.map((p) => <ProductCard key={p.id} p={p} />)}
          </div>
        </div>
      </div>
    </div>
  );
}

function FilterGroup({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="rounded-xl border border-border bg-white p-4">
      <div className="mb-3 font-display text-sm font-bold uppercase tracking-wider text-navy">{title}</div>
      <div className="space-y-1">{children}</div>
    </div>
  );
}

function Check({ label }: { label: string }) {
  return (
    <label className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-bg-soft">
      <input type="checkbox" className="h-4 w-4 accent-navy" /> {label}
    </label>
  );
}
