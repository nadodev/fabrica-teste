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
} from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';

type SiteSettings = {
    storeName: string;
    logoUrl: string;
    footerLogoUrl?: string;
    legalName?: string;
    documentNumber?: string;
    contactEmail?: string;
    contactPhone?: string;
    whatsapp?: string;
    companyAddress?: string;
    businessHours?: string;
    payments?: {
        pixEnabled?: boolean;
        cardEnabled?: boolean;
        boletoEnabled?: boolean;
    };
    policies?: {
        termsUrl?: string;
        privacyUrl?: string;
        warrantyInfo?: string;
    };
    socialLinks?: { label: string; url: string }[];
};

export function SiteFooter({ settings }: { settings: SiteSettings }) {
    const socialLinks = Array.isArray(settings.socialLinks)
        ? settings.socialLinks
        : [];
    const payments = [];

    if (settings.payments?.pixEnabled !== false) {
        payments.push({ icon: QrCode, label: 'Pix' });
    }

    if (settings.payments?.cardEnabled !== false) {
        payments.push({ icon: CreditCard, label: 'Cartão' });
    }

    if (settings.payments?.boletoEnabled !== false) {
        payments.push({ icon: Barcode, label: 'Boleto' });
    }

    return (
        <footer className="mt-16 bg-navy-deep text-white">
            <div className="border-b border-white/10 bg-navy">
                <div className="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:grid-cols-2 lg:grid-cols-4">
                    <FooterBenefit
                        icon={ShieldCheck}
                        title="Compra segura"
                        text="Atendimento direto com a fábrica"
                    />
                    <FooterBenefit
                        icon={CreditCard}
                        title="Pagamento facilitado"
                        text={
                            payments.length > 0
                                ? payments.map((item) => item.label).join(', ')
                                : 'Consulte as formas disponíveis'
                        }
                    />
                    <FooterBenefit
                        icon={Truck}
                        title="Entrega nacional"
                        text="Envio para empresas em todo o Brasil"
                    />
                    <FooterBenefit
                        icon={BadgeCheck}
                        title="Qualidade garantida"
                        text="Uniformes desde 2007"
                    />
                </div>
            </div>

            <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-2 xl:grid-cols-[1.15fr_0.7fr_1.55fr_1fr]">
                <div>
                    <div className="inline-flex rounded-lg bg-white p-2">
                        <img
                            src={settings.footerLogoUrl ?? settings.logoUrl}
                            width={190}
                            alt={settings.storeName}
                        />
                    </div>
                    <p className="mt-4 text-sm leading-7 text-white/70">
                        {settings.legalName || settings.storeName} produz
                        uniformes profissionais com qualidade, conforto e
                        durabilidade.
                    </p>
                    {socialLinks.length > 0 && (
                        <div className="mt-5 flex flex-wrap gap-2">
                            {socialLinks.map((link) => (
                                <SocialBtn
                                    key={`${link.label}-${link.url}`}
                                    label={link.label}
                                    href={link.url}
                                >
                                    <Instagram className="h-4 w-4" />
                                </SocialBtn>
                            ))}
                        </div>
                    )}
                </div>

                <FooterCol
                    title="Loja"
                    links={[
                        { to: '/', label: 'Início' },
                        { to: '/produtos', label: 'Produtos' },
                        { to: '/empresas', label: 'Empresas' },
                        { to: '/escolas', label: 'Escolas' },
                        { to: '/carrinho', label: 'Carrinho' },
                        {
                            to: settings.policies?.privacyUrl || '/privacidade',
                            label: 'Privacidade',
                        },
                        {
                            to: settings.policies?.termsUrl || '/termos',
                            label: 'Termos',
                        },
                    ]}
                />

                <div>
                    <div className="mb-4 text-sm font-bold tracking-wider text-yellow uppercase">
                        Atendimento
                    </div>
                    <div className="space-y-3 text-sm text-white/78">
                        {settings.companyAddress && (
                            <p className="flex items-start gap-2">
                                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-yellow" />{' '}
                                {settings.companyAddress}
                            </p>
                        )}
                        {settings.contactPhone && (
                            <p className="flex items-start gap-2">
                                <Phone className="mt-0.5 h-4 w-4 shrink-0 text-yellow" />{' '}
                                {settings.contactPhone}
                            </p>
                        )}
                        {settings.whatsapp && (
                            <p className="flex items-start gap-2">
                                <MessageCircle className="mt-0.5 h-4 w-4 shrink-0 text-yellow" />{' '}
                                {settings.whatsapp}
                            </p>
                        )}
                        {settings.businessHours && (
                            <p>{settings.businessHours}</p>
                        )}
                    </div>
                </div>

                <div>
                    <div className="mb-4 text-sm font-bold tracking-wider text-yellow uppercase">
                        Pagamento
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                        {payments.map((payment) => (
                            <Payment
                                key={payment.label}
                                icon={payment.icon}
                                label={payment.label}
                            />
                        ))}
                        {payments.length > 0 && (
                            <Payment icon={ShieldCheck} label="Seguro" />
                        )}
                    </div>
                    <div className="mt-5 space-y-3 text-sm text-white/78">
                        {settings.contactEmail && (
                            <p className="flex items-start gap-2">
                                <Mail className="mt-0.5 h-4 w-4 shrink-0 text-yellow" />{' '}
                                {settings.contactEmail}
                            </p>
                        )}
                        {settings.whatsapp && (
                            <p className="flex items-start gap-2">
                                <MessageCircle className="mt-0.5 h-4 w-4 shrink-0 text-yellow" />{' '}
                                Atendimento pelo WhatsApp
                            </p>
                        )}
                        {settings.policies?.warrantyInfo && (
                            <p className="flex items-start gap-2">
                                <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-yellow" />{' '}
                                {settings.policies.warrantyInfo}
                            </p>
                        )}
                    </div>
                </div>
            </div>

            <div className="border-t border-white/10">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-5 text-xs text-white/50">
                    <span>
                        © {new Date().getFullYear()} {settings.storeName}. Todos
                        os direitos reservados.
                    </span>
                    <span>
                        {settings.legalName || settings.storeName}
                        {settings.documentNumber
                            ? ` - ${settings.documentNumber}`
                            : ''}
                    </span>
                </div>
            </div>
        </footer>
    );
}

function FooterBenefit({
    icon: Icon,
    title,
    text,
}: {
    icon: ComponentType<{ className?: string }>;
    title: string;
    text: string;
}) {
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

function FooterCol({
    title,
    links,
}: {
    title: string;
    links: { to: string; label: string }[];
}) {
    return (
        <div>
            <div className="mb-4 text-sm font-bold tracking-wider text-yellow uppercase">
                {title}
            </div>
            <ul className="space-y-2 text-sm text-white/80">
                {links.map((l) => (
                    <li key={l.label}>
                        <a
                            href={l.to}
                            className="transition-colors hover:text-yellow"
                        >
                            {l.label}
                        </a>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function Payment({
    icon: Icon,
    label,
}: {
    icon: ComponentType<{ className?: string }>;
    label: string;
}) {
    return (
        <div className="flex items-center gap-2 rounded-lg border border-white/10 bg-white/8 px-3 py-2 text-sm font-bold">
            <Icon className="h-4 w-4 text-yellow" /> {label}
        </div>
    );
}

function SocialBtn({
    children,
    label,
    href,
}: {
    children: ReactNode;
    label: string;
    href: string;
}) {
    return (
        <a
            href={href}
            aria-label={label}
            className="grid h-9 w-9 place-items-center rounded-full bg-white/10 transition-colors hover:bg-yellow hover:text-navy"
        >
            {children}
        </a>
    );
}
