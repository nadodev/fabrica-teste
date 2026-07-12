import { Link } from "@inertiajs/react";
import { useMemo, useState } from "react";
import type { LucideIcon } from "lucide-react";
import {
  ArrowLeft,
  BadgeCheck,
  CreditCard,
  Minus,
  PackageCheck,
  Plus,
  Ruler,
  ShieldCheck,
  ShoppingCart,
  Sparkles,
  Truck,
} from "lucide-react";
import { formatBRL, products } from "@/lib/store-data";
import { ProductCard } from "@/components/product-card";

type Props = {
  productId: string;
};

const sizes = ["PP", "P", "M", "G", "GG", "XGG"];

export default function ProdutoDetalhe({ productId }: Props) {
  const product = products.find((item) => item.id === productId) ?? products[0];
  const related = useMemo(() => products.filter((item) => item.id !== product.id).slice(0, 4), [product.id]);
  const gallery = useMemo(() => [product, ...products.filter((item) => item.id !== product.id)].slice(0, 4), [product]);
  const [selectedImage, setSelectedImage] = useState(product.image);
  const [selectedColor, setSelectedColor] = useState(product.colors[0]);
  const [selectedSize, setSelectedSize] = useState("M");
  const [qty, setQty] = useState(1);

  return (
    <div className="bg-bg-soft">
      <section className="border-b border-border bg-white">
        <div className="mx-auto max-w-7xl px-4 py-4">
          <Link href="/produtos" className="inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline">
            <ArrowLeft className="h-4 w-4" /> Voltar para produtos
          </Link>
        </div>
      </section>

      <section className="mx-auto grid max-w-7xl gap-5 px-4 py-6 md:gap-8 md:py-8 lg:grid-cols-[0.95fr_1.05fr]">
        <div className="space-y-3 md:space-y-4">
          <div className="overflow-hidden rounded-xl border border-border bg-white p-2 shadow-[var(--shadow-soft)] md:rounded-2xl md:p-3">
            <div className="overflow-hidden rounded-xl bg-bg-soft">
              <img src={selectedImage} alt={product.name} className="aspect-square w-full object-cover sm:aspect-[4/3]" />
            </div>
          </div>
          <div className="flex gap-2 overflow-x-auto pb-1 sm:grid sm:grid-cols-4 sm:gap-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            {gallery.map((item, index) => (
              <button
                key={`${item.id}-${index}`}
                onClick={() => setSelectedImage(item.image)}
                className={`w-20 shrink-0 overflow-hidden rounded-xl border-2 bg-white p-1 transition sm:w-auto ${
                  selectedImage === item.image ? "border-navy ring-2 ring-yellow" : "border-border hover:border-yellow"
                }`}
              >
                <img src={item.image} alt={item.name} className="aspect-square w-full rounded-lg object-cover" />
              </button>
            ))}
          </div>
        </div>

        <div className="rounded-xl border border-border bg-white p-4 shadow-[var(--shadow-soft)] md:rounded-2xl md:p-6">
          <div className="flex flex-wrap items-center gap-2">
            <span className="rounded-full bg-bg-soft px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-text-muted">{product.segment}</span>
            {product.personalizable && (
              <span className="inline-flex items-center gap-1 rounded-full bg-yellow px-3 py-1 text-xs font-black text-navy">
                <Sparkles className="h-3.5 w-3.5" /> Personalizável
              </span>
            )}
          </div>

          <h1 className="mt-4 font-display text-2xl font-black leading-tight text-navy md:text-4xl">{product.name}</h1>
          <p className="mt-3 max-w-2xl text-sm leading-7 text-text-muted">{product.description}</p>

          <div className="mt-5 rounded-xl bg-bg-soft p-4">
            <div className="text-xs text-text-muted">Preço unitário a partir de</div>
            <div className="font-display text-3xl font-black text-navy md:text-4xl">{formatBRL(product.price)}</div>
            <div className="mt-1 text-xs text-text-muted">Valor pode variar conforme quantidade, tecido e personalização.</div>
          </div>

          <div className="mt-6 grid gap-5">
            <div>
              <div className="mb-2 text-sm font-bold text-navy">Cor</div>
              <div className="flex flex-wrap gap-2">
                {product.colors.map((color) => (
                  <button
                    key={color}
                    onClick={() => setSelectedColor(color)}
                    className={`h-9 w-9 rounded-full border-2 ${selectedColor === color ? "border-navy ring-2 ring-yellow" : "border-border"}`}
                    style={{ background: color }}
                    aria-label={`Selecionar cor ${color}`}
                  />
                ))}
              </div>
            </div>

            <div>
              <div className="mb-2 flex items-center gap-2 text-sm font-bold text-navy">
                <Ruler className="h-4 w-4" /> Tamanho
              </div>
              <div className="flex flex-wrap gap-2">
                {sizes.map((size) => (
                  <button
                    key={size}
                    onClick={() => setSelectedSize(size)}
                    className={`h-10 min-w-12 rounded-md border px-3 text-sm font-black transition ${
                      selectedSize === size ? "border-navy bg-navy text-white" : "border-border bg-white text-navy hover:border-navy"
                    }`}
                  >
                    {size}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <div className="mb-2 text-sm font-bold text-navy">Quantidade</div>
              <div className="inline-flex overflow-hidden rounded-md border border-border bg-white">
                <button onClick={() => setQty(Math.max(1, qty - 1))} className="grid h-11 w-11 place-items-center text-navy hover:bg-bg-soft" aria-label="Diminuir">
                  <Minus className="h-4 w-4" />
                </button>
                <div className="grid h-11 w-14 place-items-center border-x border-border font-black text-navy">{qty}</div>
                <button onClick={() => setQty(qty + 1)} className="grid h-11 w-11 place-items-center text-navy hover:bg-bg-soft" aria-label="Aumentar">
                  <Plus className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <div className="mt-7 grid gap-3 sm:grid-cols-2">
            <Link href="/carrinho" className="inline-flex items-center justify-center gap-2 rounded-md bg-yellow px-6 py-3 font-black text-navy transition hover:brightness-95">
              <ShoppingCart className="h-5 w-5" /> Adicionar ao carrinho
            </Link>
            <Link href="/carrinho" className="inline-flex items-center justify-center gap-2 rounded-md bg-navy px-6 py-3 font-black text-white transition hover:bg-navy-deep">
              Comprar agora
            </Link>
          </div>

          <div className="mt-6 grid grid-cols-1 gap-3 border-t border-border pt-5 sm:grid-cols-3">
            <Trust icon={ShieldCheck} text="Compra segura" />
            <Trust icon={Truck} text="Entrega nacional" />
            <Trust icon={CreditCard} text="Pix, cartão e boleto" />
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-12">
        <div className="grid gap-5 lg:grid-cols-3">
          <InfoCard icon={PackageCheck} title="Produção sob medida" text="Pedidos por grade de tamanho, cor e quantidade para equipes." />
          <InfoCard icon={BadgeCheck} title="Acabamento profissional" text="Costura, tecido e personalização pensados para uso diário." />
          <InfoCard icon={Sparkles} title="Personalização" text="Bordado, silk e aplicação da identidade visual da sua empresa." />
        </div>
      </section>

      <section className="bg-white py-10">
        <div className="mx-auto max-w-7xl px-4">
          <h2 className="font-display text-2xl font-black text-navy">Você também pode gostar</h2>
          <div className="mt-6 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
            {related.map((item) => <ProductCard key={item.id} p={item} />)}
          </div>
        </div>
      </section>
    </div>
  );
}

function Trust({ icon: Icon, text }: { icon: LucideIcon; text: string }) {
  return (
    <div className="flex items-center gap-2 text-xs font-bold text-text-muted">
      <Icon className="h-4 w-4 text-navy" /> {text}
    </div>
  );
}

function InfoCard({ icon: Icon, title, text }: { icon: LucideIcon; title: string; text: string }) {
  return (
    <div className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
      <div className="grid h-11 w-11 place-items-center rounded-lg bg-yellow text-navy">
        <Icon className="h-5 w-5" />
      </div>
      <h3 className="mt-4 font-display text-lg font-black text-navy">{title}</h3>
      <p className="mt-1 text-sm leading-6 text-text-muted">{text}</p>
    </div>
  );
}
