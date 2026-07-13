import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight, LayoutGrid, List, Search } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useMemo, useState } from 'react';
import type { CatalogProduct } from '@/modules/catalog/domain/product';
import { CatalogProductCard } from '@/modules/catalog/ui/catalog-product-card';

type Filters = {
    busca?: string;
    categoria?: string;
    preco_min?: number;
    preco_max?: number;
    estoque?: string;
    variacao?: string;
    ordenar?: string;
};
type Pagination = {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
};

export default function ProductsPage({
    products = [],
    categories = [],
    filters = {},
    pagination = { currentPage: 1, lastPage: 1, perPage: 12, total: 0 },
}: {
    products?: CatalogProduct[];
    categories?: string[];
    filters?: Filters;
    pagination?: Pagination;
}) {
    const safeProducts = Array.isArray(products) ? products : [];
    const initialSearch =
        filters.busca ??
        (typeof window === 'undefined'
            ? ''
            : (new URLSearchParams(window.location.search).get('busca') ?? ''));
    const [activeCategory, setActiveCategory] = useState<string | null>(
        filters.categoria || null,
    );
    const [searchTerm, setSearchTerm] = useState(initialSearch);
    const [minPrice, setMinPrice] = useState(String(filters.preco_min || ''));
    const [maxPrice, setMaxPrice] = useState(String(filters.preco_max || ''));
    const [stockFilter, setStockFilter] = useState(filters.estoque || '');
    const [variationFilter, setVariationFilter] = useState(
        filters.variacao || '',
    );
    const [sort, setSort] = useState(filters.ordenar || 'relevancia');
    const [view, setView] = useState<'grid' | 'list'>('grid');

    const safeCategories = useMemo(
        () => (Array.isArray(categories) ? categories : []),
        [categories],
    );
    const filtered = safeProducts;

    const goToFilters = (next: {
        busca?: string;
        categoria?: string | null;
        preco_min?: string;
        preco_max?: string;
        estoque?: string;
        variacao?: string;
        ordenar?: string;
    }) => {
        const params = new URLSearchParams();
        const term = (next.busca ?? searchTerm).trim();
        const category =
            next.categoria === undefined ? activeCategory : next.categoria;
        const priceMin = next.preco_min ?? minPrice;
        const priceMax = next.preco_max ?? maxPrice;
        const stock = next.estoque ?? stockFilter;
        const variation = next.variacao ?? variationFilter;
        const order = next.ordenar ?? sort;

        if (term) {
            params.set('busca', term);
        }

        if (category) {
            params.set('categoria', category);
        }

        if (priceMin) {
            params.set('preco_min', priceMin);
        }

        if (priceMax) {
            params.set('preco_max', priceMax);
        }

        if (stock) {
            params.set('estoque', stock);
        }

        if (variation) {
            params.set('variacao', variation);
        }

        if (order && order !== 'relevancia') {
            params.set('ordenar', order);
        }

        router.visit(params.toString() ? `/produtos?${params}` : '/produtos', {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        goToFilters({ busca: searchTerm });
    };

    return (
        <div>
            <Head title="Produtos">
                <meta
                    name="description"
                    content="Compre uniformes profissionais, escolares e personalizados para empresas em todo o Brasil."
                />
            </Head>
            <div className="bg-navy text-white">
                <div className="mx-auto max-w-7xl px-4 py-10">
                    <div className="flex items-center gap-2 text-xs text-white/70">
                        <Link href="/" className="hover:text-yellow">
                            Inicio
                        </Link>
                        <ChevronRight className="h-3 w-3" />
                        <span className="text-yellow">Produtos</span>
                    </div>
                    <h1 className="mt-3 font-display text-4xl font-black md:text-5xl">
                        Todos os fardamentos
                    </h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Encontre o uniforme ideal para sua empresa, escola ou
                        equipe.
                    </p>
                    {searchTerm.trim() && (
                        <div className="mt-4 inline-flex rounded-full bg-yellow px-3 py-1 text-xs font-black text-navy">
                            Busca: {searchTerm}
                        </div>
                    )}
                </div>
            </div>

            <div className="mx-auto grid max-w-7xl gap-6 px-4 py-6 md:py-10 lg:grid-cols-[260px_1fr] lg:gap-8">
                <aside className="hidden space-y-6 lg:block">
                    <FilterGroup title="Categorias">
                        <CategoryButton
                            label="Todos"
                            active={activeCategory === null}
                            onClick={() => {
                                setActiveCategory(null);
                                goToFilters({ categoria: null });
                            }}
                        />
                        {safeCategories.map((category) => (
                            <CategoryButton
                                key={category}
                                label={category}
                                active={activeCategory === category}
                                onClick={() => {
                                    const next =
                                        activeCategory === category
                                            ? null
                                            : category;
                                    setActiveCategory(next);
                                    goToFilters({ categoria: next });
                                }}
                            />
                        ))}
                    </FilterGroup>
                    <FilterGroup title="Estoque">
                        <CategoryButton
                            label="Todos"
                            active={stockFilter === ''}
                            onClick={() => {
                                setStockFilter('');
                                goToFilters({ estoque: '' });
                            }}
                        />
                        <CategoryButton
                            label="Disponiveis"
                            active={stockFilter === 'disponivel'}
                            onClick={() => {
                                setStockFilter('disponivel');
                                goToFilters({ estoque: 'disponivel' });
                            }}
                        />
                    </FilterGroup>
                    <FilterGroup title="Preco">
                        <div className="grid gap-2">
                            <input
                                value={minPrice}
                                onChange={(event) =>
                                    setMinPrice(event.target.value)
                                }
                                className="rounded-md border border-border px-3 py-2 text-sm"
                                placeholder="Minimo"
                            />
                            <input
                                value={maxPrice}
                                onChange={(event) =>
                                    setMaxPrice(event.target.value)
                                }
                                className="rounded-md border border-border px-3 py-2 text-sm"
                                placeholder="Maximo"
                            />
                            <input
                                value={variationFilter}
                                onChange={(event) =>
                                    setVariationFilter(event.target.value)
                                }
                                className="rounded-md border border-border px-3 py-2 text-sm"
                                placeholder="Tamanho/cor"
                            />
                            <button
                                onClick={() =>
                                    goToFilters({
                                        preco_min: minPrice,
                                        preco_max: maxPrice,
                                        variacao: variationFilter,
                                    })
                                }
                                className="rounded-md bg-navy px-3 py-2 text-sm font-black text-white"
                            >
                                Aplicar
                            </button>
                        </div>
                    </FilterGroup>
                </aside>

                <div>
                    <div className="mb-4 flex [scrollbar-width:none] gap-2 overflow-x-auto pb-1 lg:hidden [&::-webkit-scrollbar]:hidden">
                        <CategoryChip
                            label="Todos"
                            active={activeCategory === null}
                            onClick={() => {
                                setActiveCategory(null);
                                goToFilters({ categoria: null });
                            }}
                        />
                        {safeCategories.map((category) => (
                            <CategoryChip
                                key={category}
                                label={category}
                                active={activeCategory === category}
                                onClick={() => {
                                    const next =
                                        activeCategory === category
                                            ? null
                                            : category;
                                    setActiveCategory(next);
                                    goToFilters({ categoria: next });
                                }}
                            />
                        ))}
                    </div>

                    <div className="mb-5 flex items-center justify-between gap-3">
                        <div className="text-sm text-text-muted">
                            <span className="font-semibold text-text-dark">
                                {pagination.total}
                            </span>{' '}
                            produtos encontrados
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                            <select
                                value={sort}
                                onChange={(event) => {
                                    setSort(event.target.value);
                                    goToFilters({
                                        ordenar: event.target.value,
                                    });
                                }}
                                className="max-w-36 rounded-md border border-border bg-white px-2 py-2 text-xs sm:max-w-none sm:px-3 sm:text-sm"
                            >
                                <option value="relevancia">Relevancia</option>
                                <option value="menor-preco">Menor preco</option>
                                <option value="maior-preco">Maior preco</option>
                                <option value="nome">Nome</option>
                            </select>
                            <div className="flex overflow-hidden rounded-md border border-border">
                                <button
                                    aria-label="Grade"
                                    onClick={() => setView('grid')}
                                    className={`p-2 ${view === 'grid' ? 'bg-navy text-white' : 'bg-white text-navy'}`}
                                >
                                    <LayoutGrid className="h-4 w-4" />
                                </button>
                                <button
                                    aria-label="Lista"
                                    onClick={() => setView('list')}
                                    className={`p-2 ${view === 'list' ? 'bg-navy text-white' : 'bg-white text-navy'}`}
                                >
                                    <List className="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <form onSubmit={submitSearch} className="mb-5 flex gap-2">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-navy" />
                            <input
                                type="search"
                                value={searchTerm}
                                onChange={(event) =>
                                    setSearchTerm(event.target.value)
                                }
                                placeholder="Buscar por produto, SKU ou categoria"
                                className="h-11 w-full rounded-lg border border-border bg-white pr-3 pl-10 text-sm outline-none focus:border-navy"
                            />
                        </div>
                        <button className="rounded-lg bg-navy px-4 py-2 text-sm font-black text-white">
                            Buscar
                        </button>
                    </form>

                    {filtered.length > 0 ? (
                        <div
                            className={
                                view === 'grid'
                                    ? 'grid grid-cols-2 gap-3 sm:gap-6 xl:grid-cols-3'
                                    : 'space-y-4'
                            }
                        >
                            {filtered.map((product) => (
                                <CatalogProductCard
                                    key={product.id}
                                    product={product}
                                    variant={view}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-border bg-white p-8 text-center">
                            <h2 className="font-display text-xl font-black text-navy">
                                Nenhum produto encontrado
                            </h2>
                            <p className="mt-2 text-sm text-text-muted">
                                Tente outro termo ou limpe a busca para ver
                                todos os produtos.
                            </p>
                            <button
                                type="button"
                                onClick={() => {
                                    setSearchTerm('');
                                    router.visit('/produtos');
                                }}
                                className="mt-5 rounded-md bg-yellow px-5 py-3 text-sm font-black text-navy"
                            >
                                Ver todos os produtos
                            </button>
                        </div>
                    )}
                    {pagination.lastPage > 1 && (
                        <nav
                            className="mt-8 flex flex-wrap justify-center gap-2"
                            aria-label="Paginacao de produtos"
                        >
                            {Array.from(
                                { length: pagination.lastPage },
                                (_, index) => index + 1,
                            ).map((page) => (
                                <button
                                    key={page}
                                    type="button"
                                    onClick={() => {
                                        const params = new URLSearchParams(
                                            window.location.search,
                                        );
                                        params.set('pagina', String(page));
                                        router.visit(
                                            `/produtos?${params.toString()}`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className={`grid h-10 min-w-10 place-items-center rounded-lg px-3 text-sm font-black ${page === pagination.currentPage ? 'bg-navy text-white' : 'border border-border bg-white text-navy'}`}
                                >
                                    {page}
                                </button>
                            ))}
                        </nav>
                    )}
                </div>
            </div>
        </div>
    );
}

function FilterGroup({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="rounded-xl border border-border bg-white p-4">
            <div className="mb-3 font-display text-sm font-bold tracking-wider text-navy uppercase">
                {title}
            </div>
            <div className="space-y-1">{children}</div>
        </div>
    );
}

function CategoryButton({
    label,
    active,
    onClick,
}: {
    label: string;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            onClick={onClick}
            className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition ${active ? 'bg-navy font-semibold text-white' : 'hover:bg-bg-soft'}`}
        >
            {label}
        </button>
    );
}

function CategoryChip({
    label,
    active,
    onClick,
}: {
    label: string;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            onClick={onClick}
            className={`shrink-0 rounded-full border px-4 py-2 text-xs font-black ${active ? 'border-navy bg-navy text-white' : 'border-border bg-white text-navy'}`}
        >
            {label}
        </button>
    );
}
