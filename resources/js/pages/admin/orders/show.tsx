import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MapPin, Phone, User } from 'lucide-react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
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
    customerDocument: string | null;
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

export default function OrderShow({
    order,
    statuses = {},
}: {
    order: Order;
    statuses?: Record<string, string>;
}) {
    const items = Array.isArray(order.items) ? order.items : [];
    const updateStatus = (status: string) => {
        router.post(
            `/admin/pedidos/${order.id}/status`,
            { status },
            {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    return (
        <AdminLayout title={`Pedido ${order.number}`}>
            <Head title={`Pedido ${order.number}`} />
            <Link
                href="/admin/pedidos"
                className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline"
            >
                <ArrowLeft className="h-4 w-4" /> Voltar
            </Link>

            <div className="grid gap-6 xl:grid-cols-[1fr_380px]">
                <section className="space-y-5">
                    <div className="rounded-xl border border-border bg-white p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div className="text-xs text-text-muted">
                                    {new Date(order.createdAt).toLocaleString(
                                        'pt-BR',
                                    )}
                                </div>
                                <h2 className="font-display text-2xl font-black text-navy">
                                    {order.number}
                                </h2>
                                <div className="mt-1 text-sm font-bold text-text-muted">
                                    {order.checkoutType === 'quote'
                                        ? 'Orcamento'
                                        : 'Pedido'}
                                </div>
                            </div>
                            <select
                                value={order.status}
                                onChange={(event) =>
                                    updateStatus(event.target.value)
                                }
                                className="rounded-md border border-border bg-white px-3 py-2 text-sm font-bold text-navy"
                            >
                                {Object.entries(statuses).map(
                                    ([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-white p-5">
                        <h3 className="mb-4 font-display text-lg font-black text-navy">
                            Itens do pedido
                        </h3>
                        <div className="divide-y divide-border">
                            {items.map((item) => (
                                <div
                                    key={`${item.productId}-${item.variationLabel ?? ''}`}
                                    className="flex justify-between gap-4 py-4"
                                >
                                    <div>
                                        <div className="text-xs font-bold tracking-wider text-text-muted uppercase">
                                            {item.sku}
                                        </div>
                                        <div className="font-bold text-navy">
                                            {item.name}
                                        </div>
                                        {item.variationLabel && (
                                            <div className="text-sm text-text-muted">
                                                {item.variationLabel}
                                            </div>
                                        )}
                                        {item.notes && (
                                            <div className="mt-1 rounded-md bg-bg-soft px-2 py-1 text-xs text-text-muted">
                                                Obs: {item.notes}
                                            </div>
                                        )}
                                        <div className="mt-1 text-xs text-text-muted">
                                            {item.quantity} x{' '}
                                            {formatMoney(
                                                item.unitPriceAmount,
                                                item.priceCurrency,
                                            )}
                                        </div>
                                    </div>
                                    <div className="font-display text-lg font-black text-navy">
                                        {formatMoney(
                                            item.subtotalAmount,
                                            item.priceCurrency,
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <aside className="space-y-5">
                    <InfoCard icon={User} title="Cliente">
                        <p className="font-bold text-navy">
                            {order.customerName ?? 'Nao informado'}
                        </p>
                        <p>{order.customerEmail}</p>
                        <p>{order.customerDocument}</p>
                    </InfoCard>
                    <InfoCard icon={Phone} title="Contato">
                        <p>{order.customerPhone ?? 'Nao informado'}</p>
                    </InfoCard>
                    <InfoCard icon={MapPin} title="Entrega">
                        <p className="font-bold text-navy">
                            {order.deliveryMethod === 'pickup'
                                ? 'Retirada na loja'
                                : 'Envio para endereco'}
                        </p>
                        <p>
                            {order.shippingAddress}, {order.shippingNumber}
                        </p>
                        <p>
                            {order.shippingCity} - {order.shippingState}
                        </p>
                        <p>{order.shippingZip}</p>
                        {order.shippingService && (
                            <p>
                                {order.shippingCompany} -{' '}
                                {order.shippingService}
                            </p>
                        )}
                        {order.shippingDeliveryTime ? (
                            <p>
                                Prazo: {order.shippingDeliveryTime} dias uteis
                            </p>
                        ) : null}
                    </InfoCard>
                    <InfoCard icon={Phone} title="Pagamento">
                        <p className="font-bold text-navy">
                            {paymentLabel(order.paymentMethod)}
                        </p>
                        <p>{paymentStatusLabel(order.paymentStatus)}</p>
                    </InfoCard>
                    <div className="rounded-xl border border-border bg-white p-5">
                        <h3 className="font-display text-lg font-black text-navy">
                            Resumo
                        </h3>
                        <div className="mt-4 space-y-3 text-sm">
                            <Row
                                label="Subtotal"
                                value={formatMoney(
                                    order.subtotalAmount,
                                    order.currency,
                                )}
                            />
                            {order.discountAmount > 0 && (
                                <Row
                                    label={`Desconto ${order.couponCode ?? ''}`}
                                    value={`- ${formatMoney(order.discountAmount, order.currency)}`}
                                    positive
                                />
                            )}
                            {order.shippingAmount > 0 && (
                                <Row
                                    label="Frete"
                                    value={formatMoney(
                                        order.shippingAmount,
                                        order.currency,
                                    )}
                                />
                            )}
                            <div className="flex items-end justify-between border-t border-border pt-4">
                                <span className="text-text-muted">Total</span>
                                <strong className="font-display text-2xl text-navy">
                                    {formatMoney(
                                        order.totalAmount,
                                        order.currency,
                                    )}
                                </strong>
                            </div>
                        </div>
                        {order.notes && (
                            <p className="mt-5 rounded-lg bg-bg-soft p-3 text-sm text-text-muted">
                                {order.notes}
                            </p>
                        )}
                    </div>
                </aside>
            </div>
        </AdminLayout>
    );
}

function InfoCard({
    icon: Icon,
    title,
    children,
}: {
    icon: typeof User;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <div className="rounded-xl border border-border bg-white p-5 text-sm text-text-muted">
            <div className="mb-3 flex items-center gap-2 font-display text-lg font-black text-navy">
                <Icon className="h-5 w-5" /> {title}
            </div>
            <div className="space-y-1">{children}</div>
        </div>
    );
}

function Row({
    label,
    value,
    positive = false,
}: {
    label: string;
    value: string;
    positive?: boolean;
}) {
    return (
        <div className="flex justify-between gap-3">
            <span className="text-text-muted">{label}</span>
            <strong className={positive ? 'text-green-700' : 'text-navy'}>
                {value}
            </strong>
        </div>
    );
}

function paymentLabel(method: string | null) {
    return (
        {
            pix: 'Pix',
            credit_card: 'Cartao de credito',
            boleto: 'Boleto',
            combine: 'Combinar depois',
        }[method ?? ''] ?? 'Nao informado'
    );
}

function paymentStatusLabel(status: string | null) {
    return (
        {
            pending: 'Pagamento pendente',
            paid: 'Pagamento aprovado',
            refused: 'Pagamento recusado',
            refunded: 'Estornado',
            cancelled: 'Cancelado',
        }[status ?? ''] ?? 'Pagamento pendente'
    );
}
