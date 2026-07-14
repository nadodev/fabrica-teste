import { Head, Link, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import { formatMoney } from '@/modules/catalog/domain/product';

type Order = {
    id: string;
    number: string;
    customerName: string;
    customerEmail: string | null;
    customerPhone: string | null;
    status: string;
    checkoutType: string;
    subtotalAmount: number;
    discountAmount: number;
    shippingAmount: number;
    totalAmount: number;
    currency: string;
    couponCode: string | null;
    paymentMethod: string | null;
    paymentStatus: string | null;
    createdAt: string;
    allowedStatuses: Record<string, string>;
};

export default function OrdersIndex({
    orders = [],
    statuses = {},
}: {
    orders?: Order[];
    statuses?: Record<string, string>;
}) {
    const safeOrders = Array.isArray(orders) ? orders : [];

    const updateStatus = (order: Order, status: string) => {
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
        <AdminLayout title="Pedidos">
            <Head title="Pedidos" />
            <div className="mb-5 text-sm text-text-muted">
                {safeOrders.length} pedido(s)
            </div>
            <div className="overflow-hidden rounded-xl border border-border bg-white">
                {safeOrders.length === 0 ? (
                    <div className="p-12 text-center text-text-muted">
                        Nenhum pedido recebido ainda.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-bg-soft text-xs text-text-muted uppercase">
                                <tr>
                                    <th className="px-5 py-3">Pedido</th>
                                    <th className="px-5 py-3">Cliente</th>
                                    <th className="px-5 py-3">Total</th>
                                    <th className="px-5 py-3">Pagamento</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3 text-right">
                                        Acoes
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {safeOrders.map((order) => (
                                    <tr key={order.id}>
                                        <td className="px-5 py-4">
                                            <div className="font-bold text-navy">
                                                {order.number}
                                            </div>
                                            <div className="text-xs font-bold text-navy/70">
                                                {order.checkoutType === 'quote'
                                                    ? 'Orcamento'
                                                    : 'Pedido'}
                                            </div>
                                            <div className="text-xs text-text-muted">
                                                {new Date(
                                                    order.createdAt,
                                                ).toLocaleString('pt-BR')}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="font-semibold text-navy">
                                                {order.customerName}
                                            </div>
                                            <div className="text-xs text-text-muted">
                                                {order.customerPhone ??
                                                    order.customerEmail}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="font-black text-navy">
                                                {formatMoney(
                                                    order.totalAmount,
                                                    order.currency,
                                                )}
                                            </div>
                                            {order.shippingAmount > 0 && (
                                                <div className="text-xs text-text-muted">
                                                    Frete{' '}
                                                    {formatMoney(
                                                        order.shippingAmount,
                                                        order.currency,
                                                    )}
                                                </div>
                                            )}
                                            {order.discountAmount > 0 && (
                                                <div className="text-xs text-green-700">
                                                    -
                                                    {formatMoney(
                                                        order.discountAmount,
                                                        order.currency,
                                                    )}{' '}
                                                    {order.couponCode}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="font-semibold text-navy">
                                                {paymentLabel(
                                                    order.paymentMethod,
                                                )}
                                            </div>
                                            <div className="text-xs text-text-muted">
                                                {paymentStatusLabel(
                                                    order.paymentStatus,
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            <select
                                                value={order.status}
                                                onChange={(event) =>
                                                    updateStatus(
                                                        order,
                                                        event.target.value,
                                                    )
                                                }
                                                className="rounded-md border border-border bg-white px-2 py-2 text-xs font-bold text-navy"
                                            >
                                                {Object.entries(
                                                    order.allowedStatuses ??
                                                        statuses,
                                                ).map(([value, label]) => (
                                                    <option
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {label}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <Link
                                                href={`/admin/pedidos/${order.id}`}
                                                className="inline-flex rounded-md p-2 text-navy hover:bg-bg-soft"
                                                aria-label={`Ver ${order.number}`}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function paymentLabel(method: string | null) {
    return (
        {
            pix: 'Pix',
            credit_card: 'Cartao',
            boleto: 'Boleto',
            combine: 'Combinar',
        }[method ?? ''] ?? 'Nao informado'
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
