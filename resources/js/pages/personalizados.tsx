import { Link } from "@inertiajs/react";
import { Sparkles, Palette, Ruler, ArrowRight } from "lucide-react";
import catPerso from "@/assets/cat-personalizado.jpg";

export default function PersonalizadosPage() {
  return (
    <div>
      <section className="bg-yellow-soft py-16 md:py-20">
        <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 md:grid-cols-2">
          <div>
            <span className="inline-block rounded-full bg-navy px-3 py-1 text-xs font-semibold text-yellow">Personalização premium</span>
            <h1 className="mt-4 font-display text-4xl font-black text-navy md:text-5xl">Personalize os uniformes da sua equipe</h1>
            <p className="mt-4 max-w-lg text-text-dark/80">Bordado computadorizado, silk screen e transfer premium — combinamos técnicas para o acabamento ideal em cada peça.</p>
            <Link href="/orcamento" className="mt-6 inline-flex items-center gap-2 rounded-md bg-navy px-6 py-3 font-bold text-white hover:bg-navy-deep">Começar meu orçamento <ArrowRight className="h-4 w-4" /></Link>
          </div>
          <div className="overflow-hidden rounded-2xl border-4 border-navy">
            <img src={catPerso} alt="Bordado personalizado" loading="lazy" className="h-full w-full object-cover" />
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-16">
        <div className="grid gap-6 md:grid-cols-3">
          {[
            { n: "01", icon: Palette, title: "Escolha o modelo", desc: "Peça, cor, tecido e detalhes." },
            { n: "02", icon: Sparkles, title: "Envie sua marca", desc: "Bordado, silk ou transfer." },
            { n: "03", icon: Ruler, title: "Receba o orçamento", desc: "Com grade e prazo de produção." },
          ].map((s) => (
            <div key={s.n} className="rounded-xl border border-border bg-white p-6">
              <div className="font-display text-xs font-black text-yellow">{s.n}</div>
              <div className="mt-2 grid h-12 w-12 place-items-center rounded-lg bg-navy"><s.icon className="h-5 w-5 text-yellow" /></div>
              <div className="mt-4 font-display text-lg font-bold text-navy">{s.title}</div>
              <div className="text-sm text-text-muted">{s.desc}</div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}