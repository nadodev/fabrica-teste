import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    CreditCard,
    MapPin,
    MessageCircle,
    Phone,
    Scissors,
    ShieldCheck,
    Store,
    Timer,
    Truck,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import catEmpresa from '@/assets/cat-empresa.jpg';
import catEscola from '@/assets/cat-escola.jpg';
import catPerso from '@/assets/cat-personalizado.jpg';
import catProf from '@/assets/cat-profissional.jpg';
import heroImg from '@/assets/hero-team.jpg';
import { ProductCard } from '@/components/product-card';
import { products } from '@/lib/store-data';
import type { CatalogProduct } from '@/modules/catalog/domain/product';
import { CatalogProductCard } from '@/modules/catalog/ui/catalog-product-card';

type Banner = {
    id: string;
    eyebrow: string;
    title: string;
    subtitle: string;
    button_label: string;
    link_url: string;
    image_url: string | null;
};
type Category = {
    id: string;
    name: string;
    description: string;
    image_url: string | null;
    link_url: string;
};
type StoreItem = {
    id: string;
    type: string;
    city: string;
    address: string;
    phone: string;
    hours: string;
};
type HistoryData = {
    eyebrow: string;
    title: string;
    body: string;
    mission: string;
    vision: string;
    values: string;
} | null;

export default function Index({
    banners = [],
    categories = [],
    products: catalogProducts = [],
    stores = [],
    history = null,
}: {
    banners?: Banner[];
    categories?: Category[];
    products?: CatalogProduct[];
    stores?: StoreItem[];
    history?: HistoryData;
}) {
    const settings = usePage<{
        siteSettings?: {
            storeName?: string;
            companyAddress?: string;
            contactPhone?: string;
            whatsapp?: string;
            businessHours?: string;
            seo?: { description?: string };
        };
        commerceSettings?: {
            payments?: {
                pixEnabled?: boolean;
                cardEnabled?: boolean;
                boletoEnabled?: boolean;
            };
        };
    }>().props;
    const site = settings.siteSettings ?? {};

    return (
        <div className="bg-bg-soft">
            <Head title="Uniformes profissionais">
                <meta
                    name="description"
                    content={
                        site.seo?.description ||
                        `${site.storeName || 'Loja'}: uniformes profissionais, escolares e personalizados.`
                    }
                />
            </Head>
            <Hero banners={banners} />
            <FeaturedProducts products={catalogProducts} />
            <ProductLines categories={categories} />
            <CommerceBenefits payments={settings.commerceSettings?.payments} />
            <Stores stores={stores} settings={site} />
            <Technology />
            <History history={history} storeName={site.storeName} />
            <QuoteCTA whatsapp={site.whatsapp} />
        </div>
    );
}

function Hero({ banners }: { banners: Banner[] }) {
    const safeBanners = Array.isArray(banners) ? banners : [];
    const fallback = [
        {
            id: 'default-store-banner',
            eyebrow: 'Destaque da loja',
            title: 'Uniformes profissionais para sua equipe',
            subtitle:
                'Cadastre banners no admin para controlar esse espaco da home.',
            button_label: 'Ver produtos',
            link_url: '/produtos',
            image_url: null,
        },
    ];
    const slides = safeBanners.length > 0 ? safeBanners : fallback;
    const [activeIndex, setActiveIndex] = useState(0);
    const active = slides[activeIndex] ?? slides[0];

    useEffect(() => {
        const timer = window.setInterval(
            () => setActiveIndex((current) => (current + 1) % slides.length),
            4500,
        );

        return () => window.clearInterval(timer);
    }, [slides.length]);

    return (
        <section className="bg-bg-soft py-3 md:py-5">
            <div className="relative min-h-[390px] w-full overflow-hidden bg-navy text-white shadow-[var(--shadow-card)] sm:min-h-[350px] md:min-h-[300px]">
                <img
                    src={active?.image_url ?? heroImg}
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover opacity-35"
                />
                <div className="absolute inset-0 bg-gradient-to-b from-navy via-navy/92 to-navy/72 md:bg-gradient-to-r md:from-navy md:via-navy/90 md:to-navy/30" />
                <div className="relative mx-auto grid min-h-[390px] max-w-7xl items-center gap-5 px-4 py-7 md:min-h-[300px] md:grid-cols-[1fr_320px]">
                    <div className="max-w-2xl">
                        <div className="inline-flex rounded-full bg-yellow px-3 py-1 text-[11px] font-black tracking-[0.16em] text-navy uppercase">
                            {active?.eyebrow}
                        </div>
                        <h1 className="mt-3 font-display text-3xl leading-tight font-black md:text-5xl">
                            {active?.title}
                        </h1>
                        <p className="mt-3 max-w-xl text-sm leading-6 text-white/78 md:text-base">
                            {active?.subtitle}
                        </p>
                        <Link
                            href={active?.link_url ?? '/produtos'}
                            className="mt-5 inline-flex items-center gap-2 rounded-md bg-yellow px-5 py-3 text-sm font-black text-navy transition hover:brightness-95"
                        >
                            {active?.button_label ?? 'Ver produtos'}{' '}
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </div>
                    <div className="hidden rounded-2xl bg-white p-2 shadow-[var(--shadow-card)] md:block">
                        <img
                            src={active?.image_url ?? heroImg}
                            alt={active?.title ?? ''}
                            className="h-48 w-full rounded-xl object-cover"
                        />
                    </div>
                    <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-1.5">
                        {slides.map((item, index) => (
                            <button
                                key={item.id}
                                type="button"
                                aria-label={`Mostrar ${item.title}`}
                                onClick={() => setActiveIndex(index)}
                                className={`h-1.5 rounded-full transition-all ${index === activeIndex ? 'w-8 bg-yellow' : 'w-3 bg-white/40'}`}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

function FeaturedProducts({
    products: catalogProducts,
}: {
    products: CatalogProduct[];
}) {
    const safeCatalogProducts = Array.isArray(catalogProducts)
        ? catalogProducts
        : [];

    return (
        <section className="bg-white py-8 md:py-12">
            <div className="mx-auto max-w-7xl px-4">
                <div className="flex items-end justify-between gap-3">
                    <SectionHeading
                        eyebrow="Vitrine"
                        title="Produtos em destaque"
                    />
                    <Link
                        href="/produtos"
                        className="inline-flex shrink-0 items-center gap-2 rounded-md border border-navy px-3 py-2 text-xs font-black text-navy transition hover:bg-navy hover:text-white md:px-4 md:text-sm"
                    >
                        Ver todos <ArrowRight className="h-4 w-4" />
                    </Link>
                </div>
                <div className="mt-6 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                    {safeCatalogProducts.length > 0
                        ? safeCatalogProducts
                              .slice(0, 4)
                              .map((product) => (
                                  <CatalogProductCard
                                      key={product.id}
                                      product={product}
                                  />
                              ))
                        : products
                              .slice(0, 4)
                              .map((p) => <ProductCard key={p.id} p={p} />)}
                </div>
            </div>
        </section>
    );
}

function ProductLines({ categories }: { categories: Category[] }) {
    const safeCategories = Array.isArray(categories) ? categories : [];
    const fallback = [
        {
            id: 'empresas',
            name: 'Empresas',
            description: 'Camisas, polos, calcas e conjuntos corporativos.',
            image_url: catEmpresa,
            link_url: '/empresas',
        },
        {
            id: 'escolas',
            name: 'Escolas',
            description:
                'Uniformes confortaveis e resistentes para a rotina escolar.',
            image_url: catEscola,
            link_url: '/escolas',
        },
        {
            id: 'profissional',
            name: 'Profissional',
            description: 'Cozinha, limpeza, industria, saude e atendimento.',
            image_url: catProf,
            link_url: '/produtos',
        },
        {
            id: 'personalizados',
            name: 'Personalizados',
            description: 'Bordado, estampa, logotipo e identidade visual.',
            image_url: catPerso,
            link_url: '/personalizados',
        },
    ];
    const lines = safeCategories.length > 0 ? safeCategories : fallback;

    return (
        <section className="mx-auto max-w-7xl px-4 py-12 md:py-14">
            <SectionHeading
                eyebrow="Compre por linha"
                title="Categorias para sua empresa"
            />
            <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {lines.map((line) => (
                    <Link
                        key={line.id}
                        href={line.link_url || '/produtos'}
                        className="group overflow-hidden rounded-xl border border-border bg-white shadow-[var(--shadow-soft)] transition hover:-translate-y-1 hover:shadow-[var(--shadow-card)]"
                    >
                        <div className="aspect-[5/4] overflow-hidden bg-bg-soft">
                            <img
                                src={line.image_url ?? catProf}
                                alt={line.name}
                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                        </div>
                        <div className="p-5">
                            <h3 className="font-display text-lg font-black text-navy">
                                {line.name}
                            </h3>
                            <p className="mt-1 text-sm leading-6 text-text-muted">
                                {line.description}
                            </p>
                        </div>
                    </Link>
                ))}
            </div>
        </section>
    );
}

function CommerceBenefits({
    payments,
}: {
    payments?: {
        pixEnabled?: boolean;
        cardEnabled?: boolean;
        boletoEnabled?: boolean;
    };
}) {
    const enabledPayments = [
        payments?.pixEnabled !== false ? 'Pix' : null,
        payments?.cardEnabled !== false ? 'cartao' : null,
        payments?.boletoEnabled !== false ? 'boleto' : null,
    ]
        .filter(Boolean)
        .join(', ');
    const items = [
        {
            icon: ShieldCheck,
            title: 'Compra segura',
            desc: 'Atendimento consultivo do pedido a entrega.',
        },
        {
            icon: CreditCard,
            title: 'Pagamento facilitado',
            desc: enabledPayments
                ? `${enabledPayments} e condicoes para empresas.`
                : 'Consulte as formas disponiveis.',
        },
        {
            icon: Truck,
            title: 'Entrega nacional',
            desc: 'Logistica para pedidos pequenos e grandes volumes.',
        },
        {
            icon: Scissors,
            title: 'Personalizacao',
            desc: 'Bordado, silk e acabamento sob medida.',
        },
    ];

    return (
        <section className="border-y border-border bg-white py-8">
            <div className="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-2 lg:grid-cols-4">
                {items.map((item) => (
                    <div
                        key={item.title}
                        className="flex gap-3 rounded-xl bg-bg-soft p-4"
                    >
                        <div className="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-navy text-yellow">
                            <item.icon className="h-5 w-5" />
                        </div>
                        <div>
                            <h3 className="font-display text-sm font-black text-navy">
                                {item.title}
                            </h3>
                            <p className="mt-1 text-xs leading-5 text-text-muted">
                                {item.desc}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}

function Stores({
    stores,
    settings,
}: {
    stores: StoreItem[];
    settings: {
        companyAddress?: string;
        contactPhone?: string;
        whatsapp?: string;
        businessHours?: string;
    };
}) {
    const safeStores = Array.isArray(stores) ? stores : [];
    const fallback = settings.companyAddress
        ? [
              {
                  id: 'general',
                  type: 'Atendimento',
                  city: 'Nossa empresa',
                  address: settings.companyAddress,
                  phone: [settings.contactPhone, settings.whatsapp]
                      .filter(Boolean)
                      .join(' / '),
                  hours: settings.businessHours || 'Consulte o atendimento',
              },
          ]
        : [];
    const rows = safeStores.length > 0 ? safeStores : fallback;

    return (
        <section id="lojas" className="bg-bg-soft py-12 md:py-14">
            <div className="mx-auto max-w-7xl px-4">
                <SectionHeading
                    eyebrow="Nossas lojas"
                    title="Retire, visite ou fale com uma unidade"
                />
                <div className="mt-8 grid gap-5 lg:grid-cols-2">
                    {rows.map((store) => (
                        <article
                            key={store.id}
                            className="overflow-hidden rounded-xl border border-border bg-white shadow-[var(--shadow-soft)]"
                        >
                            <div className="flex items-center justify-between bg-navy px-5 py-4 text-white">
                                <div>
                                    <div className="text-xs font-black tracking-[0.18em] text-yellow uppercase">
                                        {store.type}
                                    </div>
                                    <h3 className="mt-1 font-display text-2xl font-black">
                                        {store.city}
                                    </h3>
                                </div>
                                <Store className="h-8 w-8 text-yellow" />
                            </div>
                            <div className="space-y-4 p-5 text-sm text-text-muted">
                                <Info
                                    icon={MapPin}
                                    label="Endereco"
                                    value={store.address}
                                />
                                <Info
                                    icon={Phone}
                                    label="Telefone"
                                    value={store.phone}
                                />
                                <Info
                                    icon={Timer}
                                    label="Horario"
                                    value={store.hours}
                                />
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
        {
            icon: Scissors,
            title: 'Tecnologia de ponta',
            desc: 'Agilidade e alta precisao em cada etapa de producao com equipamentos modernos.',
        },
        {
            icon: ShieldCheck,
            title: 'Empresa consolidada',
            desc: 'Qualidade e confiabilidade comprovadas em cada entrega.',
        },
        {
            icon: Truck,
            title: 'Capacidade produtiva',
            desc: 'Estrutura para atender grandes volumes com eficiencia.',
        },
    ];

    return (
        <section className="mx-auto max-w-7xl px-4 py-12 md:py-14">
            <div className="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div>
                    <SectionHeading
                        eyebrow="Tecnologia & inovacao"
                        title="Produtividade a servico do seu negocio"
                    />
                    <p className="mt-5 text-base leading-8 text-text-muted">
                        Com estrutura moderna e equipe capacitada, garantimos
                        uniformes de alto padrao, otimizando tempo e agregando
                        valor a sua empresa.
                    </p>
                </div>
                <div className="grid gap-4">
                    {items.map((item) => (
                        <div
                            key={item.title}
                            className="flex gap-4 rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]"
                        >
                            <div className="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-yellow">
                                <item.icon className="h-5 w-5 text-navy" />
                            </div>
                            <div>
                                <h3 className="font-display text-lg font-bold text-navy">
                                    {item.title}
                                </h3>
                                <p className="mt-1 text-sm leading-6 text-text-muted">
                                    {item.desc}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function History({
    history,
    storeName,
}: {
    history: HistoryData;
    storeName?: string;
}) {
    const data = history ?? {
        eyebrow: 'Nossa historia',
        title: 'Tradicao e excelencia',
        body: `${storeName || 'Nossa empresa'} e sinonimo de excelencia na fabricacao de uniformes profissionais.`,
        mission:
            'Oferecer uniformes que unam funcionalidade, conforto e estilo.',
        vision: 'Ser referencia no mercado de uniformes profissionais.',
        values: 'Integridade, respeito, qualidade e sustentabilidade.',
    };

    return (
        <section id="historia" className="bg-navy py-12 text-white md:py-14">
            <div className="mx-auto grid max-w-7xl gap-8 px-4 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <div>
                    <div className="text-xs font-black tracking-[0.2em] text-yellow uppercase">
                        {data.eyebrow}
                    </div>
                    <h2 className="mt-3 font-display text-3xl leading-tight font-black md:text-4xl">
                        {data.title}
                    </h2>
                    <p className="mt-5 leading-8 text-white/78">{data.body}</p>
                </div>
                <div className="grid gap-3">
                    {[
                        ['Missao', data.mission],
                        ['Visao', data.vision],
                        ['Valores', data.values],
                    ].map(([title, desc]) => (
                        <div
                            key={title}
                            className="rounded-xl border border-white/10 bg-white/8 p-4"
                        >
                            <div className="flex items-center gap-2 font-display text-lg font-bold text-yellow">
                                <CheckCircle2 className="h-5 w-5" /> {title}
                            </div>
                            <p className="mt-1 text-sm leading-6 text-white/72">
                                {desc}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function QuoteCTA({ whatsapp }: { whatsapp?: string }) {
    const number = whatsapp?.replace(/\D/g, '');

    return (
        <section className="mx-auto max-w-7xl px-4 py-12 md:py-16">
            <div className="rounded-2xl bg-navy-deep p-8 text-white shadow-[var(--shadow-card)] md:p-10">
                <span className="inline-block rounded-full bg-yellow px-3 py-1 text-xs font-bold text-navy">
                    Compra corporativa
                </span>
                <h2 className="mt-4 font-display text-3xl leading-tight font-black md:text-4xl">
                    Monte o pedido de uniformes da sua equipe
                </h2>
                <p className="mt-3 max-w-2xl text-white/80">
                    Escolha os produtos, adicione ao carrinho e revise tamanhos,
                    cores e personalizacao.
                </p>
                <div className="mt-6 flex flex-wrap gap-3">
                    <Link
                        href="/produtos"
                        className="inline-flex items-center gap-2 rounded-md bg-yellow px-6 py-3 font-bold text-navy transition hover:brightness-95"
                    >
                        Ver produtos <ArrowRight className="h-4 w-4" />
                    </Link>
                    {number && (
                        <a
                            href={`https://wa.me/${number}`}
                            className="inline-flex items-center gap-2 rounded-md border border-white/40 px-6 py-3 font-semibold text-white transition hover:bg-white hover:text-navy"
                        >
                            <MessageCircle className="h-4 w-4" /> WhatsApp
                        </a>
                    )}
                </div>
            </div>
        </section>
    );
}

function Info({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof MapPin;
    label: string;
    value: string;
}) {
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

function SectionHeading({
    eyebrow,
    title,
}: {
    eyebrow: string;
    title: string;
}) {
    return (
        <div>
            <div className="text-xs font-bold tracking-[0.2em] text-navy/60 uppercase">
                {eyebrow}
            </div>
            <h2 className="mt-2 font-display text-3xl font-black text-navy md:text-4xl">
                {title}
            </h2>
            <div className="mt-3 h-1 w-16 rounded bg-yellow" />
        </div>
    );
}
