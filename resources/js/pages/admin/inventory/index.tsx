import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type Stock = {
    id: string;
    name: string;
    sku: string;
    variationKey: string | null;
    onHand: number;
    reserved: number;
    available: number;
    lowStockThreshold: number;
    updatedAt: string | null;
};
type Movement = {
    id: number;
    name: string;
    sku: string;
    variationKey: string | null;
    type: string;
    quantity: number;
    reservedDelta: number;
    balanceAfter: number;
    reservedAfter: number | null;
    reference: string;
    createdAt: string;
};

export default function InventoryIndex({
    stocks = [],
    movements = [],
}: {
    stocks?: Stock[];
    movements?: Movement[];
}) {
    const rows = Array.isArray(stocks) ? stocks : [];
    const history = Array.isArray(movements) ? movements : [];
    const form = useForm({
        stock_id: rows[0]?.id ?? '',
        quantity: 1,
        reason: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/estoque/ajuste', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Estoque">
            <Head title="Estoque" />
            <form
                onSubmit={submit}
                className="mb-6 grid gap-3 rounded-xl border border-border bg-white p-4 md:grid-cols-[1fr_140px_1fr_auto]"
            >
                <select
                    value={form.data.stock_id}
                    onChange={(event) =>
                        form.setData('stock_id', event.target.value)
                    }
                    className="rounded-lg border border-border px-3 py-2.5"
                >
                    {rows.map((stock) => (
                        <option key={stock.id} value={stock.id}>
                            {stock.name} ({stock.sku})
                        </option>
                    ))}
                </select>
                <input
                    type="number"
                    value={form.data.quantity}
                    onChange={(event) =>
                        form.setData('quantity', Number(event.target.value))
                    }
                    className="rounded-lg border border-border px-3 py-2.5"
                    placeholder="+10 ou -3"
                />
                <input
                    value={form.data.reason}
                    onChange={(event) =>
                        form.setData('reason', event.target.value)
                    }
                    className="rounded-lg border border-border px-3 py-2.5"
                    placeholder="Motivo do ajuste"
                />
                <button
                    disabled={form.processing}
                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-yellow px-4 py-2.5 font-black text-navy"
                >
                    <Save className="h-4 w-4" /> Ajustar
                </button>
            </form>

            <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <section className="rounded-xl border border-border bg-white p-5">
                    <h2 className="font-display text-lg font-black text-navy">
                        Saldos
                    </h2>
                    <div className="mt-4 divide-y divide-border">
                        {rows.map((stock) => (
                            <div
                                key={stock.id}
                                className="flex justify-between gap-3 py-3"
                            >
                                <div>
                                    <div className="font-bold text-navy">
                                        {stock.name}
                                    </div>
                                    <div className="text-xs text-text-muted">
                                        {stock.sku}
                                    </div>
                                </div>
                                <div className="text-right text-sm">
                                    <strong>{stock.available}</strong>
                                    <div className="text-xs text-text-muted">
                                        {stock.onHand} fisico · {stock.reserved}{' '}
                                        reservado
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
                <section className="rounded-xl border border-border bg-white p-5">
                    <h2 className="font-display text-lg font-black text-navy">
                        Movimentacoes recentes
                    </h2>
                    <div className="mt-4 divide-y divide-border">
                        {history.map((movement) => (
                            <div
                                key={movement.id}
                                className="grid gap-2 py-3 text-sm md:grid-cols-[1fr_100px_100px]"
                            >
                                <div>
                                    <div className="font-bold text-navy">
                                        {movement.name}
                                    </div>
                                    <div className="text-xs text-text-muted">
                                        {movement.type} - {movement.reference}
                                    </div>
                                </div>
                                <div
                                    className={
                                        movement.quantity >= 0
                                            ? 'font-black text-green-700'
                                            : 'font-black text-red-700'
                                    }
                                >
                                    {movement.quantity > 0 ? '+' : ''}
                                    {movement.quantity}
                                </div>
                                <div className="text-text-muted">
                                    Fisico {movement.balanceAfter}
                                    {movement.reservedAfter !== null && (
                                        <div className="text-xs">
                                            Reservado {movement.reservedAfter} (
                                            {movement.reservedDelta > 0
                                                ? '+'
                                                : ''}
                                            {movement.reservedDelta})
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}
