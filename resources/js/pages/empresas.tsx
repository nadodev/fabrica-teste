import { Link } from "@inertiajs/react";
import { ArrowRight, Building2, Users, Palette, Package } from "lucide-react";
import catEmpresa from "@/assets/cat-empresa.jpg";
import { products } from "@/lib/store-data";
import { ProductCard } from "@/components/product-card";

export default function EmpresasPage() {
  const items = products.filter((p) => p.segment === "Empresarial");
  return (
    <div>
      <section className="bg-navy text-white">
        <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 md:grid-cols-2 md:py-20">
          <div>
            <span className="inline-flex items-center gap-2 rounded-full bg-yellow/15 px-3 py-1 text-xs font-semibold text-yellow"><Building2 className="h-3.5 w-3.5" /> Para empresas</span>
            <h1 className="mt-4 font-display text-4xl font-black md:text-5xl">Uniformes corporativos que representam sua marca</h1>
            <p className="mt-4 max-w-lg text-white/80">Camisas, polos, calças e conjuntos com bordado, grade completa de tamanhos e atendimento consultivo.</p>
            <div className="mt-6 flex gap-3">
              <Link href="/orcamento" className="rounded-md bg-yellow px-5 py-3 font-bold text-navy hover:brightness-95">Solicitar orçamento</Link>
              <Link href="/produtos" className="rounded-md border border-white/40 px-5 py-3 font-semibold hover:bg-white hover:text-navy transition">Ver catálogo</Link>
            </div>
          </div>
          <div className="overflow-hidden rounded-2xl border-4 border-yellow">
            <img src="/1.jpg" alt="Equipe corporativa uniformizada" loading="lazy" className="h-full w-full object-cover" />
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-16">
        <div className="grid gap-6 md:grid-cols-4">
          {[
            { icon: Users, title: "Grade completa", desc: "Tamanhos do PP ao XGG e especiais." },
            { icon: Palette, title: "Cores e bordado", desc: "Match perfeito com sua identidade visual." },
            { icon: Package, title: "Reposição fácil", desc: "Compre novas peças quando precisar." },
            { icon: Building2, title: "Multi-departamento", desc: "Modelos diferentes por setor." },
          ].map((f) => (
            <div key={f.title} className="rounded-xl border border-border bg-white p-6">
              <div className="grid h-12 w-12 place-items-center rounded-lg bg-yellow"><f.icon className="h-5 w-5 text-navy" /></div>
              <div className="mt-4 font-display font-bold text-navy">{f.title}</div>
              <div className="text-sm text-text-muted">{f.desc}</div>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-bg-soft py-16">
        <div className="mx-auto max-w-7xl px-4">
          <h2 className="font-display text-3xl font-black text-navy">Peças em destaque para empresas</h2>
          <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {items.map((p) => <ProductCard key={p.id} p={p} />)}
          </div>
          <div className="mt-10 text-center">
            <Link href="/orcamento" className="inline-flex items-center gap-2 rounded-md bg-navy px-6 py-3 font-bold text-white hover:bg-navy-deep">Começar meu orçamento <ArrowRight className="h-4 w-4" /></Link>
          </div>
        </div>
      </section>
    </div>
  );
}