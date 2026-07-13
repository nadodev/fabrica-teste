import { Link } from '@inertiajs/react';
import { GraduationCap, Shirt, Ruler, HeartHandshake } from 'lucide-react';
import catEscola from '@/assets/cat-escola.jpg';
import { ProductCard } from '@/components/product-card';
import { products } from '@/lib/store-data';

export default function EscolasPage() {
    const items = products.filter((p) => p.segment === 'Escolar');

    return (
        <div>
            <section className="bg-navy text-white">
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 md:grid-cols-2 md:py-20">
                    <div>
                        <span className="inline-flex items-center gap-2 rounded-full bg-yellow/15 px-3 py-1 text-xs font-semibold text-yellow">
                            <GraduationCap className="h-3.5 w-3.5" /> Para
                            escolas
                        </span>
                        <h1 className="mt-4 font-display text-4xl font-black md:text-5xl">
                            Uniformes escolares confortáveis e resistentes
                        </h1>
                        <p className="mt-4 max-w-lg text-white/80">
                            Modelos que suportam a rotina escolar, com bordado
                            da logo e grade completa — do infantil ao adulto.
                        </p>
                        <div className="mt-6 flex gap-3">
                            <Link
                                href="/orcamento"
                                className="rounded-md bg-yellow px-5 py-3 font-bold text-navy hover:brightness-95"
                            >
                                Solicitar orçamento
                            </Link>
                            <Link
                                href="/produtos"
                                className="rounded-md border border-white/40 px-5 py-3 font-semibold transition hover:bg-white hover:text-navy"
                            >
                                Ver modelos
                            </Link>
                        </div>
                    </div>
                    <div className="overflow-hidden rounded-2xl border-4 border-yellow">
                        <img
                            src={catEscola}
                            alt="Alunos uniformizados"
                            loading="lazy"
                            className="h-full w-full object-cover"
                        />
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-16">
                <div className="grid gap-6 md:grid-cols-4">
                    {[
                        {
                            icon: Shirt,
                            title: 'Tecidos duráveis',
                            desc: 'Suportam lavagens frequentes.',
                        },
                        {
                            icon: Ruler,
                            title: 'Grade infantil',
                            desc: 'Do maternal ao ensino médio.',
                        },
                        {
                            icon: GraduationCap,
                            title: 'Identidade escolar',
                            desc: 'Bordado da logo em cada peça.',
                        },
                        {
                            icon: HeartHandshake,
                            title: 'Parceria contínua',
                            desc: 'Reposição durante o ano letivo.',
                        },
                    ].map((f) => (
                        <div
                            key={f.title}
                            className="rounded-xl border border-border bg-white p-6"
                        >
                            <div className="grid h-12 w-12 place-items-center rounded-lg bg-yellow">
                                <f.icon className="h-5 w-5 text-navy" />
                            </div>
                            <div className="mt-4 font-display font-bold text-navy">
                                {f.title}
                            </div>
                            <div className="text-sm text-text-muted">
                                {f.desc}
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="bg-bg-soft py-16">
                <div className="mx-auto max-w-7xl px-4">
                    <h2 className="font-display text-3xl font-black text-navy">
                        Modelos escolares
                    </h2>
                    <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {items.map((p) => (
                            <ProductCard key={p.id} p={p} />
                        ))}
                    </div>
                </div>
            </section>
        </div>
    );
}
