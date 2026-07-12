import type { ComponentType, ReactNode } from "react";
import {
  BadgeCheck,
  Barcode,
  CreditCard,
  Instagram,
  Mail,
  MapPin,
  MessageCircle,
  Phone,
  QrCode,
  ShieldCheck,
  Truck,
} from "lucide-react";

type SiteSettings = {
  storeName: string;
  logoUrl: string;
};

export function SiteFooter({ settings }: { settings: SiteSettings }) {

  return (
    <footer className="mt-16 bg-navy-deep text-white">
      <div className="border-b border-white/10 bg-navy">
        <div className="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:grid-cols-2 lg:grid-cols-4">
          <FooterBenefit icon={ShieldCheck} title="Compra segura" text="Atendimento direto com a fábrica" />
          <FooterBenefit icon={CreditCard} title="Pagamento facilitado" text="Pix, cartão e boleto" />
          <FooterBenefit icon={Truck} title="Entrega nacional" text="Envio para empresas em todo o Brasil" />
          <FooterBenefit icon={BadgeCheck} title="Qualidade garantida" text="Uniformes desde 2007" />
        </div>
      </div>

      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-2 xl:grid-cols-[1.15fr_0.7fr_1.55fr_1fr]">
        <div>
          <div className="inline-flex rounded-lg bg-white p-2">
            <img src={settings.logoUrl} width={190} alt={settings.storeName} />
          </div>
          <p className="mt-4 text-sm leading-7 text-white/70">
            Desde 2007, a Fábrica de Fardamentos produz uniformes profissionais com qualidade, conforto e durabilidade.
          </p>
          <div className="mt-5 flex gap-2">
            <SocialBtn label="Instagram Pernambuco" href="#"><Instagram className="h-4 w-4" /></SocialBtn>
            <SocialBtn label="Instagram São Paulo" href="#"><Instagram className="h-4 w-4" /></SocialBtn>
          </div>
        </div>

        <FooterCol title="Loja" links={[
          { to: "/", label: "Início" },
          { to: "/produtos", label: "Produtos" },
          { to: "/empresas", label: "Empresas" },
          { to: "/escolas", label: "Escolas" },
          { to: "/carrinho", label: "Carrinho" },
        ]} />

        <div>
          <div className="mb-4 text-sm font-bold uppercase tracking-wider text-yellow">Unidades</div>
          <div className="grid gap-4 text-sm text-white/78 lg:grid-cols-2">
            <div>
              <div className="font-bold text-white">Pernambuco</div>
              <p className="mt-1 leading-6">Av. Dr. Júlio Maranhão, 7, Guararapes, Jaboatão dos Guararapes-PE.</p>
              <p className="mt-1">Fone: (81) 3074-2933</p>
              <p>WhatsApp: (81) 97910-6667</p>
            </div>
            <div>
              <div className="font-bold text-white">São Paulo</div>
              <p className="mt-1 leading-6">Estrada do Rufino, 850, Serraria, Diadema-SP.</p>
              <p className="mt-1">Fone: (11) 4057-3202</p>
              <p>WhatsApp: (11) 94211-0729</p>
            </div>
          </div>
        </div>

        <div>
          <div className="mb-4 text-sm font-bold uppercase tracking-wider text-yellow">Pagamento</div>
          <div className="grid grid-cols-2 gap-2">
            <Payment icon={QrCode} label="Pix" />
            <Payment icon={CreditCard} label="Cartão" />
            <Payment icon={Barcode} label="Boleto" />
            <Payment icon={ShieldCheck} label="Seguro" />
          </div>
          <div className="mt-5 space-y-3 text-sm text-white/78">
            <p className="flex items-start gap-2"><Mail className="mt-0.5 h-4 w-4 shrink-0 text-yellow" /> fabricadefardamentos@gmail.com</p>
            <p className="flex items-start gap-2"><MessageCircle className="mt-0.5 h-4 w-4 shrink-0 text-yellow" /> Atendimento pelo WhatsApp</p>
            <p className="flex items-start gap-2"><MapPin className="mt-0.5 h-4 w-4 shrink-0 text-yellow" /> Pernambuco e São Paulo</p>
          </div>
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-5 text-xs text-white/50">
          <span>© {new Date().getFullYear()} Fábrica de Fardamentos. Todos os direitos reservados.</span>
          <span className="inline-flex items-center gap-2"><Phone className="h-3.5 w-3.5" /> Atendimento para todo o Brasil</span>
        </div>
      </div>
    </footer>
  );
}

function FooterBenefit({ icon: Icon, title, text }: { icon: ComponentType<{ className?: string }>; title: string; text: string }) {
  return (
    <div className="flex gap-3">
      <div className="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-yellow text-navy">
        <Icon className="h-5 w-5" />
      </div>
      <div>
        <div className="font-display text-sm font-black">{title}</div>
        <div className="text-xs text-white/68">{text}</div>
      </div>
    </div>
  );
}

function FooterCol({ title, links }: { title: string; links: { to: string; label: string }[] }) {
  return (
    <div>
      <div className="mb-4 text-sm font-bold uppercase tracking-wider text-yellow">{title}</div>
      <ul className="space-y-2 text-sm text-white/80">
        {links.map((l) => (
          <li key={l.label}><a href={l.to} className="transition-colors hover:text-yellow">{l.label}</a></li>
        ))}
      </ul>
    </div>
  );
}

function Payment({ icon: Icon, label }: { icon: ComponentType<{ className?: string }>; label: string }) {
  return (
    <div className="flex items-center gap-2 rounded-lg border border-white/10 bg-white/8 px-3 py-2 text-sm font-bold">
      <Icon className="h-4 w-4 text-yellow" /> {label}
    </div>
  );
}

function SocialBtn({ children, label, href }: { children: ReactNode; label: string; href: string }) {
  return (
    <a href={href} aria-label={label} className="grid h-9 w-9 place-items-center rounded-full bg-white/10 transition-colors hover:bg-yellow hover:text-navy">
      {children}
    </a>
  );
}
