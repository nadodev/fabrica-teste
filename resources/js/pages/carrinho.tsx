import { Link, router } from "@inertiajs/react";
import { ChevronRight, ShieldCheck, ShoppingBag, Trash2 } from "lucide-react";
import fallbackImage from "@/assets/prod-polo.jpg";
import { formatMoney } from "@/modules/catalog/domain/product";

type CartItem = {
  productId: string;
  sku: string;
  name: string;
  unitPriceAmount: number;
  priceCurrency: string;
  quantity: number;
  subtotalAmount: number;
  imageUrl: string | null;
};

type Cart = {
  items: CartItem[];
  totalAmount: number;
  currency: string;
};

export default function CartPage({ cart }: { cart: Cart }) {
  const remove = (productId: string) => {
    router.delete(`/carrinho/itens/${productId}`, {
      headers: { "Idempotency-Key": crypto.randomUUID() },
      preserveScroll: true,
    });
  };

  return (
    <div className="min-h-[70vh] bg-bg-soft">
      <header className="bg-navy text-white">
        <div className="mx-auto max-w-7xl px-4 py-8">
          <div className="flex items-center gap-2 text-xs text-white/70">
            <Link href="/" className="hover:text-yellow">Início</Link>
            <ChevronRight className="h-3 w-3" />
            <span className="text-yellow">Carrinho</span>
          </div>
          <h1 className="mt-2 font-display text-3xl font-black md:text-4xl">Seu carrinho</h1>
        </div>
      </header>

      {cart.items.length === 0 ? (
        <main className="mx-auto flex max-w-2xl flex-col items-center px-4 py-20 text-center">
          <div className="grid h-20 w-20 place-items-center rounded-full bg-white text-navy shadow-[var(--shadow-soft)]"><ShoppingBag className="h-9 w-9" /></div>
          <h2 className="mt-6 font-display text-2xl font-black text-navy">Seu carrinho está vazio</h2>
          <p className="mt-2 text-text-muted">Escolha os produtos e quantidades para iniciar seu pedido.</p>
          <Link href="/produtos" className="mt-6 rounded-md bg-yellow px-6 py-3 font-black text-navy">Ver produtos</Link>
        </main>
      ) : (
        <main className="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-[1fr_360px]">
          <section className="space-y-4">
            {cart.items.map((item) => (
              <article key={item.productId} className="grid gap-4 rounded-xl border border-border bg-white p-5 sm:grid-cols-[120px_1fr]">
                <img src={item.imageUrl ?? fallbackImage} alt={item.name} className="aspect-square w-full rounded-lg object-cover" />
                <div>
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="text-xs font-bold uppercase tracking-wider text-text-muted">{item.sku}</div>
                      <h2 className="font-display text-lg font-bold text-navy">{item.name}</h2>
                    </div>
                    <button onClick={() => remove(item.productId)} aria-label={`Remover ${item.name}`} className="rounded-md p-2 text-text-muted hover:bg-bg-soft hover:text-red-700"><Trash2 className="h-4 w-4" /></button>
                  </div>
                  <div className="mt-5 flex items-end justify-between">
                    <div className="text-sm text-text-muted">{item.quantity} × {formatMoney(item.unitPriceAmount, item.priceCurrency)}</div>
                    <div className="font-display text-xl font-black text-navy">{formatMoney(item.subtotalAmount, item.priceCurrency)}</div>
                  </div>
                </div>
              </article>
            ))}
          </section>

          <aside className="h-fit rounded-xl border border-border bg-white p-6 shadow-[var(--shadow-soft)] lg:sticky lg:top-32">
            <h2 className="font-display text-lg font-bold text-navy">Resumo do pedido</h2>
            <div className="mt-5 flex items-end justify-between border-t border-border pt-5">
              <span className="text-text-muted">Subtotal</span>
              <strong className="font-display text-2xl text-navy">{formatMoney(cart.totalAmount, cart.currency)}</strong>
            </div>
            <p className="mt-2 text-xs leading-5 text-text-muted">Frete, disponibilidade e preços serão confirmados novamente no checkout.</p>
            <button className="mt-5 w-full rounded-md bg-yellow py-3 font-black text-navy">Finalizar compra</button>
            <Link href="/produtos" className="mt-3 block text-center text-sm font-semibold text-navy hover:underline">Continuar comprando</Link>
            <div className="mt-5 flex items-center gap-2 border-t border-border pt-4 text-xs text-text-muted"><ShieldCheck className="h-4 w-4 text-navy" /> Preços calculados com segurança no servidor</div>
          </aside>
        </main>
      )}
    </div>
  );
}
