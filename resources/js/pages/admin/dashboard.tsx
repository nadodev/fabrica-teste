import { Head, Link } from '@inertiajs/react';
import type { DollarSign } from 'lucide-react';
import {
    AlertTriangle,
    BarChart3,
    Boxes,
    ClipboardList,
    FileText,
    ShoppingCart,
    TrendingUp,
} from 'lucide-react';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import { formatMoney } from '@/modules/catalog/domain/product';

type Props = {
    stats: {
        totalRevenue: number;
        todayRevenue: number;
        orderCount: number;
        pendingOrders: number;
        quoteCount: number;
        activeProducts: number;
        cartCount: number;
        averageTicket: number;
        lowStockCount: number;
    };
    recentOrders: {
        id: string;
        number: string;
        customerName: string;
        totalAmount: number;
        currency: string;
        status: string;
        checkoutType: string;
        createdAt: string;
    }[];
    topProducts: {
        name: string;
        sku: string;
        quantity: number;
        totalAmount: number;
    }[];
    lowStock: {
        name: string;
        sku: string;
        variation: string;
        stock: number;
        threshold: number;
    }[];
};

export default function Dashboard({
    stats,
    recentOrders = [],
    topProducts = [],
    lowStock = [],
}: Partial<Props>) {
    const s = {
        totalRevenue: 0,
        todayRevenue: 0,
        orderCount: 0,
        pendingOrders: 0,
        quoteCount: 0,
        activeProducts: 0,
        cartCount: 0,
        averageTicket: 0,
        lowStockCount: 0,
        ...stats,
    };
    const orders = Array.isArray(recentOrders) ? recentOrders : [];
    const products = Array.isArray(topProducts) ? topProducts : [];
    const stock = Array.isArray(lowStock) ? lowStock : [];

    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard ecommerce" />

            <section className="overflow-hidden rounded-2xl bg-navy text-white shadow-[var(--shadow-card)]">
                <div className="grid gap-6 p-6 lg:grid-cols-[1fr_360px]">
                    <div>
                        <div className="text-sm font-bold text-yellow">
                            Visao geral da loja
                        </div>
                        <h2 className="mt-2 font-display text-3xl font-black">
                            Operacao ecommerce
                        </h2>
                        <p className="mt-2 max-w-2xl text-white/70">
                            Pedidos, orcamentos, estoque e carrinhos em um
                            painel unico para acompanhar a loja sem procurar em
                            varias telas.
                        </p>
                        <div className="mt-6 grid gap-3 sm:grid-cols-3">
                            <HeroMetric
                                label="Hoje"
                                value={formatMoney(s.todayRevenue, 'BRL')}
                            />
                            <HeroMetric
                                label="Total"
                                value={formatMoney(s.totalRevenue, 'BRL')}
                            />
                            <HeroMetric
                                label="Ticket medio"
                                value={formatMoney(s.averageTicket, 'BRL')}
                            />
                        </div>
                    </div>
                    <div className="rounded-xl bg-white/10 p-5">
                        <div className="text-sm font-bold text-yellow">
                            Atencao
                        </div>
                        <div className="mt-4 space-y-3">
                            <QuickLine
                                label="Pendentes"
                                value={s.pendingOrders}
                            />
                            <QuickLine
                                label="Orcamentos"
                                value={s.quoteCount}
                            />
                            <QuickLine
                                label="Estoque baixo"
                                value={s.lowStockCount}
                            />
                        </div>
                    </div>
                </div>
            </section>

            <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Metric
                    icon={ClipboardList}
                    label="Pedidos"
                    value={String(s.orderCount)}
                    hint="Todos os pedidos"
                />
                <Metric
                    icon={FileText}
                    label="Orcamentos"
                    value={String(s.quoteCount)}
                    hint="Solicitacoes recebidas"
                />
                <Metric
                    icon={Boxes}
                    label="Produtos ativos"
                    value={String(s.activeProducts)}
                    hint="Visiveis na loja"
                />
                <Metric
                    icon={ShoppingCart}
                    label="Carrinhos ativos"
                    value={String(s.cartCount)}
                    hint="Clientes em compra"
                />
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <Panel
                    title="Pedidos recentes"
                    subtitle="Ultimas movimentacoes da loja"
                    action={
                        <Link
                            href="/admin/pedidos"
                            className="text-sm font-black text-navy hover:underline"
                        >
                            Ver pedidos
                        </Link>
                    }
                >
                    <div className="space-y-3">
                        {orders.length === 0 ? (
                            <Empty text="Nenhum pedido ainda." />
                        ) : (
                            orders.map((order) => (
                                <Link
                                    href={`/admin/pedidos/${order.id}`}
                                    key={order.id}
                                    className="grid gap-3 rounded-xl border border-border p-3 transition hover:border-navy sm:grid-cols-[1fr_auto]"
                                >
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <strong className="text-navy">
                                                {order.number}
                                            </strong>
                                            <span className="rounded-full bg-bg-soft px-2 py-0.5 text-[11px] font-black text-text-muted">
                                                {order.checkoutType === 'quote'
                                                    ? 'Orcamento'
                                                    : 'Pedido'}
                                            </span>
                                        </div>
                                        <div className="mt-1 text-sm text-text-muted">
                                            {order.customerName} -{' '}
                                            {new Date(
                                                order.createdAt,
                                            ).toLocaleString('pt-BR')}
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <div className="font-display text-lg font-black text-navy">
                                            {formatMoney(
                                                order.totalAmount,
                                                order.currency,
                                            )}
                                        </div>
                                        <div className="text-xs font-bold text-text-muted">
                                            {order.status}
                                        </div>
                                    </div>
                                </Link>
                            ))
                        )}
                    </div>
                </Panel>

                <Panel
                    title="Estoque critico"
                    subtitle={`${s.lowStockCount} alerta(s)`}
                    icon={AlertTriangle}
                >
                    <div className="space-y-3">
                        {stock.length === 0 ? (
                            <div className="rounded-lg bg-green-50 p-4 text-sm font-semibold text-green-800">
                                Tudo certo com o estoque.
                            </div>
                        ) : (
                            stock.map((item) => (
                                <div
                                    key={`${item.sku}-${item.variation}`}
                                    className="rounded-lg border border-red-100 bg-red-50 p-3"
                                >
                                    <div className="font-bold text-navy">
                                        {item.name}
                                    </div>
                                    <div className="text-sm text-red-800">
                                        {item.variation} - {item.stock} un. /
                                        alerta {item.threshold}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </Panel>
            </div>

            <Panel
                title="Produtos mais vendidos"
                subtitle="Ranking por quantidade"
                icon={TrendingUp}
                className="mt-6"
            >
                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    {products.length === 0 ? (
                        <Empty text="Ainda sem dados de venda." />
                    ) : (
                        products.map((product, index) => (
                            <div
                                key={product.sku}
                                className="rounded-xl border border-border bg-bg-soft p-4"
                            >
                                <div className="flex items-center justify-between">
                                    <div className="text-xs font-bold text-text-muted uppercase">
                                        {product.sku}
                                    </div>
                                    <span className="grid h-7 w-7 place-items-center rounded-full bg-yellow text-xs font-black text-navy">
                                        {index + 1}
                                    </span>
                                </div>
                                <div className="mt-2 font-bold text-navy">
                                    {product.name}
                                </div>
                                <div className="mt-3 text-sm text-text-muted">
                                    {product.quantity} vendidos
                                </div>
                                <div className="mt-1 text-sm font-black text-navy">
                                    {formatMoney(product.totalAmount, 'BRL')}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </Panel>
        </AdminLayout>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
    hint,
}: {
    icon: typeof DollarSign;
    label: string;
    value: string;
    hint: string;
}) {
    return (
        <div className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
            <div className="grid h-11 w-11 place-items-center rounded-lg bg-yellow text-navy">
                <Icon className="h-5 w-5" />
            </div>
            <div className="mt-4 text-sm font-bold text-text-muted">
                {label}
            </div>
            <div className="mt-1 font-display text-3xl font-black text-navy">
                {value}
            </div>
            <div className="mt-1 text-xs text-text-muted">{hint}</div>
        </div>
    );
}

function HeroMetric({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl bg-white/10 p-4">
            <div className="text-xs font-bold text-white/60 uppercase">
                {label}
            </div>
            <div className="mt-1 font-display text-2xl font-black">{value}</div>
        </div>
    );
}

function QuickLine({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex items-center justify-between rounded-lg bg-white/10 px-3 py-2">
            <span>{label}</span>
            <strong className="font-display text-xl text-yellow">
                {value}
            </strong>
        </div>
    );
}

function Panel({
    title,
    subtitle,
    action,
    icon: Icon = BarChart3,
    className = '',
    children,
}: {
    title: string;
    subtitle: string;
    action?: React.ReactNode;
    icon?: typeof BarChart3;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <section
            className={`rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] ${className}`}
        >
            <div className="mb-4 flex items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                    <Icon className="h-5 w-5 text-navy" />
                    <div>
                        <h2 className="font-display text-xl font-black text-navy">
                            {title}
                        </h2>
                        <p className="text-sm text-text-muted">{subtitle}</p>
                    </div>
                </div>
                {action}
            </div>
            {children}
        </section>
    );
}

function Empty({ text }: { text: string }) {
    return (
        <div className="rounded-lg bg-bg-soft p-6 text-center text-sm text-text-muted">
            {text}
        </div>
    );
}
