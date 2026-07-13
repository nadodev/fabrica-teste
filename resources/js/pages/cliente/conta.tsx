import { Head, router } from '@inertiajs/react';
import { LogOut, PackageCheck } from 'lucide-react';
import { formatMoney } from '@/modules/catalog/domain/product';

type Order = {
    id: string;
    number: string;
    status: string;
    checkoutType: string;
    totalAmount: number;
    currency: string;
    paymentMethod: string | null;
    paymentStatus: string | null;
    createdAt: string;
};

export default function CustomerAccount({ orders = [] }: { orders?: Order[] }) {
    const safeOrders = Array.isArray(orders) ? orders : [];

    return (
        <main className="mx-auto max-w-7xl px-4 py-10">
            <Head title="Minha conta" />
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="font-display text-3xl font-black text-navy">
                        Minha conta
                    </h1>
                    <p className="mt-1 text-sm text-text-muted">
                        Pedidos e orcamentos vinculados com seguranca a sua
                        conta.
                    </p>
                </div>
                <button
                    onClick={() => router.post('/sair')}
                    className="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-bold text-navy hover:bg-bg-soft"
                >
                    <LogOut className="h-4 w-4" /> Sair
                </button>
            </div>

            <section className="overflow-hidden rounded-xl border border-border bg-white shadow-[var(--shadow-soft)]">
                {safeOrders.length === 0 ? (
                    <div className="grid place-items-center px-4 py-16 text-center">
                        <PackageCheck className="h-10 w-10 text-navy" />
                        <h2 className="mt-4 font-display text-xl font-black text-navy">
                            Nenhum pedido ainda
                        </h2>
                        <p className="mt-1 text-sm text-text-muted">
                            Quando voce finalizar um carrinho, ele aparece aqui.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-bg-soft text-xs text-text-muted uppercase">
                                <tr>
                                    <th className="px-5 py-3">Pedido</th>
                                    <th className="px-5 py-3">Tipo</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3">Total</th>
                                    <th className="px-5 py-3">Pagamento</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {safeOrders.map((order) => (
                                    <tr key={order.id}>
                                        <td className="px-5 py-4">
                                            <div className="font-bold text-navy">
                                                {order.number}
                                            </div>
                                            <div className="text-xs text-text-muted">
                                                {new Date(
                                                    order.createdAt,
                                                ).toLocaleString('pt-BR')}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            {order.checkoutType === 'quote'
                                                ? 'Orcamento'
                                                : 'Pedido'}
                                        </td>
                                        <td className="px-5 py-4">
                                            {statusLabel(order.status)}
                                        </td>
                                        <td className="px-5 py-4 font-black text-navy">
                                            {formatMoney(
                                                order.totalAmount,
                                                order.currency,
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            {paymentLabel(order.paymentMethod)}
                                            <div className="text-xs text-text-muted">
                                                {paymentStatusLabel(
                                                    order.paymentStatus,
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </main>
    );
}

function statusLabel(status: string) {
    return (
        {
            quote_requested: 'Orcamento recebido',
            awaiting_payment: 'Aguardando pagamento',
            paid: 'Pago',
            processing: 'Em producao',
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
            credit_card: 'Cartao',
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
