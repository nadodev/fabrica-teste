import { Head, Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import { formatMoney } from '@/modules/catalog/domain/product';

type Customer = {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    orders_count: number;
    total_amount: number;
    last_order_at: string;
};

export default function CustomersIndex({
    customers = [],
}: {
    customers?: Customer[];
}) {
    const rows = Array.isArray(customers) ? customers : [];

    return (
        <AdminLayout title="Clientes">
            <Head title="Clientes" />
            <div className="overflow-hidden rounded-xl border border-border bg-white">
                {rows.length === 0 ? (
                    <div className="p-12 text-center text-text-muted">
                        Nenhum cliente ainda.
                    </div>
                ) : (
                    <div className="divide-y divide-border">
                        {rows.map((customer) => (
                            <div
                                key={customer.email}
                                className="grid gap-3 p-4 md:grid-cols-[1fr_160px_160px_auto] md:items-center"
                            >
                                <div>
                                    <div className="font-display text-lg font-black text-navy">
                                        {customer.name}
                                    </div>
                                    <div className="text-sm text-text-muted">
                                        {customer.email}{' '}
                                        {customer.phone
                                            ? `- ${customer.phone}`
                                            : ''}
                                    </div>
                                </div>
                                <div className="text-sm text-text-muted">
                                    {customer.orders_count} pedido(s)
                                </div>
                                <div className="font-black text-navy">
                                    {formatMoney(customer.total_amount, 'BRL')}
                                </div>
                                <Link
                                    href={`/admin/clientes/${encodeURIComponent(customer.email)}`}
                                    className="rounded-md p-2 text-navy hover:bg-bg-soft"
                                    aria-label="Ver cliente"
                                >
                                    <Eye className="h-4 w-4" />
                                </Link>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
