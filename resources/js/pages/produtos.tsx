import { Link, router } from "@inertiajs/react";
import { ChevronRight, LayoutGrid, List, Search } from "lucide-react";
import type { FormEvent, ReactNode } from "react";
import { useMemo, useState } from "react";
import type { CatalogProduct } from "@/modules/catalog/domain/product";
import { CatalogProductCard } from "@/modules/catalog/ui/catalog-product-card";

export default function ProductsPage({ products = [] }: { products?: CatalogProduct[] }) {
  const safeProducts = Array.isArray(products) ? products : [];
  const initialSearch = typeof window === "undefined" ? "" : new URLSearchParams(window.location.search).get("busca") ?? "";
  const [activeCategory, setActiveCategory] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState(initialSearch);
  const [view, setView] = useState<"grid" | "list">("grid");

  const categories = useMemo(() => Array.from(new Set(safeProducts.map((product) => product.category))).filter(Boolean), [safeProducts]);
  const normalizedSearch = searchTerm.trim().toLowerCase();
  const filtered = safeProducts.filter((product) => {
    const matchesCategory = activeCategory === null || product.category === activeCategory;
    const matchesSearch = normalizedSearch === "" || [
      product.name,
      product.sku,
      product.category,
      product.description,
    ].some((value) => value.toLowerCase().includes(normalizedSearch));

    return matchesCategory && matchesSearch;
  });

  const submitSearch = (event: FormEvent) => {
    event.preventDefault();
    const term = searchTerm.trim();

    router.visit(term ? `/produtos?busca=${encodeURIComponent(term)}` : "/produtos", {
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <div>
      <div className="bg-navy text-white">
        <div className="mx-auto max-w-7xl px-4 py-10">
          <div className="flex items-center gap-2 text-xs text-white/70">
            <Link href="/" className="hover:text-yellow">Inicio</Link>
            <ChevronRight className="h-3 w-3" />
            <span className="text-yellow">Produtos</span>
          </div>
          <h1 className="mt-3 font-display text-4xl font-black md:text-5xl">Todos os fardamentos</h1>
          <p className="mt-3 max-w-2xl text-white/80">Encontre o uniforme ideal para sua empresa, escola ou equipe.</p>
          {normalizedSearch && <div className="mt-4 inline-flex rounded-full bg-yellow px-3 py-1 text-xs font-black text-navy">Busca: {searchTerm}</div>}
        </div>
      </div>

      <div className="mx-auto grid max-w-7xl gap-6 px-4 py-6 md:py-10 lg:grid-cols-[260px_1fr] lg:gap-8">
        <aside className="hidden space-y-6 lg:block">
          <FilterGroup title="Categorias">
            <CategoryButton label="Todos" active={activeCategory === null} onClick={() => setActiveCategory(null)} />
            {categories.map((category) => (
              <CategoryButton key={category} label={category} active={activeCategory === category} onClick={() => setActiveCategory(activeCategory === category ? null : category)} />
            ))}
          </FilterGroup>
          <FilterGroup title="Estoque">
            <div className="rounded-md bg-bg-soft px-3 py-2 text-sm text-text-muted">
              Produtos com disponibilidade aparecem no detalhe antes da compra.
            </div>
          </FilterGroup>
        </aside>

        <div>
          <div className="mb-4 flex gap-2 overflow-x-auto pb-1 lg:hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <CategoryChip label="Todos" active={activeCategory === null} onClick={() => setActiveCategory(null)} />
            {categories.map((category) => (
              <CategoryChip key={category} label={category} active={activeCategory === category} onClick={() => setActiveCategory(activeCategory === category ? null : category)} />
            ))}
          </div>

          <div className="mb-5 flex items-center justify-between gap-3">
            <div className="text-sm text-text-muted"><span className="font-semibold text-text-dark">{filtered.length}</span> produtos encontrados</div>
            <div className="flex shrink-0 items-center gap-2">
              <select className="max-w-36 rounded-md border border-border bg-white px-2 py-2 text-xs sm:max-w-none sm:px-3 sm:text-sm">
                <option>Relevancia</option>
                <option>Menor preco</option>
                <option>Maior preco</option>
              </select>
              <div className="flex overflow-hidden rounded-md border border-border">
                <button aria-label="Grade" onClick={() => setView("grid")} className={`p-2 ${view === "grid" ? "bg-navy text-white" : "bg-white text-navy"}`}><LayoutGrid className="h-4 w-4" /></button>
                <button aria-label="Lista" onClick={() => setView("list")} className={`p-2 ${view === "list" ? "bg-navy text-white" : "bg-white text-navy"}`}><List className="h-4 w-4" /></button>
              </div>
            </div>
          </div>

          <form onSubmit={submitSearch} className="mb-5 flex gap-2">
            <div className="relative flex-1">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-navy" />
              <input
                type="search"
                value={searchTerm}
                onChange={(event) => setSearchTerm(event.target.value)}
                placeholder="Buscar por produto, SKU ou categoria"
                className="h-11 w-full rounded-lg border border-border bg-white pl-10 pr-3 text-sm outline-none focus:border-navy"
              />
            </div>
            <button className="rounded-lg bg-navy px-4 py-2 text-sm font-black text-white">Buscar</button>
          </form>

          {filtered.length > 0 ? (
            <div className={view === "grid" ? "grid grid-cols-2 gap-3 sm:gap-6 xl:grid-cols-3" : "space-y-4"}>
              {filtered.map((product) => <CatalogProductCard key={product.id} product={product} />)}
            </div>
          ) : (
            <div className="rounded-xl border border-border bg-white p-8 text-center">
              <h2 className="font-display text-xl font-black text-navy">Nenhum produto encontrado</h2>
              <p className="mt-2 text-sm text-text-muted">Tente outro termo ou limpe a busca para ver todos os produtos.</p>
              <button type="button" onClick={() => { setSearchTerm(""); router.visit("/produtos"); }} className="mt-5 rounded-md bg-yellow px-5 py-3 text-sm font-black text-navy">Ver todos os produtos</button>
            </div>
          )}
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

function CategoryButton({ label, active, onClick }: { label: string; active: boolean; onClick: () => void }) {
  return (
    <button onClick={onClick} className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition ${active ? "bg-navy font-semibold text-white" : "hover:bg-bg-soft"}`}>
      {label}
    </button>
  );
}

function CategoryChip({ label, active, onClick }: { label: string; active: boolean; onClick: () => void }) {
  return (
    <button onClick={onClick} className={`shrink-0 rounded-full border px-4 py-2 text-xs font-black ${active ? "border-navy bg-navy text-white" : "border-border bg-white text-navy"}`}>
      {label}
    </button>
  );
}
