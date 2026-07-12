import type { ReactNode } from 'react';
import { MessageCircle, Mail, Phone } from "lucide-react";

const inputCls =
  "w-full rounded-lg border border-border bg-bg-soft px-3 py-2.5 text-sm outline-none focus:border-navy focus:bg-white transition-colors";

export default function OrcamentoPage() {
  return (
    <div className="bg-bg-soft">
      <section className="bg-navy text-white">
        <div className="mx-auto max-w-7xl px-4 py-14">
          <h1 className="font-display text-4xl font-black md:text-5xl">Solicitar orçamento</h1>
          <p className="mt-3 max-w-2xl text-white/80">
            Conte sobre seu pedido — modelo, quantidade, cores e personalização. Respondemos em até 24h.
          </p>
        </div>
      </section>

      <section className="mx-auto grid max-w-7xl gap-8 px-4 py-12 lg:grid-cols-[1fr_360px]">
        <form className="grid gap-4 rounded-2xl border border-border bg-white p-6 shadow-[var(--shadow-soft)]">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nome"><input className={inputCls} placeholder="Seu nome" /></Field>
            <Field label="Empresa / Instituição"><input className={inputCls} placeholder="Ex.: Escola Vila Nova" /></Field>
            <Field label="E-mail"><input type="email" className={inputCls} placeholder="voce@email.com" /></Field>
            <Field label="WhatsApp"><input className={inputCls} placeholder="(00) 00000-0000" /></Field>
          </div>
          <Field label="Segmento">
            <select className={inputCls}>
              <option>Empresarial</option>
              <option>Escolar</option>
              <option>Profissional</option>
              <option>Industrial</option>
              <option>Eventos</option>
            </select>
          </Field>
          <Field label="Quantidade estimada"><input className={inputCls} placeholder="Ex.: 80 peças" /></Field>
          <Field label="Detalhes do pedido">
            <textarea rows={5} className={inputCls} placeholder="Modelos, cores, tamanhos, personalização (bordado, silk), prazo desejado…" />
          </Field>
          <button type="button" className="mt-2 w-fit rounded-md bg-yellow px-6 py-3 font-bold text-navy hover:brightness-95 transition">
            Enviar solicitação
          </button>
        </form>

        <aside className="space-y-4">
          <div className="rounded-2xl bg-navy p-6 text-white">
            <div className="font-display text-lg font-bold text-yellow">Fale direto com a equipe</div>
            <ul className="mt-4 space-y-3 text-sm">
              <li className="flex items-center gap-2"><MessageCircle className="h-4 w-4 text-yellow" /> WhatsApp: (00) 00000-0000</li>
              <li className="flex items-center gap-2"><Phone className="h-4 w-4 text-yellow" /> Telefone: (00) 0000-0000</li>
              <li className="flex items-center gap-2"><Mail className="h-4 w-4 text-yellow" /> contato@fardaplus.com.br</li>
            </ul>
          </div>
          <div className="rounded-2xl border border-border bg-white p-6 text-sm text-text-muted">
            <div className="mb-2 font-semibold text-navy">O que incluir no seu pedido?</div>
            <ul className="list-disc space-y-1 pl-4">
              <li>Grade de tamanhos por peça</li>
              <li>Cores e tecido preferido</li>
              <li>Tipo de personalização (bordado / silk)</li>
              <li>Data limite para entrega</li>
            </ul>
          </div>
        </aside>
      </section>
    </div>
  );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="grid gap-1.5">
      <span className="text-xs font-semibold text-navy">{label}</span>
      {children}
    </label>
  );
}