import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CircleDollarSign,
    MapPin,
    PackageCheck,
    ShoppingCart,
    UserRound,
} from 'lucide-react';
import { formatPhone, formatPostalCode } from '@/lib/input-masks';
import { formatMoney } from '@/modules/catalog/domain/product';

type OrderItem = {
    productId: string;
    sku: string;
    name: string;
    variationLabel: string | null;
    notes: string | null;
    unitPriceAmount: number;
    priceCurrency: string;
    quantity: number;
    subtotalAmount: number;
};

type Order = {
    id: string;
    number: string;
    status: string;
    checkoutType: string;
    customerName: string | null;
    customerEmail: string | null;
    customerPhone: string | null;
    shippingZip: string | null;
    shippingAddress: string | null;
    shippingNumber: string | null;
    shippingCity: string | null;
    shippingState: string | null;
    deliveryMethod: string;
    shippingService: string | null;
    shippingCompany: string | null;
    shippingAmount: number;
    shippingDeliveryTime: number | null;
    paymentMethod: string | null;
    paymentStatus: string | null;
    notes: string | null;
    subtotalAmount: number;
    discountAmount: number;
    totalAmount: number;
    currency: string;
    couponCode: string | null;
    createdAt: string;
    items: OrderItem[];
};

export default function CustomerOrderShow({ order }: { order: Order }) {
    const items = Array.isArray(order.items) ? order.items : [];
    const paymentFailed =
        order.checkoutType === 'payment' &&
        (order.paymentStatus === 'refused' || order.status === 'cancelled');

    return (
        <main className="mx-auto max-w-7xl px-4 py-10">
            <Head title={`Pedido ${order.number}`} />
            <Link
                href="/minha-conta"
                className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline"
            >
                <ArrowLeft className="h-4 w-4" /> Voltar para minha conta
            </Link>

            <div className="mb-6 flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
                <div>
                    <p className="text-xs text-text-muted">
                        {new Date(order.createdAt).toLocaleString('pt-BR')}
                    </p>
                    <h1 className="font-display text-3xl font-black text-navy">
                        {order.number}
                    </h1>
                    <p className="mt-1 text-sm font-bold text-text-muted">
                        {order.checkoutType === 'quote'
                            ? 'Orçamento'
                            : 'Pedido'}
                    </p>
                </div>
                <span className="rounded-full bg-bg-soft px-4 py-2 text-sm font-black text-navy">
                    {statusLabel(order.status)}
                </span>
            </div>

            {paymentFailed && (
                <section
                    role="alert"
                    className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 p-5"
                >
                    <div>
                        <h2 className="font-display text-lg font-black text-red-900">
                            Pagamento não concluído
                        </h2>
                        <p className="mt-1 text-sm text-red-800">
                            O carrinho foi preservado. Revise os itens e informe
                            outro cartão em uma nova finalização segura.
                        </p>
                    </div>
                    <Link
                        href="/carrinho"
                        className="inline-flex items-center gap-2 rounded-lg bg-navy px-4 py-2.5 text-sm font-black text-white"
                    >
                        <ShoppingCart className="h-4 w-4" /> Ir ao carrinho
                    </Link>
                </section>
            )}

            <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                <section className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
                    <h2 className="mb-4 flex items-center gap-2 font-display text-xl font-black text-navy">
                        <PackageCheck className="h-5 w-5" /> Itens
                    </h2>
                    <div className="divide-y divide-border">
                        {items.map((item, index) => (
                            <article
                                key={`${item.productId}-${item.sku}-${item.variationLabel ?? ''}-${index}`}
                                className="flex justify-between gap-4 py-4 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <p className="text-xs font-bold tracking-wide text-text-muted uppercase">
                                        {item.sku}
                                    </p>
                                    <h3 className="font-bold text-navy">
                                        {item.name}
                                    </h3>
                                    {item.variationLabel && (
                                        <p className="text-sm text-text-muted">
                                            {item.variationLabel}
                                        </p>
                                    )}
                                    {item.notes && (
                                        <p className="mt-1 text-xs text-text-muted">
                                            Observação: {item.notes}
                                        </p>
                                    )}
                                    <p className="mt-1 text-xs text-text-muted">
                                        {item.quantity} ×{' '}
                                        {formatMoney(
                                            item.unitPriceAmount,
                                            item.priceCurrency,
                                        )}
                                    </p>
                                </div>
                                <strong className="text-navy">
                                    {formatMoney(
                                        item.subtotalAmount,
                                        item.priceCurrency,
                                    )}
                                </strong>
                            </article>
                        ))}
                    </div>
                    {order.notes && (
                        <div className="mt-5 rounded-lg bg-bg-soft p-4 text-sm text-text-muted">
                            <strong className="text-navy">
                                Observações do pedido:
                            </strong>{' '}
                            {order.notes}
                        </div>
                    )}
                </section>

                <aside className="space-y-5">
                    <InfoCard
                        icon={<CircleDollarSign className="h-5 w-5" />}
                        title="Resumo"
                    >
                        <MoneyRow
                            label="Subtotal"
                            value={formatMoney(
                                order.subtotalAmount,
                                order.currency,
                            )}
                        />
                        {order.discountAmount > 0 && (
                            <MoneyRow
                                label={`Desconto${order.couponCode ? ` (${order.couponCode})` : ''}`}
                                value={`− ${formatMoney(order.discountAmount, order.currency)}`}
                            />
                        )}
                        <MoneyRow
                            label="Frete"
                            value={formatMoney(
                                order.shippingAmount,
                                order.currency,
                            )}
                        />
                        <div className="mt-3 flex justify-between border-t border-border pt-3 font-display text-lg font-black text-navy">
                            <span>Total</span>
                            <span>
                                {formatMoney(order.totalAmount, order.currency)}
                            </span>
                        </div>
                        <p className="mt-4 text-sm text-text-muted">
                            {paymentLabel(order.paymentMethod)} ·{' '}
                            {paymentStatusLabel(order.paymentStatus)}
                        </p>
                    </InfoCard>

                    <InfoCard
                        icon={<UserRound className="h-5 w-5" />}
                        title="Dados do pedido"
                    >
                        <p className="font-bold text-navy">
                            {order.customerName ?? 'Cliente'}
                        </p>
                        {order.customerEmail && <p>{order.customerEmail}</p>}
                        {order.customerPhone && (
                            <p>{formatPhone(order.customerPhone)}</p>
                        )}
                    </InfoCard>

                    <InfoCard
                        icon={<MapPin className="h-5 w-5" />}
                        title="Entrega"
                    >
                        <p className="font-bold text-navy">
                            {order.shippingAddress}, {order.shippingNumber}
                        </p>
                        <p>
                            {order.shippingCity}/{order.shippingState} ·{' '}
                            {formatPostalCode(order.shippingZip ?? '')}
                        </p>
                        {order.shippingService && (
                            <p className="mt-3">
                                {order.shippingCompany
                                    ? `${order.shippingCompany} · `
                                    : ''}
                                {order.shippingService}
                                {order.shippingDeliveryTime !== null
                                    ? ` · até ${order.shippingDeliveryTime} dias úteis`
                                    : ''}
                            </p>
                        )}
                    </InfoCard>
                </aside>
            </div>
        </main>
    );
}

function InfoCard({
    icon,
    title,
    children,
}: {
    icon: React.ReactNode;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
            <h2 className="mb-3 flex items-center gap-2 font-display text-lg font-black text-navy">
                {icon} {title}
            </h2>
            <div className="text-sm text-text-muted">{children}</div>
        </section>
    );
}

function MoneyRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4 py-1.5">
            <span>{label}</span>
            <strong className="text-navy">{value}</strong>
        </div>
    );
}

function statusLabel(status: string) {
    return (
        {
            quote_requested: 'Orçamento recebido',
            awaiting_payment: 'Aguardando pagamento',
            paid: 'Pago',
            processing: 'Em produção',
            shipped: 'Enviado',
            delivered: 'Entregue',
            cancelled: 'Cancelado',
            refunded: 'Reembolsado',
        }[status] ?? status
    );
}

function paymentLabel(method: string | null) {
    return (
        {
            pix: 'Pix',
            credit_card: 'Cartão',
            boleto: 'Boleto',
            combine: 'A combinar',
        }[method ?? ''] ?? 'A combinar'
    );
}

function paymentStatusLabel(status: string | null) {
    return (
        {
            pending: 'Pendente',
            paid: 'Aprovado',
            refused: 'Recusado',
            refunded: 'Estornado',
            cancelled: 'Cancelado',
        }[status ?? ''] ?? 'Pendente'
    );
}
