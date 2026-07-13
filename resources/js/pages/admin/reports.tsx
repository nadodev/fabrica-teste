import { Head, router } from '@inertiajs/react';
import { AlertTriangle, BarChart3, ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import { formatMoney } from '@/modules/catalog/domain/product';

type Props = {
    filters: { from: string; to: string };
    summary: {
        revenue: number;
        ordersCount: number;
        averageTicket: number;
        abandonedCarts: number;
        criticalStock: number;
    };
    salesByDay: { date: string; orders_count: number; total_amount: number }[];
    topProducts: {
        name: string;
        sku: string;
        quantity: number;
        total_amount: number;
    }[];
    coupons: {
        coupon_code: string;
        uses_count: number;
        discount_amount: number;
        total_amount: number;
    }[];
    abandonedCarts: {
        id: string;
        updated_at: string;
        items_count: number;
        units_count: number;
    }[];
    criticalStock: {
        name: string;
        sku: string;
        variation: string;
        stock: number;
        threshold: number;
    }[];
};

export default function Reports({
    filters,
    summary,
    salesByDay = [],
    topProducts = [],
    coupons = [],
    abandonedCarts = [],
    criticalStock = [],
}: Props) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const apply = () => router.visit(`/admin/relatorios?from=${from}&to=${to}`);

    return (
        <AdminLayout title="Relatorios">
            <Head title="Relatorios" />
            <div className="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-border bg-white p-4">
                <label className="text-sm font-bold text-navy">
                    Inicio
                    <input
                        type="date"
                        value={from}
                        onChange={(e) => setFrom(e.target.value)}
                        className="mt-1 block rounded-lg border border-border px-3 py-2"
                    />
                </label>
                <label className="text-sm font-bold text-navy">
                    Fim
                    <input
                        type="date"
                        value={to}
                        onChange={(e) => setTo(e.target.value)}
                        className="mt-1 block rounded-lg border border-border px-3 py-2"
                    />
                </label>
                <button
                    onClick={apply}
                    className="rounded-lg bg-yellow px-5 py-2.5 font-black text-navy"
                >
                    Filtrar
                </button>
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <Metric
                    icon={BarChart3}
                    label="Vendas"
                    value={formatMoney(summary.revenue, 'BRL')}
                />
                <Metric
                    icon={ShoppingCart}
                    label="Pedidos"
                    value={String(summary.ordersCount)}
                />
                <Metric
                    icon={BarChart3}
                    label="Ticket medio"
                    value={formatMoney(summary.averageTicket, 'BRL')}
                />
                <Metric
                    icon={ShoppingCart}
                    label="Carrinhos abandonados"
                    value={String(summary.abandonedCarts)}
                />
                <Metric
                    icon={AlertTriangle}
                    label="Estoque critico"
                    value={String(summary.criticalStock)}
                />
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-2">
                <Panel title="Vendas por periodo">
                    {salesByDay.map((row) => (
                        <Row
                            key={row.date}
                            label={`${row.date} - ${row.orders_count} pedido(s)`}
                            value={formatMoney(row.total_amount, 'BRL')}
                        />
                    ))}
                </Panel>
                <Panel title="Produtos mais vendidos">
                    {topProducts.map((row) => (
                        <Row
                            key={row.sku}
                            label={`${row.name} (${row.quantity})`}
                            value={formatMoney(row.total_amount, 'BRL')}
                        />
                    ))}
                </Panel>
                <Panel title="Cupons usados">
                    {coupons.length === 0 ? (
                        <Empty />
                    ) : (
                        coupons.map((row) => (
                            <Row
                                key={row.coupon_code}
                                label={`${row.coupon_code} - ${row.uses_count} uso(s)`}
                                value={`-${formatMoney(row.discount_amount, 'BRL')}`}
                            />
                        ))
                    )}
                </Panel>
                <Panel title="Carrinhos abandonados">
                    {abandonedCarts.length === 0 ? (
                        <Empty />
                    ) : (
                        abandonedCarts.map((row) => (
                            <Row
                                key={row.id}
                                label={`${row.units_count} unidade(s) - ${new Date(row.updated_at).toLocaleString('pt-BR')}`}
                                value={`${row.items_count} item(ns)`}
                            />
                        ))
                    )}
                </Panel>
                <Panel title="Estoque critico">
                    {criticalStock.length === 0 ? (
                        <Empty />
                    ) : (
                        criticalStock.map((row) => (
                            <Row
                                key={`${row.sku}-${row.variation}`}
                                label={`${row.name} - ${row.variation}`}
                                value={`${row.stock}/${row.threshold}`}
                                danger
                            />
                        ))
                    )}
                </Panel>
            </div>
        </AdminLayout>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof BarChart3;
    label: string;
    value: string;
}) {
    return (
        <div className="rounded-xl border border-border bg-white p-5">
            <Icon className="h-5 w-5 text-yellow" />
            <div className="mt-3 text-xs font-bold text-text-muted uppercase">
                {label}
            </div>
            <div className="mt-1 font-display text-2xl font-black text-navy">
                {value}
            </div>
        </div>
    );
}

function Panel({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-xl border border-border bg-white p-5">
            <h2 className="font-display text-lg font-black text-navy">
                {title}
            </h2>
            <div className="mt-4 divide-y divide-border">{children}</div>
        </section>
    );
}

function Row({
    label,
    value,
    danger = false,
}: {
    label: string;
    value: string;
    danger?: boolean;
}) {
    return (
        <div className="flex justify-between gap-4 py-3 text-sm">
            <span className="text-text-muted">{label}</span>
            <strong className={danger ? 'text-red-700' : 'text-navy'}>
                {value}
            </strong>
        </div>
    );
}

function Empty() {
    return (
        <div className="py-6 text-sm text-text-muted">
            Sem dados no periodo.
        </div>
    );
}
