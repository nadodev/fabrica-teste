import { Link } from "@inertiajs/react";
import { ChevronRight, Trash2, ShieldCheck, MessageCircle, RefreshCcw, Tag } from "lucide-react";
import poloImg from "@/assets/prod-polo.jpg";
import calcaImg from "@/assets/prod-calca.jpg";
import { formatBRL } from "@/lib/store-data";

export default function CartPage() {
  const items = [
    {
      id: 1,
      name: "Camisa Polo Empresarial Azul-Marinho",
      color: "Azul-Marinho",
      image: poloImg,
      customization: "Bordado do logotipo no peito esquerdo",
      grade: [{ size: "P", qty: 5 }, { size: "M", qty: 10 }, { size: "G", qty: 8 }],
      unit: 69.9,
    },
    {
      id: 2,
      name: "Calça Profissional Unissex",
      color: "Preto",
      image: calcaImg,
      customization: null,
      grade: [{ size: "M", qty: 6 }, { size: "G", qty: 4 }],
      unit: 89.9,
    },
  ];

  const subtotal = items.reduce((s, i) => s + i.grade.reduce((n, g) => n + g.qty, 0) * i.unit, 0);
  const discount = 120;
  const shipping = 0;
  const total = subtotal - discount + shipping;

  return (
    <div className="bg-bg-soft">
      <div className="bg-navy text-white">
        <div className="mx-auto max-w-7xl px-4 py-8">
          <div className="flex items-center gap-2 text-xs text-white/70">
            <Link href="/" className="hover:text-yellow">Iní­cio</Link>
            <ChevronRight className="h-3 w-3" />
            <span className="text-yellow">Carrinho</span>
          </div>
          <h1 className="mt-2 font-display text-3xl font-black md:text-4xl">Seu carrinho</h1>
        </div>
      </div>

      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-[1fr_380px]">
        <div className="space-y-4">
          {items.map((it) => {
            const totalQty = it.grade.reduce((n, g) => n + g.qty, 0);
            return (
              <div key={it.id} className="grid gap-4 rounded-xl border border-border bg-white p-5 sm:grid-cols-[120px_1fr]">
                <div className="aspect-square overflow-hidden rounded-lg bg-bg-soft">
                  <img src={it.image} alt={it.name} loading="lazy" className="h-full w-full object-cover" />
                </div>
                <div className="min-w-0">
                  <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="min-w-0">
                      <h3 className="font-display font-bold text-navy">{it.name}</h3>
                      <div className="mt-1 text-sm text-text-muted">Cor: <span className="text-text-dark">{it.color}</span></div>
                      {it.customization && (
                        <div className="mt-1 inline-flex items-center gap-1 rounded-full bg-yellow/20 px-2 py-0.5 text-xs font-semibold text-navy">
                          {it.customization}
                        </div>
                      )}
                    </div>
                    <button aria-label="Remover" className="rounded-md p-2 text-text-muted hover:bg-bg-soft hover:text-navy">
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                  <div className="mt-3 rounded-lg bg-bg-soft p-3">
                    <div className="mb-2 text-xs font-bold uppercase tracking-wider text-navy">Grade de tamanhos</div>
                    <div className="flex flex-wrap gap-2">
                      {it.grade.map((g) => (
                        <div key={g.size} className="flex items-center gap-2 rounded-md border border-border bg-white px-3 py-1.5 text-sm">
                          <span className="font-bold text-navy">{g.size}</span>
                          <input type="number" defaultValue={g.qty} className="w-14 rounded border border-border bg-bg-soft px-2 py-0.5 text-center text-sm" />
                          <span className="text-xs text-text-muted">un</span>
                        </div>
                      ))}
                      <button className="rounded-md border border-dashed border-navy px-3 py-1.5 text-xs font-semibold text-navy hover:bg-navy hover:text-white transition">
                        + Adicionar tamanho
                      </button>
                    </div>
                  </div>
                  <div className="mt-3 flex items-center justify-between text-sm">
                    <div className="text-text-muted">{totalQty} unidade{totalQty > 1 ? `s` : ``} — {formatBRL(it.unit)}</div>
                    <div className="font-display text-lg font-black text-navy">{formatBRL(totalQty * it.unit)}</div>
                  </div>
                </div>
              </div>
            );
          })}

          <div className="rounded-xl border border-border bg-white p-5">
            <label className="text-sm font-semibold text-navy">Observações sobre o pedido</label>
            <textarea
              rows={3}
              placeholder="Ex.: gostaria do logotipo bordado no lado esquerdo do peito."
              className="mt-2 w-full rounded-lg border border-border bg-bg-soft p-3 text-sm outline-none focus:border-navy focus:bg-white"
            />
          </div>
        </div>

        {/* Summary */}
        <aside className="lg:sticky lg:top-32 h-fit space-y-4">
          <div className="rounded-xl border border-border bg-white p-6 shadow-[var(--shadow-soft)]">
            <h2 className="font-display text-lg font-bold text-navy">Resumo do pedido</h2>
            <dl className="mt-4 space-y-3 text-sm">
              <Row label="Subtotal" value={formatBRL(subtotal)} />
              <Row label="Desconto" value={`- ${formatBRL(discount)}`} accent />
              <Row label="Frete" value={shipping === 0 ? "A calcular" : formatBRL(shipping)} />
            </dl>
            <div className="my-4 h-px bg-border" />
            <div className="flex items-end justify-between">
              <div className="text-sm text-text-muted">Total</div>
              <div className="font-display text-2xl font-black text-navy">{formatBRL(total)}</div>
            </div>

            <div className="mt-4 flex overflow-hidden rounded-md border border-border">
              <div className="flex items-center gap-2 bg-bg-soft px-3 text-text-muted"><Tag className="h-4 w-4" /></div>
              <input placeholder="Cupom de desconto" className="flex-1 bg-white px-3 py-2 text-sm outline-none" />
              <button className="bg-navy px-4 text-sm font-semibold text-white">Aplicar</button>
            </div>

            <button className="mt-5 w-full rounded-md bg-yellow py-3 font-bold text-navy hover:brightness-95 transition">
              Finalizar compra
            </button>
            <button className="mt-2 w-full rounded-md border-2 border-navy py-3 font-bold text-navy hover:bg-navy hover:text-white transition">
              Transformar em orçamento
            </button>
            <Link href="/produtos" className="mt-3 block text-center text-sm font-semibold text-navy hover:underline">
              Continuar comprando
            </Link>
          </div>

          <div className="rounded-xl border border-border bg-white p-5 text-sm">
            <div className="mb-3 font-semibold text-navy">Compra segura</div>
            <ul className="space-y-2 text-text-muted">
              <li className="flex items-center gap-2"><ShieldCheck className="h-4 w-4 text-navy" /> Pagamento protegido</li>
              <li className="flex items-center gap-2"><RefreshCcw className="h-4 w-4 text-navy" /> Polí­tica de troca em 7 dias</li>
              <li className="flex items-center gap-2"><MessageCircle className="h-4 w-4 text-navy" /> Atendimento pelo WhatsApp</li>
            </ul>
          </div>
        </aside>
      </div>
    </div>
  );
}

function Row({ label, value, accent }: { label: string; value: string; accent?: boolean }) {
  return (
    <div className="flex justify-between">
      <dt className="text-text-muted">{label}</dt>
      <dd className={accent ? "font-semibold text-navy" : "text-text-dark"}>{value}</dd>
    </div>
  );
}