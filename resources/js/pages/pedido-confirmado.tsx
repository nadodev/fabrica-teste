import { Link } from "@inertiajs/react";
import { CheckCircle2, MessageCircle, PackageCheck } from "lucide-react";

export default function PedidoConfirmado({ orderNumber }: { orderNumber: string }) {
  return (
    <main className="min-h-[70vh] bg-bg-soft px-4 py-16">
      <div className="mx-auto max-w-2xl rounded-2xl border border-border bg-white p-8 text-center shadow-[var(--shadow-card)]">
        <div className="mx-auto grid h-20 w-20 place-items-center rounded-full bg-green-100 text-green-800">
          <CheckCircle2 className="h-10 w-10" />
        </div>
        <h1 className="mt-6 font-display text-3xl font-black text-navy">Pedido gerado com sucesso</h1>
        <p className="mt-3 text-text-muted">Recebemos seu pedido <strong className="text-navy">{orderNumber}</strong>. Nossa equipe vai confirmar disponibilidade, frete e proximo passo do pagamento.</p>
        <div className="mt-6 rounded-xl bg-bg-soft p-4 text-left">
          <div className="flex gap-3">
            <PackageCheck className="mt-0.5 h-5 w-5 text-navy" />
            <div>
              <div className="font-bold text-navy">Sem pagamento online por enquanto</div>
              <p className="mt-1 text-sm text-text-muted">O pedido ficou salvo no painel e o atendimento pode seguir pelo WhatsApp.</p>
            </div>
          </div>
        </div>
        <div className="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
          <a href="https://wa.me/5581979106667" className="inline-flex items-center justify-center gap-2 rounded-md bg-yellow px-6 py-3 font-black text-navy">
            <MessageCircle className="h-5 w-5" /> Chamar no WhatsApp
          </a>
          <Link href="/produtos" className="inline-flex items-center justify-center rounded-md border border-navy px-6 py-3 font-bold text-navy">
            Continuar comprando
          </Link>
        </div>
      </div>
    </main>
  );
}
