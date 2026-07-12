import { Link } from "@inertiajs/react";
import { useEffect, useState } from "react";
import type { LucideIcon } from "lucide-react";
import {
  ArrowRight,
  Award,
  Building2,
  CheckCircle2,
  CreditCard,
  Factory,
  MapPin,
  MessageCircle,
  PackageCheck,
  Phone,
  Scissors,
  ShieldCheck,
  Shirt,
  ShoppingCart,
  Sparkles,
  Store,
  Timer,
  Truck,
} from "lucide-react";
import heroImg from "@/assets/hero-team.jpg";
import catEmpresa from "@/assets/cat-empresa.jpg";
import catEscola from "@/assets/cat-escola.jpg";
import catProf from "@/assets/cat-profissional.jpg";
import catPerso from "@/assets/cat-personalizado.jpg";
import { products } from "@/lib/store-data";
import { ProductCard } from "@/components/product-card";

export default function Index() {
  return (
    <div className="bg-bg-soft">
      <Hero />
      <FeaturedProducts />
      <ProductLines />
      <CommerceBenefits />
      <Stores />
      <Technology />
      <History />
      <QuoteCTA />
    </div>
  );
}

function Hero() {
  const featuredProducts = products.slice(0, 3);
  const [activeIndex, setActiveIndex] = useState(0);
  const activeProduct = featuredProducts[activeIndex];

  useEffect(() => {
    const timer = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % featuredProducts.length);
    }, 4500);

    return () => window.clearInterval(timer);
  }, [featuredProducts.length]);

  return (
    <section className="bg-bg-soft py-3 md:py-5">
      <div className="relative min-h-[390px] w-full overflow-hidden bg-navy text-white shadow-[var(--shadow-card)] sm:min-h-[350px] md:min-h-[300px]">
        <img src={heroImg} alt="" className="absolute inset-0 h-full w-full object-cover opacity-35" />
        <div className="absolute inset-0 bg-gradient-to-b from-navy via-navy/92 to-navy/72 md:bg-gradient-to-r md:from-navy md:via-navy/90 md:to-navy/30" />

        <div className="relative mx-auto flex min-h-[390px] max-w-7xl items-center px-4 py-7 sm:min-h-[350px] md:min-h-[300px] md:py-8">
          <article className="grid w-full items-center gap-5 md:grid-cols-[1fr_310px_210px]">
            <div className="max-w-2xl">
              <div className="inline-flex rounded-full bg-yellow px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-navy md:text-xs">
                {activeIndex === 0 ? "Oferta da loja" : "Destaque da vitrine"}
              </div>
              <h1 className="mt-3 font-display text-2xl font-black leading-tight sm:text-3xl md:mt-4 md:text-5xl">
                {activeProduct.name}
              </h1>
              <p className="mt-2 max-w-xl text-sm leading-6 text-white/78 md:mt-3 md:text-base">
                Uniforme profissional com acabamento reforçado, variação de cores e compra pelo carrinho.
              </p>
              <div className="mt-4 flex items-center gap-3 md:hidden">
                <img src={activeProduct.image} alt={activeProduct.name} className="h-24 w-24 rounded-xl border-2 border-white bg-white object-cover" />
                <div>
                  <div className="text-xs text-white/70">a partir de</div>
                  <div className="font-display text-2xl font-black text-yellow">R$ {activeProduct.price.toFixed(2).replace(".", ",")}</div>
                </div>
              </div>
              <Link href={`/produtos/${activeProduct.id}`} className="mt-5 inline-flex items-center gap-2 rounded-md bg-yellow px-5 py-3 text-sm font-black text-navy transition hover:brightness-95">
                Ver produto <ArrowRight className="h-4 w-4" />
              </Link>
            </div>

            <div className="hidden justify-self-end rounded-2xl bg-white p-2 shadow-[var(--shadow-card)] md:block">
              <img src={activeProduct.image} alt={activeProduct.name} className="h-44 w-72 rounded-xl object-cover" />
            </div>

            <div className="hidden rounded-2xl border border-white/15 bg-white/10 p-5 text-right lg:block">
              <div className="text-xs text-white/70">a partir de</div>
              <div className="font-display text-4xl font-black text-yellow">
                R$ {activeProduct.price.toFixed(2).replace(".", ",")}
              </div>
              <div className="mt-2 text-xs text-white/70">Pix, cartão e boleto</div>
              <div className="mt-4 flex justify-end gap-1.5">
                {featuredProducts.map((item, index) => (
                  <button
                    key={item.id}
                    type="button"
                    aria-label={`Mostrar ${item.name}`}
                    onClick={() => setActiveIndex(index)}
                    className={`h-1.5 rounded-full transition-all ${index === activeIndex ? "w-8 bg-yellow" : "w-3 bg-white/30"}`}
                  />
                ))}
              </div>
            </div>
          </article>

          <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-1.5 md:hidden">
            {featuredProducts.map((item, index) => (
              <button
                key={item.id}
                type="button"
                aria-label={`Mostrar ${item.name}`}
                onClick={() => setActiveIndex(index)}
                className={`h-1.5 rounded-full transition-all ${index === activeIndex ? "w-8 bg-yellow" : "w-3 bg-white/40"}`}
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
function FeaturedProducts() {
  return (
    <section className="bg-white py-8 md:py-12">
      <div className="mx-auto max-w-7xl px-4">
        <div className="flex items-end justify-between gap-3">
          <SectionHeading eyebrow="Vitrine" title="Produtos em destaque" />
          <Link href="/produtos" className="inline-flex shrink-0 items-center gap-2 rounded-md border border-navy px-3 py-2 text-xs font-black text-navy transition hover:bg-navy hover:text-white md:px-4 md:text-sm">
            Ver todos <ArrowRight className="h-4 w-4" />
          </Link>
        </div>
        <div className="mt-6 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
          {products.slice(0, 4).map((p) => <ProductCard key={p.id} p={p} />)}
        </div>
      </div>
    </section>
  );
}

function ProductLines() {
  const lines = [
    { title: "Empresas", desc: "Camisas, polos, calças e conjuntos corporativos.", img: catEmpresa, to: "/empresas", icon: Building2 },
    { title: "Escolas", desc: "Uniformes confortáveis e resistentes para a rotina escolar.", img: catEscola, to: "/escolas", icon: Shirt },
    { title: "Profissional", desc: "Cozinha, limpeza, indústria, saúde e atendimento.", img: catProf, to: "/produtos", icon: Factory },
    { title: "Personalizados", desc: "Bordado, estampa, logotipo e identidade visual.", img: catPerso, to: "/personalizados", icon: Sparkles },
  ];

  return (
    <section className="mx-auto max-w-7xl px-4 py-12 md:py-14">
      <SectionHeading eyebrow="Compre por linha" title="Categorias para sua empresa" />
      <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {lines.map((line) => (
          <Link key={line.title} href={line.to} className="group overflow-hidden rounded-xl border border-border bg-white shadow-[var(--shadow-soft)] transition hover:-translate-y-1 hover:shadow-[var(--shadow-card)]">
            <div className="aspect-[5/4] overflow-hidden bg-bg-soft">
              <img src={line.img} alt={line.title} loading="lazy" width={800} height={650} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
            </div>
            <div className="p-5">
              <div className="mb-3 grid h-10 w-10 place-items-center rounded-lg bg-yellow text-navy">
                <line.icon className="h-5 w-5" />
              </div>
              <h3 className="font-display text-lg font-black text-navy">{line.title}</h3>
              <p className="mt-1 text-sm leading-6 text-text-muted">{line.desc}</p>
            </div>
          </Link>
        ))}
      </div>
    </section>
  );
}

function CommerceBenefits() {
  const items = [
    { icon: ShieldCheck, title: "Compra segura", desc: "Atendimento consultivo do orçamento à entrega." },
    { icon: CreditCard, title: "Pagamento facilitado", desc: "Pix, cartão, boleto e condições para empresas." },
    { icon: Truck, title: "Entrega nacional", desc: "Logística para pedidos pequenos e grandes volumes." },
    { icon: Scissors, title: "Personalização", desc: "Bordado, silk e acabamento sob medida." },
  ];

  return (
    <section className="border-y border-border bg-white py-8">
      <div className="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-2 lg:grid-cols-4">
        {items.map((item) => (
          <div key={item.title} className="flex gap-3 rounded-xl bg-bg-soft p-4">
            <div className="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-navy text-yellow">
              <item.icon className="h-5 w-5" />
            </div>
            <div>
              <h3 className="font-display text-sm font-black text-navy">{item.title}</h3>
              <p className="mt-1 text-xs leading-5 text-text-muted">{item.desc}</p>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

function Stores() {
  const stores = [
    {
      type: "Matriz",
      city: "Pernambuco",
      address: "Av. Doutor Júlio Maranhão, 7, Guararapes, Jaboatão dos Guararapes - PE",
      phone: "(81) 97910-6667 / (81) 3074-2933",
      hours: "Segunda a Sexta, 7h às 17h",
    },
    {
      type: "Filial",
      city: "São Paulo",
      address: "Estrada do Rufino, 850, Serraria, Diadema - SP",
      phone: "(11) 94211-0729 / (11) 4057-3202",
      hours: "Segunda a Sexta, 8h às 18h / Sábado 8h às 13h",
    },
  ];

  return (
    <section id="lojas" className="bg-bg-soft py-12 md:py-14">
      <div className="mx-auto max-w-7xl px-4">
        <SectionHeading eyebrow="Nossas lojas" title="Retire, visite ou fale com uma unidade" />
        <div className="mt-8 grid gap-5 lg:grid-cols-2">
          {stores.map((store) => (
            <article key={store.city} className="overflow-hidden rounded-xl border border-border bg-white shadow-[var(--shadow-soft)]">
              <div className="flex items-center justify-between bg-navy px-5 py-4 text-white">
                <div>
                  <div className="text-xs font-black uppercase tracking-[0.18em] text-yellow">{store.type}</div>
                  <h3 className="mt-1 font-display text-2xl font-black">{store.city}</h3>
                </div>
                <Store className="h-8 w-8 text-yellow" />
              </div>
              <div className="space-y-4 p-5 text-sm text-text-muted">
                <Info icon={MapPin} label="Endereço" value={store.address} />
                <Info icon={Phone} label="Telefone" value={store.phone} />
                <Info icon={Timer} label="Horário" value={store.hours} />
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

function Technology() {
  const items = [
    { icon: Scissors, title: "Tecnologia de ponta", desc: "Agilidade e alta precisão em cada etapa de produção com equipamentos modernos." },
    { icon: ShieldCheck, title: "Empresa consolidada", desc: "Qualidade e confiabilidade comprovadas em cada entrega." },
    { icon: Truck, title: "Capacidade produtiva", desc: "Estrutura para atender grandes volumes com eficiência." },
  ];

  return (
    <section className="mx-auto max-w-7xl px-4 py-12 md:py-14">
      <div className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <div>
          <SectionHeading eyebrow="Tecnologia & inovação" title="Produtividade a serviço do seu negócio" />
          <p className="mt-5 text-base leading-8 text-text-muted">
            Com estrutura moderna e equipe capacitada, garantimos uniformes de alto padrão, otimizando tempo e agregando valor à sua empresa.
          </p>
        </div>
        <div className="grid gap-4">
          {items.map((item) => (
            <div key={item.title} className="flex gap-4 rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
              <div className="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-yellow">
                <item.icon className="h-5 w-5 text-navy" />
              </div>
              <div>
                <h3 className="font-display text-lg font-bold text-navy">{item.title}</h3>
                <p className="mt-1 text-sm leading-6 text-text-muted">{item.desc}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function History() {
  return (
    <section id="historia" className="bg-navy py-12 text-white md:py-14">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
        <div>
          <div className="text-xs font-black uppercase tracking-[0.2em] text-yellow">Nossa história</div>
          <h2 className="mt-3 font-display text-3xl font-black leading-tight md:text-4xl">Mais de 18 anos de tradição e excelência</h2>
          <p className="mt-5 leading-8 text-white/78">
            Desde 2007, a Fábrica de Fardamentos é sinônimo de excelência na fabricação de uniformes profissionais. Nossa jornada é marcada pela busca por inovação, qualidade e satisfação do cliente.
          </p>
        </div>
        <div className="grid gap-3">
          {[
            ["Missão", "Oferecer uniformes que unam funcionalidade, conforto e estilo."],
            ["Visão", "Ser referência no mercado de uniformes profissionais."],
            ["Valores", "Integridade, respeito, qualidade e sustentabilidade."],
          ].map(([title, desc]) => (
            <div key={title} className="rounded-xl border border-white/10 bg-white/8 p-4">
              <div className="flex items-center gap-2 font-display text-lg font-bold text-yellow">
                <CheckCircle2 className="h-5 w-5" /> {title}
              </div>
              <p className="mt-1 text-sm leading-6 text-white/72">{desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function QuoteCTA() {
  return (
    <section className="mx-auto max-w-7xl px-4 py-12 md:py-16">
      <div className="rounded-2xl bg-navy-deep p-8 text-white shadow-[var(--shadow-card)] md:p-10">
        <span className="inline-block rounded-full bg-yellow px-3 py-1 text-xs font-bold text-navy">Compra corporativa</span>
        <h2 className="mt-4 font-display text-3xl font-black leading-tight md:text-4xl">Monte o pedido de uniformes da sua equipe</h2>
        <p className="mt-3 max-w-2xl text-white/80">Escolha os produtos, adicione ao carrinho e revise tamanhos, cores e personalização.</p>
        <div className="mt-6 flex flex-wrap gap-3">
          <Link href="/produtos" className="inline-flex items-center gap-2 rounded-md bg-yellow px-6 py-3 font-bold text-navy transition hover:brightness-95">
            Ver produtos <ArrowRight className="h-4 w-4" />
          </Link>
          <a href="https://wa.me/5581979106667" className="inline-flex items-center gap-2 rounded-md border border-white/40 px-6 py-3 font-semibold text-white transition hover:bg-white hover:text-navy">
            <MessageCircle className="h-4 w-4" /> WhatsApp
          </a>
        </div>
      </div>
    </section>
  );
}

function Stat({ value, label }: { value: string; label: string }) {
  return (
    <div className="rounded-lg border border-white/12 bg-white/8 p-3">
      <div className="font-display text-xl font-black text-yellow">{value}</div>
      <div>{label}</div>
    </div>
  );
}

function Benefit({ icon: Icon, title }: { icon: LucideIcon; title: string }) {
  return (
    <div className="flex items-center gap-3">
      <span className="grid h-10 w-10 place-items-center rounded-lg bg-white/10 text-yellow">
        <Icon className="h-5 w-5" />
      </span>
      <span className="font-bold">{title}</span>
    </div>
  );
}

function Info({ icon: Icon, label, value }: { icon: LucideIcon; label: string; value: string }) {
  return (
    <div className="flex gap-3">
      <Icon className="mt-0.5 h-4 w-4 shrink-0 text-navy" />
      <div>
        <div className="font-semibold text-navy">{label}</div>
        <div>{value}</div>
      </div>
    </div>
  );
}

function SectionHeading({ eyebrow, title }: { eyebrow: string; title: string }) {
  return (
    <div>
      <div className="text-xs font-bold uppercase tracking-[0.2em] text-navy/60">{eyebrow}</div>
      <h2 className="mt-2 font-display text-3xl font-black text-navy md:text-4xl">{title}</h2>
      <div className="mt-3 h-1 w-16 rounded bg-yellow" />
    </div>
  );
}
