import { Head, Link, router } from "@inertiajs/react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { createIdempotencyKey } from "@/lib/idempotency-key";
import { formatMoney } from "@/modules/catalog/domain/product";
import { AdminLayout } from "@/modules/admin/ui/admin-layout";

type Coupon = {
  id: string;
  code: string;
  description: string;
  discount_type: "percent" | "fixed";
  discount_value: number;
  starts_at: string | null;
  ends_at: string | null;
  is_active: boolean;
};

export default function CouponsIndex({ coupons = [] }: { coupons?: Coupon[] }) {
  const safeCoupons = Array.isArray(coupons) ? coupons : [];
  const remove = (coupon: Coupon) => {
    if (window.confirm(`Remover cupom ${coupon.code}?`)) {
      router.delete(`/admin/cupons/${coupon.id}`, {
        headers: { "Idempotency-Key": createIdempotencyKey() },
        preserveScroll: true,
      });
    }
  };

  return (
    <AdminLayout title="Cupons">
      <Head title="Cupons" />
      <div className="mb-5 flex items-center justify-between gap-3">
        <p className="text-sm text-text-muted">{safeCoupons.length} cupom(ns)</p>
        <Link href="/admin/cupons/novo" className="inline-flex items-center gap-2 rounded-lg bg-yellow px-4 py-2.5 text-sm font-black text-navy">
          <Plus className="h-4 w-4" /> Novo cupom
        </Link>
      </div>
      <div className="overflow-hidden rounded-xl border border-border bg-white">
        {safeCoupons.length === 0 ? (
          <div className="p-12 text-center text-text-muted">Nenhum cupom cadastrado.</div>
        ) : (
          <div className="divide-y divide-border">
            {safeCoupons.map((coupon) => (
              <div key={coupon.id} className="flex items-center gap-4 p-4">
                <div className="min-w-0 flex-1">
                  <div className="font-display text-lg font-black text-navy">{coupon.code}</div>
                  <div className="text-sm text-text-muted">{coupon.description || "Sem descricao"}</div>
                  <div className="mt-1 text-xs text-text-muted">
                    {coupon.discount_type === "percent" ? `${coupon.discount_value}%` : formatMoney(coupon.discount_value, "BRL")} - {coupon.is_active ? "Ativo" : "Inativo"}
                  </div>
                </div>
                <Link href={`/admin/cupons/${coupon.id}/editar`} className="rounded-md p-2 text-navy hover:bg-bg-soft" aria-label="Editar">
                  <Pencil className="h-4 w-4" />
                </Link>
                <button onClick={() => remove(coupon)} className="rounded-md p-2 text-red-700 hover:bg-red-50" aria-label="Remover">
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            ))}
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
