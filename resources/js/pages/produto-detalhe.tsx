import { Link, router } from "@inertiajs/react";
import { ArrowLeft, Minus, Plus, ShoppingCart } from "lucide-react";
import { useMemo, useState } from "react";
import fallbackImage from "@/assets/prod-polo.jpg";
import { createIdempotencyKey } from "@/lib/idempotency-key";
import type { CatalogProduct } from "@/modules/catalog/domain/product";
import { formatMoney } from "@/modules/catalog/domain/product";

export default function ProdutoDetalhe({ product }: { product: CatalogProduct }) {
  const gallery = useMemo(() => {
    const images = [product.imageUrl, ...(product.galleryImages ?? [])].filter(Boolean) as string[];
    return Array.from(new Set(images.length > 0 ? images : [fallbackImage]));
  }, [product.galleryImages, product.imageUrl]);
  const firstPurchasableVariation = product.variations.find((variation) => variation.purchasable);
  const [selectedImage, setSelectedImage] = useState(gallery[0]);
  const [quantity, setQuantity] = useState(1);
  const [adding, setAdding] = useState(false);
  const [selectedVariationId, setSelectedVariationId] = useState(firstPurchasableVariation?.id ?? "");

  const selectedVariation = product.variations.find((variation) => variation.id === selectedVariationId) ?? null;
  const availableStock = selectedVariation ? selectedVariation.stock : product.stockAvailable;
  const canBuy = product.variations.length > 0 ? Boolean(selectedVariation?.purchasable) : product.stockAvailable > 0;
  const safeQuantity = Math.min(quantity, Math.max(availableStock, 1));

  const addToCart = () => {
    if (!canBuy) return;
    setAdding(true);
    router.post(
      "/carrinho/itens",
      { productId: product.id, quantity: safeQuantity, variationId: selectedVariationId || null },
      {
        headers: { "Idempotency-Key": createIdempotencyKey() },
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

      <main className="mx-auto grid max-w-7xl gap-6 px-4 py-6 lg:grid-cols-[0.95fr_1.05fr] lg:gap-8 lg:py-8">
        <div className="space-y-3">
          <div className="overflow-hidden rounded-2xl border border-border bg-white p-3 shadow-[var(--shadow-soft)]">
            <img src={selectedImage} alt={product.name} className="aspect-square w-full rounded-xl object-cover sm:aspect-[4/3]" />
          </div>
          <div className="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            {gallery.map((image) => (
              <button
                key={image}
                type="button"
                onClick={() => setSelectedImage(image)}
                className={`w-20 shrink-0 overflow-hidden rounded-xl border-2 bg-white p-1 ${selectedImage === image ? "border-navy ring-2 ring-yellow" : "border-border"}`}
              >
                <img src={image} alt="" className="aspect-square w-full rounded-lg object-cover" />
              </button>
            ))}
          </div>
        </div>

        <section className="rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
          <div className="flex flex-wrap items-center gap-2">
            <span className="rounded-full bg-bg-soft px-3 py-1 text-xs font-bold uppercase tracking-wider text-text-muted">{product.category}</span>
            <span className={`rounded-full px-3 py-1 text-xs font-black ${product.stockAvailable > 0 ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"}`}>
              {product.stockAvailable > 0 ? `${product.stockAvailable} disponiveis` : "Sem estoque"}
            </span>
          </div>

          <h1 className="mt-4 font-display text-3xl font-black text-navy md:text-4xl">{product.name}</h1>
          <p className="mt-3 leading-7 text-text-muted">{product.description}</p>

          <div className="mt-6 rounded-xl bg-bg-soft p-4">
            <div className="text-xs text-text-muted">Preco unitario</div>
            <div className="font-display text-4xl font-black text-navy">{formatMoney(product.priceAmount, product.priceCurrency)}</div>
          </div>

          {product.variations.length > 0 && (
            <div className="mt-6">
              <div className="mb-2 text-sm font-bold text-navy">Variacoes</div>
              <div className="grid gap-2 sm:grid-cols-2">
                {product.variations.map((variation) => (
                  <button
                    key={variation.id}
                    type="button"
                    disabled={!variation.purchasable}
                    onClick={() => {
                      setSelectedVariationId(variation.id);
                      setQuantity(1);
                    }}
                    className={`rounded-lg border px-4 py-3 text-left transition ${
                      selectedVariationId === variation.id
                        ? "border-navy bg-navy text-white"
                        : "border-border bg-white text-navy hover:border-navy"
                    } disabled:cursor-not-allowed disabled:border-border disabled:bg-bg-soft disabled:text-text-muted disabled:opacity-70`}
                  >
                    <div className="text-sm font-black">{variation.name}: {variation.value}</div>
                    <div className="mt-1 text-xs">
                      {variation.purchasable ? `${variation.stock} em estoque` : "Estoque baixo - indisponivel"}
                    </div>
                  </button>
                ))}
              </div>
            </div>
          )}

          <div className="mt-6">
            <div className="mb-2 text-sm font-bold text-navy">Quantidade</div>
            <div className="inline-flex overflow-hidden rounded-md border border-border">
              <button onClick={() => setQuantity(Math.max(1, quantity - 1))} className="grid h-11 w-11 place-items-center" aria-label="Diminuir quantidade"><Minus className="h-4 w-4" /></button>
              <div className="grid h-11 w-14 place-items-center border-x border-border font-black">{safeQuantity}</div>
              <button onClick={() => setQuantity(Math.min(availableStock || 1, quantity + 1))} className="grid h-11 w-11 place-items-center" aria-label="Aumentar quantidade"><Plus className="h-4 w-4" /></button>
            </div>
          </div>

          <button disabled={adding || !canBuy} onClick={addToCart} className="mt-7 inline-flex w-full items-center justify-center gap-2 rounded-md bg-yellow px-6 py-3 font-black text-navy disabled:cursor-not-allowed disabled:opacity-60">
            <ShoppingCart className="h-5 w-5" /> {adding ? "Adicionando..." : canBuy ? "Adicionar ao carrinho" : "Indisponivel para compra"}
          </button>
        </section>
      </main>
    </div>
  );
}
