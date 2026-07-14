import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ChevronRight,
    MessageCircle,
    Minus,
    Plus,
    ShieldCheck,
    ShoppingBag,
    Trash2,
    Truck,
} from 'lucide-react';
import type { FormEvent } from 'react';
import fallbackImage from '@/assets/prod-polo.jpg';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { formatPostalCode } from '@/lib/input-masks';
import { formatMoney } from '@/modules/catalog/domain/product';

type CartItem = {
    productId: string;
    cartItemKey: string;
    sku: string;
    name: string;
    unitPriceAmount: number;
    priceCurrency: string;
    quantity: number;
    subtotalAmount: number;
    imageUrl: string | null;
    variationLabel: string | null;
    notes: string | null;
};

type Cart = {
    items: CartItem[];
    subtotalAmount: number;
    discountAmount: number;
    shippingAmount: number;
    totalAmount: number;
    currency: string;
    coupon: {
        code: string;
        description: string;
        discountAmount: number;
    } | null;
    shipping: ShippingQuote | null;
};

type ShippingQuote = {
    serviceId: string;
    name: string;
    companyName: string;
    priceAmount: number;
    deliveryTime: number;
};

const emptyCart: Cart = {
    items: [],
    subtotalAmount: 0,
    discountAmount: 0,
    shippingAmount: 0,
    totalAmount: 0,
    currency: 'BRL',
    coupon: null,
    shipping: null,
};

export default function CartPage({
    cart = emptyCart,
    shippingQuotes = [],
    shippingZip = '',
}: {
    cart?: Cart;
    shippingQuotes?: ShippingQuote[];
    shippingZip?: string;
}) {
    const { commerceSettings, siteSettings, errors } = usePage<{
        commerceSettings?: {
            promotions?: {
                couponsEnabled?: boolean;
                minimumOrderValue?: number;
            };
            products?: { minQuantity?: number; maxQuantity?: number };
        };
        siteSettings?: { whatsapp?: string };
        errors?: Record<string, string>;
    }>().props;
    const safeCart = {
        ...emptyCart,
        ...cart,
        items: Array.isArray(cart?.items) ? cart.items : [],
    };
    const couponsEnabled =
        commerceSettings?.promotions?.couponsEnabled !== false;
    const minimumOrderValue = Number(
        commerceSettings?.promotions?.minimumOrderValue ?? 0,
    );
    const minQuantity = Math.max(
        1,
        Number(commerceSettings?.products?.minQuantity ?? 1),
    );
    const maxQuantity = Math.max(
        minQuantity,
        Number(commerceSettings?.products?.maxQuantity ?? 100),
    );
    const minimumReached = safeCart.totalAmount >= minimumOrderValue;
    const hasShipping = safeCart.shipping !== null;
    const freeShippingApplied =
        safeCart.shipping?.serviceId === 'free-shipping';
    const canCheckout = minimumReached && hasShipping;
    const couponForm = useForm({ code: safeCart.coupon?.code ?? '' });
    const shippingForm = useForm({
        zip: formatPostalCode(shippingZip ?? ''),
    });
    const remove = (cartItemKey: string) => {
        router.delete(`/carrinho/itens/${encodeURIComponent(cartItemKey)}`, {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    const updateQuantity = (cartItemKey: string, quantity: number) => {
        router.patch(
            `/carrinho/itens/${encodeURIComponent(cartItemKey)}`,
            { quantity },
            {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    const applyCoupon = (event: FormEvent) => {
        event.preventDefault();
        couponForm.post('/carrinho/cupom', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    const removeCoupon = () => {
        router.delete('/carrinho/cupom', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    const quoteShipping = (event: FormEvent) => {
        event.preventDefault();
        shippingForm.post('/carrinho/frete', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    const selectShipping = (serviceId: string) => {
        router.post(
            '/carrinho/frete/selecionar',
            { serviceId },
            {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    const removeShipping = () => {
        router.delete('/carrinho/frete', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    const saveNotes = (cartItemKey: string, notes: string) => {
        router.patch(
            `/carrinho/itens/${encodeURIComponent(cartItemKey)}/observacao`,
            { notes },
            {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    const whatsappText = encodeURIComponent(
        [
            'Olá! Quero finalizar este pedido:',
            ...safeCart.items.map(
                (item) =>
                    `- ${item.quantity}x ${item.name}${item.variationLabel ? ` (${item.variationLabel})` : ''}${item.notes ? ` - Obs: ${item.notes}` : ''}`,
            ),
            safeCart.shipping
                ? `Frete: ${safeCart.shipping.companyName} ${safeCart.shipping.name} - ${formatMoney(safeCart.shippingAmount, safeCart.currency)}`
                : '',
            `Total: ${formatMoney(safeCart.totalAmount, safeCart.currency)}`,
        ]
            .filter(Boolean)
            .join('\n'),
    );
    const whatsappNumber = siteSettings?.whatsapp?.replace(/\D/g, '') ?? '';

    return (
        <div className="min-h-[70vh] bg-bg-soft">
            <header className="bg-navy text-white">
                <div className="mx-auto max-w-7xl px-4 py-8">
                    <div className="flex items-center gap-2 text-xs text-white/70">
                        <Link href="/" className="hover:text-yellow">
                            Início
                        </Link>
                        <ChevronRight className="h-3 w-3" />
                        <span className="text-yellow">Carrinho</span>
                    </div>
                    <h1 className="mt-2 font-display text-3xl font-black md:text-4xl">
                        Seu carrinho
                    </h1>
                </div>
            </header>

            {safeCart.items.length === 0 ? (
                <main className="mx-auto flex max-w-2xl flex-col items-center px-4 py-20 text-center">
                    <div className="grid h-20 w-20 place-items-center rounded-full bg-white text-navy shadow-[var(--shadow-soft)]">
                        <ShoppingBag className="h-9 w-9" />
                    </div>
                    <h2 className="mt-6 font-display text-2xl font-black text-navy">
                        Seu carrinho está vazio
                    </h2>
                    <p className="mt-2 text-text-muted">
                        Escolha os produtos e quantidades para iniciar seu
                        pedido.
                    </p>
                    <Link
                        href="/produtos"
                        className="mt-6 rounded-md bg-yellow px-6 py-3 font-black text-navy"
                    >
                        Ver produtos
                    </Link>
                </main>
            ) : (
                <main className="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-[1fr_360px]">
                    <section className="space-y-4">
                        {safeCart.items.map((item) => (
                            <article
                                key={item.cartItemKey}
                                className="grid gap-4 rounded-xl border border-border bg-white p-5 sm:grid-cols-[120px_1fr]"
                            >
                                <img
                                    src={item.imageUrl ?? fallbackImage}
                                    alt={item.name}
                                    className="aspect-square w-full rounded-lg object-cover"
                                />
                                <div>
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <div className="text-xs font-bold tracking-wider text-text-muted uppercase">
                                                {item.sku}
                                            </div>
                                            <h2 className="font-display text-lg font-bold text-navy">
                                                {item.name}
                                            </h2>
                                        </div>
                                        <button
                                            onClick={() =>
                                                remove(item.cartItemKey)
                                            }
                                            aria-label={`Remover ${item.name}`}
                                            className="rounded-md p-2 text-text-muted hover:bg-bg-soft hover:text-red-700"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                    {item.variationLabel && (
                                        <div className="mt-2 text-sm font-semibold text-navy">
                                            {item.variationLabel}
                                        </div>
                                    )}
                                    <textarea
                                        defaultValue={item.notes ?? ''}
                                        onBlur={(event) =>
                                            saveNotes(
                                                item.cartItemKey,
                                                event.target.value,
                                            )
                                        }
                                        rows={2}
                                        className="mt-3 w-full rounded-lg border border-border bg-bg-soft px-3 py-2 text-sm outline-none focus:border-navy"
                                        placeholder="Observacao do item: tamanho especial, cor, bordado..."
                                    />
                                    <div className="mt-5 flex flex-wrap items-end justify-between gap-3">
                                        <div className="text-sm text-text-muted">
                                            {item.quantity} ×{' '}
                                            {formatMoney(
                                                item.unitPriceAmount,
                                                item.priceCurrency,
                                            )}
                                        </div>
                                        <div className="inline-flex overflow-hidden rounded-md border border-border">
                                            <button
                                                onClick={() =>
                                                    updateQuantity(
                                                        item.cartItemKey,
                                                        item.quantity <=
                                                            minQuantity
                                                            ? 0
                                                            : item.quantity - 1,
                                                    )
                                                }
                                                className="grid h-9 w-9 place-items-center text-navy hover:bg-bg-soft"
                                                aria-label="Diminuir quantidade"
                                            >
                                                <Minus className="h-4 w-4" />
                                            </button>
                                            <div className="grid h-9 w-12 place-items-center border-x border-border text-sm font-black">
                                                {item.quantity}
                                            </div>
                                            <button
                                                disabled={
                                                    item.quantity >= maxQuantity
                                                }
                                                onClick={() =>
                                                    updateQuantity(
                                                        item.cartItemKey,
                                                        item.quantity + 1,
                                                    )
                                                }
                                                className="grid h-9 w-9 place-items-center text-navy hover:bg-bg-soft disabled:opacity-40"
                                                aria-label="Aumentar quantidade"
                                            >
                                                <Plus className="h-4 w-4" />
                                            </button>
                                        </div>
                                        <div className="font-display text-xl font-black text-navy">
                                            {formatMoney(
                                                item.subtotalAmount,
                                                item.priceCurrency,
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </section>

                    <aside className="h-fit rounded-xl border border-border bg-white p-6 shadow-[var(--shadow-soft)] lg:sticky lg:top-32">
                        <h2 className="font-display text-lg font-bold text-navy">
                            Resumo do pedido
                        </h2>
                        {couponsEnabled && (
                            <form
                                onSubmit={applyCoupon}
                                className="mt-5 rounded-lg bg-bg-soft p-3"
                            >
                                <label className="text-xs font-black tracking-wider text-navy uppercase">
                                    Cupom de desconto
                                </label>
                                <div className="mt-2 flex gap-2">
                                    <input
                                        value={couponForm.data.code}
                                        onChange={(event) =>
                                            couponForm.setData(
                                                'code',
                                                event.target.value,
                                            )
                                        }
                                        className="min-w-0 flex-1 rounded-md border border-border bg-white px-3 py-2 text-sm uppercase outline-none focus:border-navy"
                                        placeholder="PROMO10"
                                    />
                                    <button
                                        disabled={couponForm.processing}
                                        className="rounded-md bg-navy px-3 py-2 text-xs font-black text-white disabled:opacity-60"
                                    >
                                        Aplicar
                                    </button>
                                </div>
                                {couponForm.errors.code && (
                                    <div className="mt-2 text-xs font-semibold text-red-700">
                                        {couponForm.errors.code}
                                    </div>
                                )}
                                {(couponForm.errors as Record<string, string>)
                                    .coupon && (
                                    <div className="mt-2 text-xs font-semibold text-red-700">
                                        {
                                            (
                                                couponForm.errors as Record<
                                                    string,
                                                    string
                                                >
                                            ).coupon
                                        }
                                    </div>
                                )}
                                {safeCart.coupon && (
                                    <div className="mt-3 flex items-center justify-between gap-3 rounded-md bg-white px-3 py-2 text-xs">
                                        <span>
                                            <strong>
                                                {safeCart.coupon.code}
                                            </strong>{' '}
                                            aplicado
                                        </span>
                                        <button
                                            type="button"
                                            onClick={removeCoupon}
                                            className="font-black text-red-700"
                                        >
                                            Remover
                                        </button>
                                    </div>
                                )}
                            </form>
                        )}
                        {freeShippingApplied && (
                            <div className="mt-4 rounded-lg bg-green-50 p-3 text-sm font-bold text-green-800">
                                Frete grátis.
                            </div>
                        )}
                        <form
                            onSubmit={quoteShipping}
                            className={`${freeShippingApplied ? 'hidden' : 'mt-4'} rounded-lg bg-bg-soft p-3`}
                        >
                            <label className="text-xs font-black tracking-wider text-navy uppercase">
                                Calcular frete
                            </label>
                            <div className="mt-2 flex gap-2">
                                <input
                                    inputMode="numeric"
                                    autoComplete="postal-code"
                                    maxLength={9}
                                    value={shippingForm.data.zip}
                                    onChange={(event) =>
                                        shippingForm.setData(
                                            'zip',
                                            formatPostalCode(
                                                event.target.value,
                                            ),
                                        )
                                    }
                                    className="min-w-0 flex-1 rounded-md border border-border bg-white px-3 py-2 text-sm outline-none focus:border-navy"
                                    placeholder="Digite seu CEP"
                                />
                                <button
                                    disabled={shippingForm.processing}
                                    className="rounded-md bg-navy px-3 py-2 text-xs font-black text-white disabled:opacity-60"
                                >
                                    Calcular
                                </button>
                            </div>
                            {shippingForm.errors.zip && (
                                <div className="mt-2 text-xs font-semibold text-red-700">
                                    {shippingForm.errors.zip}
                                </div>
                            )}
                            {((shippingForm.errors as Record<string, string>)
                                .shipping ??
                                errors?.shipping) && (
                                <div className="mt-2 text-xs font-semibold text-red-700">
                                    {(
                                        shippingForm.errors as Record<
                                            string,
                                            string
                                        >
                                    ).shipping ?? errors?.shipping}
                                </div>
                            )}
                            {shippingQuotes.length > 0 && (
                                <div className="mt-3 space-y-2">
                                    {shippingQuotes.map((quote) => {
                                        const selected =
                                            safeCart.shipping?.serviceId ===
                                            quote.serviceId;

                                        return (
                                            <button
                                                type="button"
                                                key={quote.serviceId}
                                                onClick={() =>
                                                    selectShipping(
                                                        quote.serviceId,
                                                    )
                                                }
                                                className={`flex w-full items-center justify-between gap-3 rounded-md border px-3 py-2 text-left text-xs ${selected ? 'border-navy bg-white' : 'border-border bg-white hover:border-navy'}`}
                                            >
                                                <span>
                                                    <strong className="block text-navy">
                                                        {quote.companyName} -{' '}
                                                        {quote.name}
                                                    </strong>
                                                    <span className="text-text-muted">
                                                        {quote.deliveryTime > 0
                                                            ? `${quote.deliveryTime} dias uteis`
                                                            : 'Prazo sob consulta'}
                                                    </span>
                                                </span>
                                                <strong className="text-navy">
                                                    {formatMoney(
                                                        quote.priceAmount,
                                                        safeCart.currency,
                                                    )}
                                                </strong>
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                            {safeCart.shipping && (
                                <div className="mt-3 flex items-center justify-between gap-3 rounded-md bg-white px-3 py-2 text-xs">
                                    <span>
                                        <strong>
                                            {safeCart.shipping.companyName}
                                        </strong>{' '}
                                        selecionado
                                    </span>
                                    <button
                                        type="button"
                                        onClick={removeShipping}
                                        className="font-black text-red-700"
                                    >
                                        Remover
                                    </button>
                                </div>
                            )}
                        </form>
                        <div className="mt-5 space-y-3 border-t border-border pt-5">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-text-muted">
                                    Subtotal
                                </span>
                                <strong className="text-navy">
                                    {formatMoney(
                                        safeCart.subtotalAmount,
                                        safeCart.currency,
                                    )}
                                </strong>
                            </div>
                            {safeCart.discountAmount > 0 && (
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-text-muted">
                                        Desconto
                                    </span>
                                    <strong className="text-green-700">
                                        -{' '}
                                        {formatMoney(
                                            safeCart.discountAmount,
                                            safeCart.currency,
                                        )}
                                    </strong>
                                </div>
                            )}
                            {safeCart.shipping && (
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-text-muted">
                                        Frete
                                    </span>
                                    <strong className="text-navy">
                                        {safeCart.shippingAmount === 0
                                            ? 'Grátis'
                                            : formatMoney(
                                                  safeCart.shippingAmount,
                                                  safeCart.currency,
                                              )}
                                    </strong>
                                </div>
                            )}
                        </div>
                        <div className="mt-4 flex items-end justify-between border-t border-border pt-5">
                            <span className="text-text-muted">Total</span>
                            <strong className="font-display text-2xl text-navy">
                                {formatMoney(
                                    safeCart.totalAmount,
                                    safeCart.currency,
                                )}
                            </strong>
                        </div>
                        <p className="mt-2 flex gap-2 text-xs leading-5 text-text-muted">
                            <Truck className="mt-0.5 h-4 w-4 shrink-0 text-navy" />{' '}
                            {hasShipping
                                ? freeShippingApplied
                                    ? 'Seu pedido recebeu frete grátis.'
                                    : 'Frete calculado e selecionado.'
                                : 'Calcule e selecione o frete para liberar a finalização.'}
                        </p>
                        {!minimumReached && (
                            <p className="mt-4 rounded-lg bg-yellow/25 p-3 text-xs font-bold text-navy">
                                Pedido minimo:{' '}
                                {formatMoney(
                                    minimumOrderValue,
                                    safeCart.currency,
                                )}
                                .
                            </p>
                        )}
                        {minimumReached && !hasShipping && (
                            <p className="mt-4 rounded-lg bg-yellow/25 p-3 text-xs font-bold text-navy">
                                É obrigatório calcular e selecionar uma opção de
                                frete antes de finalizar.
                            </p>
                        )}
                        {canCheckout ? (
                            <Link
                                href="/checkout"
                                className="mt-5 block w-full rounded-md bg-yellow py-3 text-center font-black text-navy"
                            >
                                Finalizar compra
                            </Link>
                        ) : (
                            <span className="mt-5 block w-full cursor-not-allowed rounded-md bg-slate-200 py-3 text-center font-black text-slate-500">
                                {minimumReached
                                    ? 'Selecione o frete para finalizar'
                                    : 'Valor minimo nao atingido'}
                            </span>
                        )}
                        {whatsappNumber && (
                            <a
                                href={`https://wa.me/${whatsappNumber}?text=${whatsappText}`}
                                target="_blank"
                                rel="noreferrer"
                                className="mt-3 flex w-full items-center justify-center gap-2 rounded-md bg-green-600 py-3 text-center font-black text-white"
                            >
                                <MessageCircle className="h-4 w-4" /> Enviar
                                pelo WhatsApp
                            </a>
                        )}
                        <Link
                            href="/produtos"
                            className="mt-3 block text-center text-sm font-semibold text-navy hover:underline"
                        >
                            Continuar comprando
                        </Link>
                        <div className="mt-5 flex items-center gap-2 border-t border-border pt-4 text-xs text-text-muted">
                            <ShieldCheck className="h-4 w-4 text-navy" /> Preços
                            calculados com segurança no servidor
                        </div>
                    </aside>
                </main>
            )}
        </div>
    );
}
