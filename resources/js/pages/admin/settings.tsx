import { Head, useForm } from '@inertiajs/react';
import { Palette, Plus, Save, Trash2, Upload } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type SocialLink = { label: string; url: string };
type Bag = Record<string, string | boolean | number | null | undefined>;
type Settings = {
    store_name: string;
    document_number?: string | null;
    legal_name?: string | null;
    contact_email?: string | null;
    contact_phone?: string | null;
    whatsapp?: string | null;
    company_address?: string | null;
    business_hours?: string | null;
    logo_url: string | null;
    header_logo_url?: string | null;
    footer_logo_url?: string | null;
    favicon_url?: string | null;
    share_image_url?: string | null;
    primary_color: string;
    secondary_color: string;
    social_links?: SocialLink[] | null;
    appearance_settings?: Bag;
    product_settings?: Bag;
    payment_settings?: Bag;
    customer_settings?: Bag;
    promotion_settings?: Bag;
    email_settings?: Bag;
    policy_settings?: Bag;
    seo_settings?: Bag;
    system_settings?: Bag;
};

const tabs = [
    ['general', 'Geral'],
    ['appearance', 'Aparencia'],
    ['products', 'Produtos'],
    ['payments', 'Pagamentos'],
    ['customers', 'Clientes'],
    ['promotions', 'Cupons'],
    ['emails', 'E-mails'],
    ['policies', 'Politicas'],
    ['seo', 'SEO'],
    ['system', 'Sistema'],
] as const;

export default function SettingsPage({ settings }: { settings: Settings }) {
    const [tab, setTab] = useState<(typeof tabs)[number][0]>('general');
    const socialLinks = Array.isArray(settings.social_links)
        ? settings.social_links
        : [];
    const form = useForm({
        storeName: settings.store_name ?? 'Fabrica de Fardamentos',
        documentNumber: settings.document_number ?? '',
        legalName: settings.legal_name ?? '',
        contactEmail: settings.contact_email ?? '',
        contactPhone: settings.contact_phone ?? '',
        whatsapp: settings.whatsapp ?? '',
        companyAddress: settings.company_address ?? '',
        businessHours: settings.business_hours ?? '',
        primaryColor: settings.primary_color ?? '#123a6b',
        secondaryColor: settings.secondary_color ?? '#f5c542',
        socialLinks:
            socialLinks.length > 0
                ? socialLinks
                : [{ label: 'Instagram', url: '' }],
        appearance: {
            productsPerPage:
                settings.appearance_settings?.productsPerPage ?? '12',
        },
        products: {
            stockControl: settings.product_settings?.stockControl ?? true,
            allowOutOfStock:
                settings.product_settings?.allowOutOfStock ?? false,
            minQuantity: settings.product_settings?.minQuantity ?? '1',
            maxQuantity: settings.product_settings?.maxQuantity ?? '100',
        },
        payments: {
            pixEnabled: settings.payment_settings?.pixEnabled ?? true,
            cardEnabled: settings.payment_settings?.cardEnabled ?? true,
            boletoEnabled: settings.payment_settings?.boletoEnabled ?? true,
        },
        customers: {
            registrationRequired:
                settings.customer_settings?.registrationRequired ?? false,
            guestCheckout: settings.customer_settings?.guestCheckout ?? true,
            validateDocument:
                settings.customer_settings?.validateDocument ?? false,
            privacyRequired:
                settings.customer_settings?.privacyRequired ?? true,
        },
        promotions: {
            couponsEnabled: settings.promotion_settings?.couponsEnabled ?? true,
            minimumOrderValue:
                settings.promotion_settings?.minimumOrderValue ?? '',
        },
        emails: {
            senderName: settings.email_settings?.senderName ?? '',
            senderEmail: settings.email_settings?.senderEmail ?? '',
            notifyNewOrder: settings.email_settings?.notifyNewOrder ?? true,
            notifyQuote: settings.email_settings?.notifyQuote ?? true,
            adminRecipients: settings.email_settings?.adminRecipients ?? '',
        },
        policies: {
            termsUrl: settings.policy_settings?.termsUrl ?? '/termos',
            privacyUrl: settings.policy_settings?.privacyUrl ?? '/privacidade',
            exchangePolicy: settings.policy_settings?.exchangePolicy ?? '',
            deliveryPolicy: settings.policy_settings?.deliveryPolicy ?? '',
            personalizationPolicy:
                settings.policy_settings?.personalizationPolicy ?? '',
            warrantyInfo: settings.policy_settings?.warrantyInfo ?? '',
            cookieNotice: settings.policy_settings?.cookieNotice ?? true,
            lgpdConsent: settings.policy_settings?.lgpdConsent ?? true,
        },
        seo: {
            title: settings.seo_settings?.title ?? '',
            description: settings.seo_settings?.description ?? '',
            keywords: settings.seo_settings?.keywords ?? '',
            googleAnalytics: settings.seo_settings?.googleAnalytics ?? '',
            googleTagManager: settings.seo_settings?.googleTagManager ?? '',
            metaPixel: settings.seo_settings?.metaPixel ?? '',
            sitemapEnabled: settings.seo_settings?.sitemapEnabled ?? true,
            robotsContent: settings.seo_settings?.robotsContent ?? '',
            socialIntegration: settings.seo_settings?.socialIntegration ?? true,
        },
        system: {
            productImportExport:
                settings.system_settings?.productImportExport ?? true,
        },
        logo: null as File | null,
        headerLogo: null as File | null,
        footerLogo: null as File | null,
        favicon: null as File | null,
        shareImage: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/configuracoes', {
            forceFormData: true,
            headers: { 'Idempotency-Key': createIdempotencyKey() },
        });
    };

    return (
        <AdminLayout title="Configuracoes">
            <Head title="Configuracoes da loja" />
            <form onSubmit={submit} className="space-y-6">
                <div className="rounded-xl border border-border bg-white p-4 shadow-[var(--shadow-soft)]">
                    <div className="flex gap-2 overflow-x-auto pb-1">
                        {tabs.map(([value, label]) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => setTab(value)}
                                className={`shrink-0 rounded-lg px-4 py-2 text-sm font-black ${tab === value ? 'bg-navy text-white' : 'bg-bg-soft text-navy hover:bg-yellow'}`}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                </div>

                <section className="rounded-xl border border-border bg-white p-6 shadow-[var(--shadow-soft)]">
                    {tab === 'general' && <GeneralTab form={form} />}
                    {tab === 'appearance' && (
                        <AppearanceTab form={form} settings={settings} />
                    )}
                    {tab === 'products' && <ProductsTab form={form} />}
                    {tab === 'payments' && <PaymentsTab form={form} />}
                    {tab === 'customers' && <CustomersTab form={form} />}
                    {tab === 'promotions' && <PromotionsTab form={form} />}
                    {tab === 'emails' && <EmailsTab form={form} />}
                    {tab === 'policies' && <PoliciesTab form={form} />}
                    {tab === 'seo' && <SeoTab form={form} />}
                    {tab === 'system' && <SystemTab form={form} />}

                    <div className="mt-6 flex justify-end border-t border-border pt-5">
                        <button
                            disabled={form.processing}
                            className="inline-flex items-center gap-2 rounded-lg bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60"
                        >
                            <Save className="h-4 w-4" />{' '}
                            {form.processing
                                ? 'Salvando...'
                                : 'Salvar configuracoes'}
                        </button>
                    </div>
                </section>
            </form>
        </AdminLayout>
    );
}

function GeneralTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <div>
            <Title
                title="Configuracoes gerais"
                text="Dados institucionais e canais de atendimento."
            />
            <div className="grid gap-4 md:grid-cols-2">
                <Field label="Nome da loja">
                    <input
                        className="input"
                        value={form.data.storeName}
                        onChange={(e) =>
                            form.setData('storeName', e.target.value)
                        }
                    />
                </Field>
                <Field label="Razao social">
                    <input
                        className="input"
                        value={form.data.legalName}
                        onChange={(e) =>
                            form.setData('legalName', e.target.value)
                        }
                    />
                </Field>
                <Field label="CNPJ/CPF">
                    <input
                        className="input"
                        value={form.data.documentNumber}
                        onChange={(e) =>
                            form.setData('documentNumber', e.target.value)
                        }
                    />
                </Field>
                <Field label="E-mail">
                    <input
                        className="input"
                        value={form.data.contactEmail}
                        onChange={(e) =>
                            form.setData('contactEmail', e.target.value)
                        }
                    />
                </Field>
                <Field label="Telefone">
                    <input
                        className="input"
                        value={form.data.contactPhone}
                        onChange={(e) =>
                            form.setData('contactPhone', e.target.value)
                        }
                    />
                </Field>
                <Field label="WhatsApp">
                    <input
                        className="input"
                        value={form.data.whatsapp}
                        onChange={(e) =>
                            form.setData('whatsapp', e.target.value)
                        }
                    />
                </Field>
                <Field label="Horario de atendimento">
                    <input
                        className="input"
                        value={form.data.businessHours}
                        onChange={(e) =>
                            form.setData('businessHours', e.target.value)
                        }
                    />
                </Field>
                <Field label="Endereco da empresa">
                    <input
                        className="input"
                        value={form.data.companyAddress}
                        onChange={(e) =>
                            form.setData('companyAddress', e.target.value)
                        }
                    />
                </Field>
            </div>
            <SocialLinks form={form} />
        </div>
    );
}

function AppearanceTab({
    form,
    settings,
}: {
    form: ReturnType<typeof useForm<any>>;
    settings: Settings;
}) {
    return (
        <div>
            <Title
                title="Aparencia da loja"
                text="Cores, logos, imagens de compartilhamento e destaques da vitrine."
            />
            <div className="grid gap-4 md:grid-cols-2">
                <Field label="Cor principal">
                    <ColorInput
                        value={form.data.primaryColor}
                        onChange={(value) =>
                            form.setData('primaryColor', value)
                        }
                    />
                </Field>
                <Field label="Cor secundaria">
                    <ColorInput
                        value={form.data.secondaryColor}
                        onChange={(value) =>
                            form.setData('secondaryColor', value)
                        }
                    />
                </Field>
                <UploadField
                    label="Logo principal"
                    current={settings.logo_url}
                    file={form.data.logo}
                    onChange={(file) => form.setData('logo', file)}
                />
                <UploadField
                    label="Logo do cabecalho"
                    current={settings.header_logo_url}
                    file={form.data.headerLogo}
                    onChange={(file) => form.setData('headerLogo', file)}
                />
                <UploadField
                    label="Logo do rodape"
                    current={settings.footer_logo_url}
                    file={form.data.footerLogo}
                    onChange={(file) => form.setData('footerLogo', file)}
                />
                <UploadField
                    label="Icone do site"
                    current={settings.favicon_url}
                    file={form.data.favicon}
                    onChange={(file) => form.setData('favicon', file)}
                />
                <UploadField
                    label="Imagem de compartilhamento"
                    current={settings.share_image_url}
                    file={form.data.shareImage}
                    onChange={(file) => form.setData('shareImage', file)}
                />
                <Field label="Produtos por pagina">
                    <input
                        className="input"
                        value={String(form.data.appearance.productsPerPage)}
                        onChange={(e) =>
                            form.setData('appearance', {
                                ...form.data.appearance,
                                productsPerPage: e.target.value,
                            })
                        }
                    />
                </Field>
            </div>
            <div className="mt-5 rounded-xl bg-bg-soft p-4">
                <div className="mb-3 text-sm font-bold text-navy">Previa</div>
                <div className="flex flex-wrap items-center gap-3">
                    <div
                        className="grid h-14 w-14 place-items-center rounded-lg text-white"
                        style={{ backgroundColor: form.data.primaryColor }}
                    >
                        <Palette className="h-6 w-6" />
                    </div>
                    <button
                        type="button"
                        className="rounded-md px-5 py-3 font-black text-white"
                        style={{ backgroundColor: form.data.primaryColor }}
                    >
                        Botao principal
                    </button>
                    <button
                        type="button"
                        className="rounded-md px-5 py-3 font-black"
                        style={{
                            backgroundColor: form.data.secondaryColor,
                            color: form.data.primaryColor,
                        }}
                    >
                        Botao destaque
                    </button>
                </div>
            </div>
        </div>
    );
}

function ProductsTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    const data = form.data.products;
    const set = (key: string, value: string | boolean) =>
        form.setData('products', { ...data, [key]: value });
    const allowOutOfStock = Boolean(data.allowOutOfStock);

    return (
        <div>
            <Title
                title="Produtos"
                text="Regras comerciais e operacionais de catalogo."
            />
            <div className="grid gap-4 md:grid-cols-2">
                <Toggle
                    label="Permitir venda sem estoque"
                    checked={allowOutOfStock}
                    onChange={(value) =>
                        form.setData('products', {
                            ...data,
                            allowOutOfStock: value,
                            stockControl: value ? false : data.stockControl,
                        })
                    }
                />
                <Toggle
                    label="Controle de estoque"
                    checked={!allowOutOfStock && Boolean(data.stockControl)}
                    onChange={(value) => set('stockControl', value)}
                    disabled={allowOutOfStock}
                />
                <Field label="Quantidade minima">
                    <input
                        className="input"
                        value={String(data.minQuantity ?? '')}
                        onChange={(e) => set('minQuantity', e.target.value)}
                    />
                </Field>
                <Field label="Quantidade maxima">
                    <input
                        className="input"
                        value={String(data.maxQuantity ?? '')}
                        onChange={(e) => set('maxQuantity', e.target.value)}
                    />
                </Field>
            </div>
            {allowOutOfStock && (
                <div className="mt-4 rounded-lg bg-yellow/30 p-3 text-sm font-semibold text-navy">
                    Venda sem estoque ativa: a vitrine libera compra/encomenda e
                    os alertas de sem estoque ficam ocultos.
                </div>
            )}
        </div>
    );
}

function PaymentsTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <ConfigGrid
            group="payments"
            form={form}
            fields={[
                ['Ativar Pix', 'pixEnabled', 'toggle'],
                ['Ativar cartao', 'cardEnabled', 'toggle'],
                ['Ativar boleto', 'boletoEnabled', 'toggle'],
            ]}
            title="Pagamentos"
            text="Defina quais formas aparecem no checkout e no rodape. A cobranca online sera integrada em uma etapa futura."
        />
    );
}

function CustomersTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <ConfigGrid
            group="customers"
            form={form}
            fields={[
                ['Cadastro obrigatorio', 'registrationRequired', 'toggle'],
                ['Compra como visitante', 'guestCheckout', 'toggle'],
                ['Validar CPF/CNPJ', 'validateDocument', 'toggle'],
                ['Exigir politica de privacidade', 'privacyRequired', 'toggle'],
            ]}
            title="Clientes"
            text="Cadastro, checkout e regras de LGPD."
        />
    );
}

function PromotionsTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <ConfigGrid
            group="promotions"
            form={form}
            fields={[
                ['Permitir cupons', 'couponsEnabled', 'toggle'],
                [
                    'Valor minimo geral do pedido (centavos)',
                    'minimumOrderValue',
                    'text',
                ],
            ]}
            title="Cupons e promocoes"
            text="Regras gerais de descontos e campanhas."
        />
    );
}

function EmailsTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <ConfigGrid
            group="emails"
            form={form}
            fields={[
                ['Remetente nome', 'senderName', 'text'],
                ['Remetente e-mail', 'senderEmail', 'text'],
                ['Novo pedido', 'notifyNewOrder', 'toggle'],
                ['Novo orcamento', 'notifyQuote', 'toggle'],
                [
                    'Destinatarios administrativos (separados por virgula)',
                    'adminRecipients',
                    'text',
                ],
            ]}
            title="E-mails e notificacoes"
            text="Remetente e avisos que ja fazem parte do fluxo de pedido e orcamento."
        />
    );
}

function PoliciesTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <ConfigGrid
            group="policies"
            form={form}
            fields={[
                ['Termos de uso', 'termsUrl', 'text'],
                ['Politica de privacidade', 'privacyUrl', 'text'],
                ['Troca e devolucao', 'exchangePolicy', 'textarea'],
                ['Politica de entrega', 'deliveryPolicy', 'textarea'],
                ['Personalizacao', 'personalizationPolicy', 'textarea'],
                ['Garantia', 'warrantyInfo', 'textarea'],
                ['Aviso de cookies', 'cookieNotice', 'toggle'],
                ['Consentimento LGPD', 'lgpdConsent', 'toggle'],
            ]}
            title="Documentos e politicas"
            text="Textos e regras legais para checkout e rodape."
        />
    );
}

function SeoTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <ConfigGrid
            group="seo"
            form={form}
            fields={[
                ['Titulo da loja', 'title', 'text'],
                ['Descricao padrao', 'description', 'textarea'],
                ['Palavras-chave', 'keywords', 'text'],
                ['Google Analytics', 'googleAnalytics', 'text'],
                ['Google Tag Manager', 'googleTagManager', 'text'],
                ['Meta Pixel', 'metaPixel', 'text'],
                ['Sitemap', 'sitemapEnabled', 'toggle'],
                ['Robots.txt', 'robotsContent', 'textarea'],
                ['Integracao redes sociais', 'socialIntegration', 'toggle'],
            ]}
            title="SEO e marketing"
            text="Metadados, pixels, sitemap e rastreamento."
        />
    );
}

function SystemTab({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <ConfigGrid
            group="system"
            form={form}
            fields={[
                ['Importar/exportar produtos', 'productImportExport', 'toggle'],
            ]}
            title="Sistema"
            text="Recursos operacionais que ja possuem implementacao real no painel."
        />
    );
}

function ConfigGrid({
    title,
    text,
    form,
    group,
    fields,
}: {
    title: string;
    text: string;
    form: ReturnType<typeof useForm<any>>;
    group: string;
    fields: [string, string, 'text' | 'textarea' | 'toggle'][];
}) {
    const data = form.data[group];
    const set = (key: string, value: string | boolean) =>
        form.setData(group, { ...data, [key]: value });

    return (
        <div>
            <Title title={title} text={text} />
            <div className="grid gap-4 md:grid-cols-2">
                {fields.map(([label, key, type]) =>
                    type === 'toggle' ? (
                        <Toggle
                            key={key}
                            label={label}
                            checked={Boolean(data[key])}
                            onChange={(value) => set(key, value)}
                        />
                    ) : (
                        <Field key={key} label={label}>
                            {type === 'textarea' ? (
                                <textarea
                                    rows={3}
                                    className="input"
                                    value={String(data[key] ?? '')}
                                    onChange={(e) => set(key, e.target.value)}
                                />
                            ) : (
                                <input
                                    className="input"
                                    value={String(data[key] ?? '')}
                                    onChange={(e) => set(key, e.target.value)}
                                />
                            )}
                        </Field>
                    ),
                )}
            </div>
        </div>
    );
}

function SocialLinks({ form }: { form: ReturnType<typeof useForm<any>> }) {
    return (
        <section className="mt-5 rounded-xl border border-border bg-bg-soft p-4">
            <div className="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h3 className="font-display text-lg font-black text-navy">
                        Redes sociais
                    </h3>
                    <p className="text-xs text-text-muted">
                        Esses links aparecem no rodape do site.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={() =>
                        form.setData('socialLinks', [
                            ...form.data.socialLinks,
                            { label: '', url: '' },
                        ])
                    }
                    className="inline-flex items-center gap-2 rounded-lg bg-navy px-3 py-2 text-xs font-black text-white"
                >
                    <Plus className="h-4 w-4" /> Adicionar
                </button>
            </div>
            <div className="space-y-3">
                {form.data.socialLinks.map(
                    (link: SocialLink, index: number) => (
                        <div
                            key={index}
                            className="grid gap-3 rounded-lg border border-border bg-white p-3 sm:grid-cols-[160px_1fr_auto]"
                        >
                            <input
                                value={link.label}
                                onChange={(event) =>
                                    form.setData(
                                        'socialLinks',
                                        form.data.socialLinks.map(
                                            (
                                                item: SocialLink,
                                                current: number,
                                            ) =>
                                                current === index
                                                    ? {
                                                          ...item,
                                                          label: event.target
                                                              .value,
                                                      }
                                                    : item,
                                        ),
                                    )
                                }
                                className="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy"
                                placeholder="Instagram"
                            />
                            <input
                                value={link.url}
                                onChange={(event) =>
                                    form.setData(
                                        'socialLinks',
                                        form.data.socialLinks.map(
                                            (
                                                item: SocialLink,
                                                current: number,
                                            ) =>
                                                current === index
                                                    ? {
                                                          ...item,
                                                          url: event.target
                                                              .value,
                                                      }
                                                    : item,
                                        ),
                                    )
                                }
                                className="rounded-lg border border-border px-3 py-2 text-sm outline-none focus:border-navy"
                                placeholder="https://instagram.com/..."
                            />
                            <button
                                type="button"
                                onClick={() =>
                                    form.setData(
                                        'socialLinks',
                                        form.data.socialLinks.filter(
                                            (_: SocialLink, current: number) =>
                                                current !== index,
                                        ),
                                    )
                                }
                                className="rounded-lg p-2 text-red-700 hover:bg-red-50"
                                aria-label="Remover rede social"
                            >
                                <Trash2 className="h-4 w-4" />
                            </button>
                        </div>
                    ),
                )}
            </div>
        </section>
    );
}

function Title({ title, text }: { title: string; text: string }) {
    return (
        <div className="mb-5">
            <h2 className="font-display text-xl font-black text-navy">
                {title}
            </h2>
            <p className="mt-1 text-sm text-text-muted">{text}</p>
        </div>
    );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <label className="block text-sm font-bold text-navy">
            {label}
            <div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:outline-none focus-within:[&_.input]:border-navy">
                {children}
            </div>
        </label>
    );
}

function UploadField({
    label,
    current,
    file,
    onChange,
}: {
    label: string;
    current?: string | null;
    file: File | null;
    onChange: (file: File | null) => void;
}) {
    return (
        <div className="rounded-lg border border-border bg-bg-soft p-3">
            <div className="text-sm font-bold text-navy">{label}</div>
            {current && (
                <img
                    src={current}
                    alt={label}
                    className="mt-2 h-14 w-auto rounded border border-border bg-white p-1"
                />
            )}
            <label className="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-border bg-white px-3 py-2 text-xs font-black text-navy hover:bg-yellow">
                <Upload className="h-4 w-4" /> Enviar arquivo
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/svg+xml,image/x-icon"
                    onChange={(event) =>
                        onChange(event.target.files?.[0] ?? null)
                    }
                    className="sr-only"
                />
            </label>
            {file && (
                <div className="mt-2 text-xs font-semibold text-text-muted">
                    {file.name}
                </div>
            )}
        </div>
    );
}

function Toggle({
    label,
    checked,
    onChange,
    disabled = false,
}: {
    label: string;
    checked: boolean;
    onChange: (value: boolean) => void;
    disabled?: boolean;
}) {
    return (
        <label
            className={`flex items-center gap-2 rounded-lg border border-border p-3 text-sm font-bold ${disabled ? 'bg-slate-100 text-text-muted' : 'bg-bg-soft text-navy'}`}
        >
            <input
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={(e) => onChange(e.target.checked)}
            />{' '}
            {label}
        </label>
    );
}

function ColorInput({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <div className="flex gap-3">
            <input
                type="color"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="h-12 w-16 rounded-lg border border-border bg-white p-1"
            />
            <input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="input"
                placeholder="#123a6b"
            />
        </div>
    );
}
