import { Link, router } from "@inertiajs/react";
import { ArrowLeft, Minus, Plus, ShoppingCart } from "lucide-react";
import { useState } from "react";
import fallbackImage from "@/assets/prod-polo.jpg";
import type { CatalogProduct } from "@/modules/catalog/domain/product";
import { formatMoney } from "@/modules/catalog/domain/product";

export default function ProdutoDetalhe({ product }: { product: CatalogProduct }) {
  const [quantity, setQuantity] = useState(1);
  const [adding, setAdding] = useState(false);
  const image = product.imageUrl ?? fallbackImage;

  const addToCart = () => {
    setAdding(true);
    router.post(
      "/carrinho/itens",
      { productId: product.id, quantity },
      {
        headers: { "Idempotency-Key": crypto.randomUUID() },
        onFinish: () => setAdding(false),
      },
    );
  };

  return (
    <div className="bg-bg-soft">
      <div className="border-b border-border bg-white">
        <div className="mx-auto max-w-7xl px-4 py-4">
          <Link href="/produtos" className="inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline">
            <ArrowLeft className="h-4 w-4" /> Voltar para produtos
          </Link>
        </div>
      </div>

      <main className="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-2">
        <div className="overflow-hidden rounded-2xl border border-border bg-white p-3 shadow-[var(--shadow-soft)]">
          <img src={image} alt={product.name} className="aspect-square w-full rounded-xl object-cover" />
        </div>

        <section className="rounded-2xl border border-border bg-white p-6 shadow-[var(--shadow-soft)]">
          <span className="rounded-full bg-bg-soft px-3 py-1 text-xs font-bold uppercase tracking-wider text-text-muted">{product.sku}</span>
          <h1 className="mt-4 font-display text-3xl font-black text-navy md:text-4xl">{product.name}</h1>
          <p className="mt-3 leading-7 text-text-muted">{product.description}</p>

          <div className="mt-6 rounded-xl bg-bg-soft p-4">
            <div className="text-xs text-text-muted">Preço unitário</div>
            <div className="font-display text-4xl font-black text-navy">{formatMoney(product.priceAmount, product.priceCurrency)}</div>
          </div>

          <div className="mt-6">
            <div className="mb-2 text-sm font-bold text-navy">Quantidade</div>
            <div className="inline-flex overflow-hidden rounded-md border border-border">
              <button onClick={() => setQuantity(Math.max(1, quantity - 1))} className="grid h-11 w-11 place-items-center" aria-label="Diminuir quantidade"><Minus className="h-4 w-4" /></button>
              <div className="grid h-11 w-14 place-items-center border-x border-border font-black">{quantity}</div>
              <button onClick={() => setQuantity(quantity + 1)} className="grid h-11 w-11 place-items-center" aria-label="Aumentar quantidade"><Plus className="h-4 w-4" /></button>
            </div>
          </div>

          <button disabled={adding} onClick={addToCart} className="mt-7 inline-flex w-full items-center justify-center gap-2 rounded-md bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60">
            <ShoppingCart className="h-5 w-5" /> {adding ? "Adicionando..." : "Adicionar ao carrinho"}
          </button>
        </section>
      </main>
    </div>
  );
}
