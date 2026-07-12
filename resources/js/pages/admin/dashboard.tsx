import { Head, Link } from "@inertiajs/react";
import { AlertTriangle, Boxes, ClipboardList, DollarSign, ShoppingCart, TrendingUp } from "lucide-react";
import { AdminLayout } from "@/modules/admin/ui/admin-layout";
import { formatMoney } from "@/modules/catalog/domain/product";

type Props = {
  stats: {
    totalRevenue: number;
    orderCount: number;
    activeProducts: number;
    cartCount: number;
    averageTicket: number;
    lowStockCount: number;
  };
  recentOrders: { id: string; number: string; customerName: string; totalAmount: number; currency: string; status: string; createdAt: string }[];
  topProducts: { name: string; sku: string; quantity: number; totalAmount: number }[];
  lowStock: { name: string; sku: string; variation: string; stock: number; threshold: number }[];
};

export default function Dashboard({ stats, recentOrders, topProducts, lowStock }: Props) {
  return (
    <AdminLayout title="Dashboard">
      <Head title="Dashboard ecommerce" />

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Metric icon={DollarSign} label="Faturamento" value={formatMoney(stats.totalRevenue, "BRL")} hint="Pedidos finalizados" />
        <Metric icon={ClipboardList} label="Pedidos" value={String(stats.orderCount)} hint={`Ticket medio ${formatMoney(stats.averageTicket, "BRL")}`} />
        <Metric icon={Boxes} label="Produtos ativos" value={String(stats.activeProducts)} hint="Visiveis na loja" />
        <Metric icon={ShoppingCart} label="Carrinhos ativos" value={String(stats.cartCount)} hint="Clientes em compra" />
      </div>

      <div className="mt-6 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <section className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h2 className="font-display text-xl font-black text-navy">Pedidos recentes</h2>
              <p className="text-sm text-text-muted">Ultimas compras feitas no site</p>
            </div>
            <Link href="/admin/produtos" className="rounded-md border border-border px-3 py-2 text-sm font-bold text-navy hover:bg-bg-soft">Produtos</Link>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-border text-xs uppercase text-text-muted">
                <tr><th className="py-3">Pedido</th><th>Cliente</th><th>Total</th><th>Status</th></tr>
              </thead>
              <tbody className="divide-y divide-border">
                {recentOrders.length === 0 ? (
                  <tr><td colSpan={4} className="py-8 text-center text-text-muted">Nenhum pedido ainda.</td></tr>
                ) : recentOrders.map((order) => (
                  <tr key={order.id}>
                    <td className="py-3 font-bold text-navy">{order.number}</td>
                    <td>{order.customerName}</td>
                    <td>{formatMoney(order.totalAmount, order.currency)}</td>
                    <td><span className="rounded-full bg-yellow/30 px-2.5 py-1 text-xs font-black text-navy">{order.status}</span></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>

        <section className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
          <div className="mb-4 flex items-center gap-2">
            <AlertTriangle className="h-5 w-5 text-red-700" />
            <div>
              <h2 className="font-display text-xl font-black text-navy">Estoque baixo</h2>
              <p className="text-sm text-text-muted">{stats.lowStockCount} alerta(s) nas variacoes</p>
            </div>
          </div>
          <div className="space-y-3">
            {lowStock.length === 0 ? (
              <div className="rounded-lg bg-green-50 p-4 text-sm font-semibold text-green-800">Tudo certo com o estoque.</div>
            ) : lowStock.map((item) => (
              <div key={`${item.sku}-${item.variation}`} className="rounded-lg border border-red-100 bg-red-50 p-3">
                <div className="font-bold text-navy">{item.name}</div>
                <div className="text-sm text-red-800">{item.variation} - {item.stock} un. / alerta {item.threshold}</div>
              </div>
            ))}
          </div>
        </section>
      </div>

      <section className="mt-6 rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
        <div className="mb-4 flex items-center gap-2">
          <TrendingUp className="h-5 w-5 text-navy" />
          <h2 className="font-display text-xl font-black text-navy">Produtos mais vendidos</h2>
        </div>
        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
          {topProducts.length === 0 ? (
            <div className="text-sm text-text-muted">Ainda sem dados de venda.</div>
          ) : topProducts.map((product) => (
            <div key={product.sku} className="rounded-lg bg-bg-soft p-4">
              <div className="text-xs font-bold uppercase text-text-muted">{product.sku}</div>
              <div className="mt-1 font-bold text-navy">{product.name}</div>
              <div className="mt-3 text-sm text-text-muted">{product.quantity} vendidos</div>
            </div>
          ))}
        </div>
      </section>
    </AdminLayout>
  );
}

function Metric({ icon: Icon, label, value, hint }: { icon: typeof DollarSign; label: string; value: string; hint: string }) {
  return (
    <div className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
      <div className="flex items-center justify-between">
        <div className="grid h-11 w-11 place-items-center rounded-lg bg-yellow text-navy"><Icon className="h-5 w-5" /></div>
      </div>
      <div className="mt-4 text-sm font-bold text-text-muted">{label}</div>
      <div className="mt-1 font-display text-3xl font-black text-navy">{value}</div>
      <div className="mt-1 text-xs text-text-muted">{hint}</div>
    </div>
  );
}
