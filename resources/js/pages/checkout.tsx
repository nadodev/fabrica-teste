import { Link, router, useForm } from "@inertiajs/react";
import { ArrowLeft, CheckCircle2, ShieldCheck } from "lucide-react";
import type { FormEvent, ReactNode } from "react";
import { createIdempotencyKey } from "@/lib/idempotency-key";
import { formatMoney } from "@/modules/catalog/domain/product";

type CartItem = {
  cartItemKey: string;
  sku: string;
  name: string;
  unitPriceAmount: number;
  priceCurrency: string;
  quantity: number;
  subtotalAmount: number;
  imageUrl: string | null;
  variationLabel: string | null;
};

type Cart = {
  items: CartItem[];
  subtotalAmount: number;
  discountAmount: number;
  totalAmount: number;
  currency: string;
  coupon: { code: string; description: string; discountAmount: number } | null;
};

const emptyCart: Cart = {
  items: [],
  subtotalAmount: 0,
  discountAmount: 0,
  totalAmount: 0,
  currency: "BRL",
  coupon: null,
};

export default function Checkout({ cart = emptyCart }: { cart?: Cart }) {
  const safeCart = { ...emptyCart, ...cart, items: Array.isArray(cart?.items) ? cart.items : [] };
  const form = useForm({
    customerName: "",
    customerEmail: "",
    customerPhone: "",
    customerDocument: "",
    shippingZip: "",
    shippingAddress: "",
    shippingNumber: "",
    shippingCity: "",
    shippingState: "",
    notes: "",
  });

  const checkoutError = (form.errors as Record<string, string>).checkout;

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post("/checkout", {
      headers: { "Idempotency-Key": createIdempotencyKey() },
    });
  };

  return (
    <div className="min-h-[70vh] bg-bg-soft">
      <header className="bg-navy text-white">
        <div className="mx-auto max-w-7xl px-4 py-8">
          <Link href="/carrinho" className="inline-flex items-center gap-2 text-sm font-bold text-white/75 hover:text-yellow">
            <ArrowLeft className="h-4 w-4" /> Voltar ao carrinho
          </Link>
          <h1 className="mt-3 font-display text-3xl font-black md:text-4xl">Finalizar compra</h1>
          <p className="mt-2 text-white/75">Preencha os dados para gerar o pedido. Pagamento sera combinado depois.</p>
        </div>
      </header>

      <main className="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-[1fr_380px]">
        <form onSubmit={submit} className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
          <h2 className="font-display text-xl font-black text-navy">Dados do cliente</h2>
          <div className="mt-5 grid gap-4 sm:grid-cols-2">
            <Field label="Nome completo" error={form.errors.customerName}><input className="input" value={form.data.customerName} onChange={(e) => form.setData("customerName", e.target.value)} /></Field>
            <Field label="E-mail" error={form.errors.customerEmail}><input type="email" className="input" value={form.data.customerEmail} onChange={(e) => form.setData("customerEmail", e.target.value)} /></Field>
            <Field label="Telefone / WhatsApp" error={form.errors.customerPhone}><input className="input" value={form.data.customerPhone} onChange={(e) => form.setData("customerPhone", e.target.value)} /></Field>
            <Field label="CPF/CNPJ" error={form.errors.customerDocument}><input className="input" value={form.data.customerDocument} onChange={(e) => form.setData("customerDocument", e.target.value)} /></Field>
          </div>

          <h2 className="mt-8 font-display text-xl font-black text-navy">Entrega</h2>
          <div className="mt-5 grid gap-4 sm:grid-cols-2">
            <Field label="CEP" error={form.errors.shippingZip}><input className="input" value={form.data.shippingZip} onChange={(e) => form.setData("shippingZip", e.target.value)} /></Field>
            <Field label="Endereco" error={form.errors.shippingAddress}><input className="input" value={form.data.shippingAddress} onChange={(e) => form.setData("shippingAddress", e.target.value)} /></Field>
            <Field label="Numero" error={form.errors.shippingNumber}><input className="input" value={form.data.shippingNumber} onChange={(e) => form.setData("shippingNumber", e.target.value)} /></Field>
            <Field label="Cidade" error={form.errors.shippingCity}><input className="input" value={form.data.shippingCity} onChange={(e) => form.setData("shippingCity", e.target.value)} /></Field>
            <Field label="Estado" error={form.errors.shippingState}><input className="input" value={form.data.shippingState} onChange={(e) => form.setData("shippingState", e.target.value)} /></Field>
          </div>
          <Field label="Observacoes" error={form.errors.notes}>
            <textarea rows={4} className="input" value={form.data.notes} onChange={(e) => form.setData("notes", e.target.value)} />
          </Field>

          {checkoutError && <div className="mt-4 rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-800">{checkoutError}</div>}

          <button disabled={form.processing} className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60">
            <CheckCircle2 className="h-5 w-5" /> {form.processing ? "Finalizando..." : "Gerar pedido"}
          </button>
        </form>

        <aside className="h-fit rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] lg:sticky lg:top-32">
          <h2 className="font-display text-lg font-black text-navy">Resumo</h2>
          <div className="mt-4 space-y-4">
            {safeCart.items.map((item) => (
              <div key={item.cartItemKey} className="flex gap-3 border-b border-border pb-4 last:border-0">
                <div className="flex-1">
                  <div className="font-bold text-navy">{item.name}</div>
                  {item.variationLabel && <div className="text-xs font-semibold text-text-muted">{item.variationLabel}</div>}
                  <div className="mt-1 text-xs text-text-muted">{item.quantity} x {formatMoney(item.unitPriceAmount, item.priceCurrency)}</div>
                </div>
                <div className="font-bold text-navy">{formatMoney(item.subtotalAmount, item.priceCurrency)}</div>
              </div>
            ))}
          </div>
          <div className="mt-5 space-y-3 border-t border-border pt-5">
            <div className="flex items-center justify-between text-sm">
              <span className="text-text-muted">Subtotal</span>
              <strong className="text-navy">{formatMoney(safeCart.subtotalAmount, safeCart.currency)}</strong>
            </div>
            {safeCart.discountAmount > 0 && (
              <div className="flex items-center justify-between text-sm">
                <span className="text-text-muted">Desconto {safeCart.coupon ? `(${safeCart.coupon.code})` : ""}</span>
                <strong className="text-green-700">- {formatMoney(safeCart.discountAmount, safeCart.currency)}</strong>
              </div>
            )}
          </div>
          <div className="mt-5 flex items-end justify-between border-t border-border pt-5">
            <span className="text-text-muted">Total</span>
            <strong className="font-display text-2xl text-navy">{formatMoney(safeCart.totalAmount, safeCart.currency)}</strong>
          </div>
          <div className="mt-5 flex items-center gap-2 rounded-lg bg-bg-soft p-3 text-xs text-text-muted">
            <ShieldCheck className="h-4 w-4 text-navy" /> Pedido sem pagamento online nesta etapa.
          </div>
        </aside>
      </main>
    </div>
  );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
  return (
    <label className="mt-4 block text-sm font-bold text-navy">
      {label}
      <div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:outline-none focus-within:[&_.input]:border-navy">{children}</div>
      {error && <span className="mt-1 block text-xs text-red-700">{error}</span>}
    </label>
  );
}
