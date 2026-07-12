import { Link, router } from "@inertiajs/react";
import type { FormEvent } from "react";
import { useEffect, useState } from "react";
import {
  Building2,
  ChevronDown,
  Factory,
  GraduationCap,
  Mail,
  MapPin,
  Menu,
  MessageCircle,
  Phone,
  Search,
  ShieldCheck,
  Shirt,
  ShoppingCart,
  Sparkles,
  Truck,
} from "lucide-react";

const departments = [
  { href: "/produtos", label: "Calças em brim", icon: Shirt },
  { href: "/produtos", label: "Batas profissionais", icon: ShieldCheck },
  { href: "/produtos", label: "Camisetas PV", icon: Sparkles },
  { href: "/empresas", label: "Empresas", icon: Building2 },
  { href: "/escolas", label: "Escolas", icon: GraduationCap },
  { href: "/personalizados", label: "Personalizados", icon: Factory },
];

type SiteSettings = {
  storeName: string;
  logoUrl: string;
};

type CartSummary = {
  itemsCount?: number;
};

type TopbarNotification = {
  message: string;
  linkLabel: string | null;
  linkUrl: string | null;
};

export function SiteHeader({ settings, initialCartItemsCount, notification }: { settings: SiteSettings; initialCartItemsCount: number; notification: TopbarNotification | null }) {
  const [cartItemsCount, setCartItemsCount] = useState(initialCartItemsCount);
  const [searchTerm, setSearchTerm] = useState("");
  const cartBadge = cartItemsCount > 99 ? "99+" : String(cartItemsCount);

  const submitSearch = (event: FormEvent) => {
    event.preventDefault();
    const term = searchTerm.trim();

    router.visit(term ? `/produtos?busca=${encodeURIComponent(term)}` : "/produtos");
  };

  useEffect(() => {
    setCartItemsCount(initialCartItemsCount);
  }, [initialCartItemsCount]);

  useEffect(() => {
    return router.on("success", (event) => {
      const cartSummary = event.detail.page.props.cartSummary as CartSummary | undefined;
      setCartItemsCount(cartSummary?.itemsCount ?? 0);
    });
  }, []);

  return (
    <header className="sticky top-0 z-40 bg-white shadow-[0_8px_24px_-22px_var(--color-navy)]">
      {notification && (
        <div className="bg-yellow text-navy">
          <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-2 px-3 py-2 text-center text-xs font-black sm:px-4">
            <span>{notification.message}</span>
            {notification.linkLabel && notification.linkUrl && (
              <Link href={notification.linkUrl} className="underline decoration-2 underline-offset-2">
                {notification.linkLabel}
              </Link>
            )}
          </div>
        </div>
      )}
      <div className="bg-navy text-white">
        <div className="mx-auto flex max-w-7xl items-center justify-center gap-2 px-3 py-2 text-[11px] font-bold sm:justify-between sm:px-4 sm:text-xs">
          <div className="flex min-w-0 items-center gap-3">
            <span className="inline-flex items-center gap-1.5"><Truck className="h-3.5 w-3.5 text-yellow" /> Enviamos para todo o Brasil</span>
            <span className="hidden sm:inline-flex items-center gap-1.5"><ShieldCheck className="h-3.5 w-3.5 text-yellow" /> Uniformes sob medida desde 2007</span>
          </div>
          <div className="hidden items-center gap-4 sm:flex">
            <a href="https://wa.me/5581979106667" className="inline-flex items-center gap-1.5 hover:underline">
              <MessageCircle className="h-3.5 w-3.5" /> PE: (81) 97910-6667
            </a>
            <a href="https://wa.me/5511942110729" className="hidden items-center gap-1.5 hover:underline sm:inline-flex">
              <Phone className="h-3.5 w-3.5" /> SP: (11) 94211-0729
            </a>
          </div>
        </div>
      </div>

      <div className="border-b border-border bg-white">
        <div className="mx-auto grid max-w-7xl grid-cols-[1fr_auto] items-center gap-3 px-3 py-3 md:grid-cols-[230px_1fr_auto] md:px-4">
          <Link href="/" className="flex w-fit items-center rounded-xl border border-border bg-white p-2 shadow-[var(--shadow-soft)]">
            <img src={settings.logoUrl} width={188} alt={settings.storeName} className="max-h-10 w-auto md:max-h-12" />
          </Link>

          <form onSubmit={submitSearch} className="relative order-3 col-span-2 md:order-none md:col-span-1">
            <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-navy" />
            <input
              type="search"
              placeholder="Buscar uniformes..."
              value={searchTerm}
              onChange={(event) => setSearchTerm(event.target.value)}
              className="h-11 w-full rounded-xl border-2 border-navy bg-bg-soft pl-12 pr-4 text-sm font-medium outline-none transition focus:border-yellow focus:bg-white md:h-12"
            />
          </form>

          <div className="flex items-center justify-between gap-2 md:justify-end">
            <a href="mailto:fabricadefardamentos@gmail.com" className="hidden rounded-xl border border-border bg-bg-soft px-3 py-2 text-xs font-bold text-navy transition hover:border-navy md:inline-flex">
              <Mail className="mr-1.5 h-4 w-4" /> E-mail
            </a>
            <Link href="/carrinho" className="relative inline-flex items-center gap-2 rounded-xl bg-yellow px-3 py-2.5 text-sm font-black text-navy shadow-[var(--shadow-soft)] transition hover:brightness-95 sm:px-5 sm:py-3">
              <ShoppingCart className="h-4 w-4" /> <span className="hidden sm:inline">Carrinho</span>
              <span className="absolute -right-2 -top-2 grid h-5 min-w-5 place-items-center rounded-full bg-navy px-1 text-[11px] text-white">{cartBadge}</span>
            </Link>
            <Link href="/produtos" className="rounded-xl border border-border p-2.5 text-navy md:hidden" aria-label="Produtos">
              <Menu className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </div>

      <nav className="border-b-4 border-yellow bg-navy text-white">
        <div className="mx-auto flex max-w-7xl flex-nowrap items-center gap-2 overflow-x-auto px-3 py-2 [scrollbar-width:none] md:flex-wrap md:px-4 md:py-2.5 [&::-webkit-scrollbar]:hidden">
          <div className="group relative shrink-0">
            <Link href="/produtos" className="inline-flex items-center gap-2 rounded-lg bg-yellow px-3 py-2 text-xs font-black text-navy transition hover:brightness-95 md:px-4 md:py-2.5 md:text-sm">
              <Menu className="h-4 w-4" /> Departamentos <ChevronDown className="hidden h-4 w-4 md:block" />
            </Link>
            <div className="invisible absolute left-0 top-full z-50 hidden w-[min(760px,calc(100vw-2rem))] pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100 md:block">
              <div className="rounded-xl border border-border bg-white p-4 text-navy shadow-[var(--shadow-card)]">
                <div className="mb-3 flex items-center justify-between border-b border-border pb-3">
                  <div>
                    <div className="text-xs font-black uppercase tracking-[0.18em] text-navy/60">Departamentos</div>
                    <div className="font-display text-lg font-black text-navy">Compre por categoria</div>
                  </div>
                  <Link href="/produtos" className="rounded-md bg-navy px-3 py-2 text-xs font-black text-white">Ver tudo</Link>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                  {departments.map((item) => (
                    <Link key={item.label} href={item.href} className="flex items-center gap-3 rounded-lg border border-border bg-bg-soft p-3 transition hover:border-yellow hover:bg-white hover:shadow-[var(--shadow-soft)]">
                      <span className="grid h-10 w-10 place-items-center rounded-md bg-navy text-yellow">
                        <item.icon className="h-5 w-5" />
                      </span>
                      <span className="font-display text-sm font-bold">{item.label}</span>
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          </div>

          <Link href="/" className="shrink-0 rounded-md px-3 py-2 text-xs font-bold transition hover:bg-white/10 md:text-sm">Início</Link>
          <Link href="/produtos" className="shrink-0 rounded-md px-3 py-2 text-xs font-bold transition hover:bg-white/10 md:text-sm">Produtos</Link>
          <Link href="/empresas" className="shrink-0 rounded-md px-3 py-2 text-xs font-bold transition hover:bg-white/10 md:text-sm">Empresas</Link>
          <Link href="/escolas" className="shrink-0 rounded-md px-3 py-2 text-xs font-bold transition hover:bg-white/10 md:text-sm">Escolas</Link>
          <Link href="/personalizados" className="shrink-0 rounded-md px-3 py-2 text-xs font-bold transition hover:bg-white/10 md:text-sm">Personalizados</Link>
          <a href="/#lojas" className="inline-flex shrink-0 items-center gap-1.5 rounded-md px-3 py-2 text-xs font-bold transition hover:bg-white/10 md:text-sm">
            <MapPin className="h-4 w-4 text-yellow" /> Lojas
          </a>
          <a href="/#historia" className="shrink-0 rounded-md px-3 py-2 text-xs font-bold transition hover:bg-white/10 md:text-sm">Sobre nós</a>

          <Link href="/carrinho" className="ml-auto hidden items-center gap-2 rounded-md bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/20 lg:inline-flex">
            <ShoppingCart className="h-4 w-4 text-yellow" /> Carrinho
            <span className="grid h-5 min-w-5 place-items-center rounded-full bg-yellow px-1 text-[11px] font-black text-navy">{cartBadge}</span>
          </Link>
        </div>
      </nav>
    </header>
  );
}
