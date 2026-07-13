import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import { formatMoney } from '@/modules/catalog/domain/product';

type Customer = {
    name: string;
    email: string;
    phone: string | null;
    document: string | null;
    ordersCount: number;
    totalAmount: number;
};
type Order = {
    id: string;
    number: string;
    status: string;
    total_amount: number;
    currency: string;
    created_at: string;
};

export default function CustomerShow({
    customer,
    orders = [],
}: {
    customer: Customer;
    orders?: Order[];
}) {
    const rows = Array.isArray(orders) ? orders : [];

    return (
        <AdminLayout title={customer.name}>
            <Head title={customer.name} />
            <Link
                href="/admin/clientes"
                className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline"
            >
                <ArrowLeft className="h-4 w-4" /> Voltar
            </Link>
            <div className="mb-6 grid gap-4 md:grid-cols-4">
                <Card label="E-mail" value={customer.email} />
                <Card label="Telefone" value={customer.phone ?? '-'} />
                <Card label="Pedidos" value={String(customer.ordersCount)} />
                <Card
                    label="Total comprado"
                    value={formatMoney(customer.totalAmount, 'BRL')}
                />
            </div>
            <section className="rounded-xl border border-border bg-white p-5">
                <h2 className="font-display text-lg font-black text-navy">
                    Historico de pedidos
                </h2>
                <div className="mt-4 divide-y divide-border">
                    {rows.map((order) => (
                        <Link
                            key={order.id}
                            href={`/admin/pedidos/${order.id}`}
                            className="flex justify-between gap-4 py-3 text-sm hover:bg-bg-soft"
                        >
                            <span>
                                <strong className="text-navy">
                                    {order.number}
                                </strong>{' '}
                                - {order.status}
                            </span>
                            <span>
                                {formatMoney(
                                    order.total_amount,
                                    order.currency,
                                )}
                            </span>
                        </Link>
                    ))}
                </div>
            </section>
        </AdminLayout>
    );
}

function Card({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-border bg-white p-5">
            <div className="text-xs font-bold text-text-muted uppercase">
                {label}
            </div>
            <div className="mt-1 font-display text-xl font-black text-navy">
                {value}
            </div>
        </div>
    );
}
