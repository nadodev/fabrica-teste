import { Link, router, usePage } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import fallbackImage from '@/assets/prod-polo.jpg';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import type { CatalogProduct } from '@/modules/catalog/domain/product';
import { formatMoney } from '@/modules/catalog/domain/product';

export function CatalogProductCard({
    product,
    variant = 'grid',
}: {
    product: CatalogProduct;
    variant?: 'grid' | 'list';
}) {
    const minimumQuantity = Math.max(
        1,
        Number(
            usePage<{
                commerceSettings?: { products?: { minQuantity?: number } };
            }>().props.commerceSettings?.products?.minQuantity ?? 1,
        ),
    );
    const [adding, setAdding] = useState(false);
    const variations = product.variations ?? [];
    const hasVariations = variations.length > 0;
    const canQuickAdd =
        !hasVariations &&
        (product.canSellWithoutStock || product.stockAvailable > 0);

    const addToCart = () => {
        if (!canQuickAdd) {
            return;
        }

        setAdding(true);
        router.post(
            '/carrinho/itens',
            {
                productId: product.id,
                quantity: minimumQuantity,
                variationId: null,
            },
            {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
                onFinish: () => setAdding(false),
            },
        );
    };

    return (
        <article
            className={`group overflow-hidden rounded-xl border border-border bg-white transition hover:-translate-y-1 hover:shadow-[var(--shadow-card)] ${variant === 'list' ? 'grid sm:grid-cols-[210px_1fr]' : 'flex flex-col'}`}
        >
            <div
                className={`overflow-hidden bg-bg-soft ${variant === 'list' ? 'aspect-[4/3] h-full sm:aspect-auto' : 'aspect-[4/5] sm:aspect-square'}`}
            >
                <img
                    src={product.imageUrl ?? fallbackImage}
                    alt={product.name}
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
            </div>
            <div className="flex flex-1 flex-col p-4">
                <div className="text-xs font-semibold tracking-wider text-text-muted uppercase">
                    {product.category}
                </div>
                <h2 className="mt-1 font-display text-base font-bold text-navy">
                    {product.name}
                </h2>
                <p className="mt-1 line-clamp-2 text-sm leading-5 text-text-muted">
                    {product.description}
                </p>
                {!product.canSellWithoutStock && (
                    <div
                        className={`mt-3 text-xs font-bold ${product.stockAvailable > 0 ? 'text-green-700' : 'text-red-700'}`}
                    >
                        {product.stockAvailable > 0
                            ? `${product.stockAvailable} em estoque`
                            : 'Sem estoque'}
                    </div>
                )}
                <div className="mt-auto pt-4 font-display text-xl font-black text-navy">
                    {formatMoney(product.priceAmount, product.priceCurrency)}
                </div>
                <div
                    className={
                        variant === 'list' ? 'flex flex-wrap gap-2' : 'flex gap-2 items-center'
                    }
                >
                    <Link
                        href={`/produtos/${product.id}`}
                        className="inline-flex justify-center rounded-md bg-navy px-3 py-2 text-xs font-semibold text-white"
                    >
                        Ver detalhes
                    </Link>
                    {hasVariations ? (
                        <Link
                            href={`/produtos/${product.id}`}
                            className="inline-flex items-center justify-center gap-2 rounded-md bg-yellow px-3 py-2 text-xs font-black text-navy"
                        >
                            <ShoppingCart className="h-3.5 w-3.5" /> Escolher
                            variacao
                        </Link>
                    ) : (
                        <button
                            type="button"
                            disabled={!canQuickAdd || adding}
                            onClick={addToCart}
                            className="inline-flex items-center justify-center gap-2 rounded-md bg-yellow px-3 py-2 text-xs font-black text-navy disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <ShoppingCart className="h-3.5 w-3.5" />{' '}
                            {adding ? 'Adicionando...' : 'Comprar'}
                        </button>
                    )}
                </div>
            </div>
        </article>
    );
}
