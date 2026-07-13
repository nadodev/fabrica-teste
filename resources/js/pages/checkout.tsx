import { Link, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Check,
    CheckCircle2,
    CreditCard,
    FileText,
    MapPin,
    PackageCheck,
    Plus,
    ShieldCheck,
    WalletCards,
    Zap,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import {
    formatDocument,
    formatPhone,
    formatPostalCode,
} from '@/lib/input-masks';
import { formatMoney } from '@/modules/catalog/domain/product';

type CartItem = {
    cartItemKey: string;
    sku: string;
    name: string;
    unitPriceAmount: number;
    priceCurrency: string;
    quantity: number;
    subtotalAmount: number;
    imageUrl: string | null;
    variationLabel: string | null;
};

type Cart = {
    items: CartItem[];
    subtotalAmount: number;
    discountAmount: number;
    shippingAmount: number;
    totalAmount: number;
    currency: string;
    coupon: {
        code: string;
        description: string;
        discountAmount: number;
    } | null;
    shipping: ShippingQuote | null;
};

type ShippingQuote = {
    serviceId: string;
    name: string;
    companyName: string;
    priceAmount: number;
    deliveryTime: number;
};

const emptyCart: Cart = {
    items: [],
    subtotalAmount: 0,
    discountAmount: 0,
    shippingAmount: 0,
    totalAmount: 0,
    currency: 'BRL',
    coupon: null,
    shipping: null,
};

type CustomerSettings = {
    validateDocument?: boolean;
    privacyRequired?: boolean;
};
type PolicySettings = { privacyUrl?: string; termsUrl?: string };
type PostalAddressResponse = {
    street: string;
    city: string;
    state: string;
    message?: string;
};
type CustomerProfile = {
    name: string;
    email: string;
    phone: string;
    document: string;
};
type SavedAddress = {
    id: string;
    type: 'personal' | 'shipping';
    label: string;
    postalCode: string;
    street: string;
    number: string;
    city: string;
    state: string;
    isDefault: boolean;
};

export default function Checkout({
    cart = emptyCart,
    shippingZip = '',
    paymentMethods = ['pix', 'credit_card', 'boleto'],
    customerSettings = {},
    policySettings = {},
    customer = null,
    savedAddresses = [],
}: {
    cart?: Cart;
    shippingZip?: string;
    paymentMethods?: string[];
    customerSettings?: CustomerSettings;
    policySettings?: PolicySettings;
    customer?: CustomerProfile | null;
    savedAddresses?: SavedAddress[];
}) {
    const safeCart = {
        ...emptyCart,
        ...cart,
        items: Array.isArray(cart?.items) ? cart.items : [],
    };
    const safeAddresses = Array.isArray(savedAddresses) ? savedAddresses : [];
    const preferredAddress =
        safeAddresses.find(
            (address) => address.type === 'shipping' && address.isDefault,
        ) ??
        safeAddresses.find((address) => address.isDefault) ??
        safeAddresses[0];
    const form = useForm({
        customerName: customer?.name ?? '',
        customerEmail: customer?.email ?? '',
        customerPhone: formatPhone(customer?.phone ?? ''),
        customerDocument: formatDocument(customer?.document ?? ''),
        shippingZip: formatPostalCode(
            preferredAddress?.postalCode || shippingZip || '',
        ),
        shippingAddress: preferredAddress?.street ?? '',
        shippingNumber: preferredAddress?.number ?? '',
        shippingCity: preferredAddress?.city ?? '',
        shippingState: preferredAddress?.state ?? '',
        checkoutType: paymentMethods.length > 0 ? 'payment' : 'quote',
        deliveryMethod: 'shipping',
        paymentMethod: paymentMethods[0] ?? 'combine',
        cardHolderName: '',
        cardNumber: '',
        cardExpiryMonth: '',
        cardExpiryYear: '',
        cardCcv: '',
        notes: '',
        privacyAccepted: false,
    });

    const checkoutError = (form.errors as Record<string, string>).checkout;
    const [addressLookup, setAddressLookup] = useState<{
        status: 'idle' | 'loading' | 'success' | 'error';
        message: string;
    }>({ status: 'idle', message: '' });
    const [selectedAddressId, setSelectedAddressId] = useState(
        preferredAddress?.id ?? '',
    );
    const lastLookupZip = useRef('');
    const addressRequest = useRef(0);
    const shippingNumberInput = useRef<HTMLInputElement>(null);

    const lookupAddress = async (value: string, retry = false) => {
        const zip = value.replace(/\D/g, '');

        if (zip.length !== 8) {
            lastLookupZip.current = '';
            setAddressLookup({ status: 'idle', message: '' });

            return;
        }

        if (!retry && lastLookupZip.current === zip) {
            return;
        }

        lastLookupZip.current = zip;
        const requestId = ++addressRequest.current;
        setAddressLookup({
            status: 'loading',
            message: 'Buscando endereco...',
        });

        try {
            const response = await fetch(
                `/endereco/cep?zip=${encodeURIComponent(zip)}`,
                { headers: { Accept: 'application/json' } },
            );
            const data = (await response.json()) as PostalAddressResponse;

            if (requestId !== addressRequest.current) {
                return;
            }

            if (!response.ok) {
                setAddressLookup({
                    status: 'error',
                    message:
                        data.message || 'Nao foi possivel consultar o CEP.',
                });

                return;
            }

            form.setData((current) => ({
                ...current,
                shippingAddress: data.street || current.shippingAddress,
                shippingCity: data.city || current.shippingCity,
                shippingState: data.state || current.shippingState,
            }));
            setAddressLookup({
                status: 'success',
                message: 'Endereco preenchido pelo CEP.',
            });
            window.requestAnimationFrame(() =>
                shippingNumberInput.current?.focus(),
            );
        } catch {
            if (requestId === addressRequest.current) {
                setAddressLookup({
                    status: 'error',
                    message: 'Nao foi possivel consultar o CEP agora.',
                });
            }
        }
    };

    const setPaymentMethod = (method: string) => {
        form.setData('paymentMethod', method);

        if (method !== 'credit_card') {
            form.setData((current) => ({
                ...current,
                paymentMethod: method,
                cardHolderName: '',
                cardNumber: '',
                cardExpiryMonth: '',
                cardExpiryYear: '',
                cardCcv: '',
            }));
        }
    };

    const selectAddress = (addressId: string) => {
        setSelectedAddressId(addressId);

        const address = safeAddresses.find((item) => item.id === addressId);

        if (!address) {
            form.setData((current) => ({
                ...current,
                shippingZip: '',
                shippingAddress: '',
                shippingNumber: '',
                shippingCity: '',
                shippingState: '',
            }));

            return;
        }

        form.setData((current) => ({
            ...current,
            shippingZip: formatPostalCode(address.postalCode),
            shippingAddress: address.street,
            shippingNumber: address.number,
            shippingCity: address.city,
            shippingState: address.state,
        }));
        setAddressLookup({ status: 'idle', message: '' });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/checkout', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            onError: (errors) => {
                if (errors.cardNumber) {
                    form.setData((current) => ({
                        ...current,
                        cardNumber: '',
                        cardCcv: '',
                    }));
                }
            },
        });
    };

    return (
        <div className="min-h-[70vh] bg-bg-soft">
            <header className="bg-navy text-white">
                <div className="mx-auto max-w-7xl px-4 py-7 md:py-8">
                    <Link
                        href="/carrinho"
                        className="inline-flex items-center gap-2 text-sm font-bold text-white/75 hover:text-yellow"
                    >
                        <ArrowLeft className="h-4 w-4" /> Voltar ao carrinho
                    </Link>
                    <h1 className="mt-3 font-display text-3xl font-black md:text-4xl">
                        Finalizar compra
                    </h1>
                    <p className="mt-2 max-w-2xl text-white/75">
                        Confira seus dados, escolha a entrega e finalize com
                        segurança.
                    </p>
                    <ol className="mt-6 grid max-w-2xl grid-cols-3 gap-2 text-xs font-bold text-white/70 sm:text-sm">
                        {['Seus dados', 'Entrega', 'Pagamento'].map(
                            (step, index) => (
                                <li
                                    key={step}
                                    className="flex items-center gap-2"
                                >
                                    <span className="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white/10 text-yellow ring-1 ring-white/20">
                                        {index + 1}
                                    </span>
                                    <span>{step}</span>
                                </li>
                            ),
                        )}
                    </ol>
                </div>
            </header>

            <div className="mx-auto grid max-w-7xl gap-6 px-4 py-6 md:py-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-8">
                <form onSubmit={submit} className="space-y-5 lg:order-first">
                    {Object.keys(form.errors).length > 0 && (
                        <div
                            role="alert"
                            className="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                        >
                            <AlertCircle className="mt-0.5 h-5 w-5 shrink-0" />
                            <div>
                                <strong className="block font-black">
                                    Revise os campos destacados
                                </strong>
                                <span>
                                    Encontramos{' '}
                                    {Object.keys(form.errors).length}{' '}
                                    {Object.keys(form.errors).length === 1
                                        ? 'informação que precisa de ajuste.'
                                        : 'informações que precisam de ajuste.'}
                                </span>
                            </div>
                        </div>
                    )}

                    <section className="rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
                        <SectionHeading
                            step="1"
                            title="Seus dados"
                            description="Usaremos estas informações para identificar o pedido e falar com você."
                        />
                        <div className="mt-5 grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Nome completo"
                                error={form.errors.customerName}
                            >
                                <input
                                    className={`input ${customer ? 'bg-bg-soft' : ''}`}
                                    autoComplete="name"
                                    placeholder="Nome e sobrenome"
                                    readOnly={customer !== null}
                                    value={form.data.customerName}
                                    onChange={(e) =>
                                        form.setData(
                                            'customerName',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="E-mail"
                                error={form.errors.customerEmail}
                            >
                                <input
                                    type="email"
                                    className={`input ${customer ? 'bg-bg-soft' : ''}`}
                                    autoComplete="email"
                                    placeholder="voce@exemplo.com.br"
                                    readOnly={customer !== null}
                                    value={form.data.customerEmail}
                                    onChange={(e) =>
                                        form.setData(
                                            'customerEmail',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Telefone / WhatsApp"
                                error={form.errors.customerPhone}
                            >
                                <input
                                    className="input"
                                    inputMode="tel"
                                    autoComplete="tel"
                                    maxLength={15}
                                    placeholder="(00) 00000-0000"
                                    value={form.data.customerPhone}
                                    onChange={(e) =>
                                        form.setData(
                                            'customerPhone',
                                            formatPhone(e.target.value),
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label={`CPF/CNPJ${customerSettings.validateDocument ? ' *' : ''}`}
                                error={form.errors.customerDocument}
                            >
                                <input
                                    className="input"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    maxLength={18}
                                    placeholder="000.000.000-00"
                                    value={form.data.customerDocument}
                                    onChange={(e) =>
                                        form.setData(
                                            'customerDocument',
                                            formatDocument(e.target.value),
                                        )
                                    }
                                />
                            </Field>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
                        <SectionHeading
                            step="2"
                            title="Endereço de entrega"
                            description="Confirme onde o pedido deve ser entregue."
                        />
                        {safeCart.shipping && (
                            <div className="mt-5 flex items-center gap-3 rounded-xl bg-green-50 p-3 text-sm text-green-900">
                                <PackageCheck className="h-5 w-5 shrink-0" />
                                <span>
                                    <strong>{safeCart.shipping.name}</strong>{' '}
                                    por {safeCart.shipping.companyName}
                                    {safeCart.shipping.deliveryTime > 0
                                        ? ` · até ${safeCart.shipping.deliveryTime} dias úteis`
                                        : ''}
                                </span>
                            </div>
                        )}
                        {safeAddresses.length > 0 && (
                            <fieldset className="mt-5">
                                <legend className="text-sm font-black text-navy">
                                    Escolha um endereço cadastrado
                                </legend>
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    {safeAddresses.map((address) => (
                                        <label
                                            key={address.id}
                                            className={`flex cursor-pointer items-start gap-3 rounded-xl border-2 p-3 transition ${selectedAddressId === address.id ? 'border-navy bg-bg-soft' : 'border-border hover:border-navy/40'}`}
                                        >
                                            <input
                                                type="radio"
                                                name="savedAddress"
                                                className="sr-only"
                                                checked={
                                                    selectedAddressId ===
                                                    address.id
                                                }
                                                onChange={() =>
                                                    selectAddress(address.id)
                                                }
                                            />
                                            <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-navy" />
                                            <span className="min-w-0 flex-1">
                                                <strong className="block text-sm text-navy">
                                                    {address.label}
                                                    {address.isDefault
                                                        ? ' · Padrão'
                                                        : ''}
                                                </strong>
                                                <span className="mt-0.5 block text-xs text-text-muted">
                                                    {address.street},{' '}
                                                    {address.number} ·{' '}
                                                    {address.city}/
                                                    {address.state}
                                                </span>
                                            </span>
                                            {selectedAddressId ===
                                                address.id && (
                                                <Check className="h-4 w-4 shrink-0 text-navy" />
                                            )}
                                        </label>
                                    ))}
                                    <label
                                        className={`flex cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition ${selectedAddressId === '' ? 'border-navy bg-bg-soft' : 'border-border hover:border-navy/40'}`}
                                    >
                                        <input
                                            type="radio"
                                            name="savedAddress"
                                            className="sr-only"
                                            checked={selectedAddressId === ''}
                                            onChange={() => selectAddress('')}
                                        />
                                        <Plus className="h-4 w-4 text-navy" />
                                        <strong className="text-sm text-navy">
                                            Preencher outro endereço
                                        </strong>
                                    </label>
                                </div>
                            </fieldset>
                        )}
                        <div className="mt-5 grid gap-4 sm:grid-cols-2">
                            <Field label="CEP" error={form.errors.shippingZip}>
                                <input
                                    className="input"
                                    inputMode="numeric"
                                    autoComplete="postal-code"
                                    maxLength={9}
                                    placeholder="00000-000"
                                    value={form.data.shippingZip}
                                    onChange={(e) => {
                                        const value = formatPostalCode(
                                            e.target.value,
                                        );
                                        form.setData('shippingZip', value);
                                        void lookupAddress(value);
                                    }}
                                    onBlur={() =>
                                        void lookupAddress(
                                            form.data.shippingZip,
                                            addressLookup.status === 'error',
                                        )
                                    }
                                />
                                {addressLookup.status !== 'idle' && (
                                    <span
                                        className={`mt-1 block text-xs ${addressLookup.status === 'error' ? 'text-red-700' : 'text-text-muted'}`}
                                        role={
                                            addressLookup.status === 'error'
                                                ? 'alert'
                                                : 'status'
                                        }
                                    >
                                        {addressLookup.message}
                                    </span>
                                )}
                            </Field>
                            <Field
                                label="Endereco"
                                error={form.errors.shippingAddress}
                            >
                                <input
                                    className="input"
                                    autoComplete="street-address"
                                    placeholder="Rua, avenida ou rodovia"
                                    value={form.data.shippingAddress}
                                    onChange={(e) =>
                                        form.setData(
                                            'shippingAddress',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Numero"
                                error={form.errors.shippingNumber}
                            >
                                <input
                                    ref={shippingNumberInput}
                                    className="input"
                                    autoComplete="address-line2"
                                    placeholder="Número ou S/N"
                                    value={form.data.shippingNumber}
                                    onChange={(e) =>
                                        form.setData(
                                            'shippingNumber',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Cidade"
                                error={form.errors.shippingCity}
                            >
                                <input
                                    className="input"
                                    autoComplete="address-level2"
                                    value={form.data.shippingCity}
                                    onChange={(e) =>
                                        form.setData(
                                            'shippingCity',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Estado"
                                error={form.errors.shippingState}
                            >
                                <input
                                    className="input"
                                    autoComplete="address-level1"
                                    maxLength={2}
                                    value={form.data.shippingState}
                                    onChange={(e) =>
                                        form.setData(
                                            'shippingState',
                                            e.target.value
                                                .replace(/[^a-z]/gi, '')
                                                .slice(0, 2)
                                                .toUpperCase(),
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <Field
                            label="Observações do pedido (opcional)"
                            error={form.errors.notes}
                        >
                            <textarea
                                rows={3}
                                className="input"
                                placeholder="Ex.: referência do endereço ou orientação para a produção"
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData('notes', e.target.value)
                                }
                            />
                        </Field>
                    </section>

                    <section className="rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
                        <SectionHeading
                            step="3"
                            title="Como deseja finalizar?"
                            description="Pague agora ou solicite um orçamento sem cobrança."
                        />
                        <div className="mt-5 grid gap-3 sm:grid-cols-2">
                            <label
                                className={`relative flex cursor-pointer gap-3 rounded-xl border-2 p-4 transition ${form.data.checkoutType === 'payment' ? 'border-navy bg-bg-soft shadow-sm' : 'border-border hover:border-navy/40'}`}
                            >
                                <input
                                    type="radio"
                                    className="sr-only"
                                    checked={
                                        form.data.checkoutType === 'payment'
                                    }
                                    onChange={() => {
                                        form.setData('checkoutType', 'payment');
                                        setPaymentMethod(
                                            paymentMethods[0] ?? 'combine',
                                        );
                                    }}
                                    disabled={paymentMethods.length === 0}
                                />
                                <WalletCards className="h-5 w-5 shrink-0 text-navy" />
                                <span>
                                    <strong className="block text-sm text-navy">
                                        Finalizar a compra
                                    </strong>
                                    <span className="mt-1 block text-xs text-text-muted">
                                        Escolha Pix, cartão ou boleto e faça o
                                        pagamento agora.
                                    </span>
                                </span>
                                {form.data.checkoutType === 'payment' && (
                                    <Check className="ml-auto h-5 w-5 shrink-0 text-navy" />
                                )}
                            </label>
                            <label
                                className={`relative flex cursor-pointer gap-3 rounded-xl border-2 p-4 transition ${form.data.checkoutType === 'quote' ? 'border-navy bg-bg-soft shadow-sm' : 'border-border hover:border-navy/40'}`}
                            >
                                <input
                                    type="radio"
                                    className="sr-only"
                                    checked={form.data.checkoutType === 'quote'}
                                    onChange={() => {
                                        form.setData('checkoutType', 'quote');
                                        setPaymentMethod('combine');
                                    }}
                                />
                                <FileText className="h-5 w-5 shrink-0 text-navy" />
                                <span>
                                    <strong className="block text-sm text-navy">
                                        Solicitar orçamento
                                    </strong>
                                    <span className="mt-1 block text-xs text-text-muted">
                                        Envie o pedido para análise sem realizar
                                        cobrança agora.
                                    </span>
                                </span>
                                {form.data.checkoutType === 'quote' && (
                                    <Check className="ml-auto h-5 w-5 shrink-0 text-navy" />
                                )}
                            </label>
                        </div>

                        {form.data.checkoutType === 'payment' && (
                            <>
                                <fieldset className="mt-6">
                                    <legend className="text-sm font-black text-navy">
                                        Forma de pagamento
                                    </legend>
                                    {form.errors.paymentMethod && (
                                        <p className="mt-1 text-xs text-red-700">
                                            {form.errors.paymentMethod}
                                        </p>
                                    )}
                                    <div className="mt-3 grid gap-3 sm:grid-cols-3">
                                        {paymentMethods.includes('pix') && (
                                            <PaymentOption
                                                value="pix"
                                                title="Pix"
                                                description="QR Code na hora"
                                                selected={
                                                    form.data.paymentMethod ===
                                                    'pix'
                                                }
                                                onChange={setPaymentMethod}
                                                icon={
                                                    <Zap className="h-5 w-5" />
                                                }
                                            />
                                        )}
                                        {paymentMethods.includes(
                                            'credit_card',
                                        ) && (
                                            <PaymentOption
                                                value="credit_card"
                                                title="Cartão"
                                                description="Resposta imediata"
                                                selected={
                                                    form.data.paymentMethod ===
                                                    'credit_card'
                                                }
                                                onChange={setPaymentMethod}
                                                icon={
                                                    <CreditCard className="h-5 w-5" />
                                                }
                                            />
                                        )}
                                        {paymentMethods.includes('boleto') && (
                                            <PaymentOption
                                                value="boleto"
                                                title="Boleto"
                                                description="Gerado ao finalizar"
                                                selected={
                                                    form.data.paymentMethod ===
                                                    'boleto'
                                                }
                                                onChange={setPaymentMethod}
                                                icon={
                                                    <FileText className="h-5 w-5" />
                                                }
                                            />
                                        )}
                                    </div>
                                </fieldset>
                                {form.data.paymentMethod === 'credit_card' && (
                                    <div className="mt-4 rounded-xl border border-border bg-bg-soft p-4 md:p-5">
                                        <div className="flex items-start gap-3">
                                            <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-green-700" />
                                            <div>
                                                <h3 className="font-display text-lg font-black text-navy">
                                                    Dados do cartão
                                                </h3>
                                                <p className="mt-1 text-xs text-text-muted">
                                                    Seus dados são usados
                                                    somente nesta tentativa e
                                                    não ficam salvos na loja.
                                                </p>
                                            </div>
                                        </div>
                                        <div className="mt-2 grid gap-x-4 sm:grid-cols-2">
                                            <div className="sm:col-span-2">
                                                <Field
                                                    label="Nome impresso no cartão"
                                                    error={
                                                        form.errors
                                                            .cardHolderName
                                                    }
                                                >
                                                    <input
                                                        className="input"
                                                        autoComplete="cc-name"
                                                        placeholder="Como aparece no cartão"
                                                        value={
                                                            form.data
                                                                .cardHolderName
                                                        }
                                                        onChange={(event) =>
                                                            form.setData(
                                                                'cardHolderName',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </Field>
                                            </div>
                                            <div className="sm:col-span-2">
                                                <Field
                                                    label="Número do cartão"
                                                    error={
                                                        form.errors.cardNumber
                                                    }
                                                >
                                                    <input
                                                        className="input"
                                                        inputMode="numeric"
                                                        autoComplete="cc-number"
                                                        maxLength={19}
                                                        placeholder="Somente números"
                                                        value={
                                                            form.data.cardNumber
                                                        }
                                                        onChange={(event) =>
                                                            form.setData(
                                                                'cardNumber',
                                                                event.target.value.replace(
                                                                    /\D/g,
                                                                    '',
                                                                ),
                                                            )
                                                        }
                                                    />
                                                </Field>
                                            </div>
                                            <Field
                                                label="Mês (MM)"
                                                error={
                                                    form.errors.cardExpiryMonth
                                                }
                                            >
                                                <input
                                                    className="input"
                                                    inputMode="numeric"
                                                    autoComplete="cc-exp-month"
                                                    maxLength={2}
                                                    placeholder="MM"
                                                    value={
                                                        form.data
                                                            .cardExpiryMonth
                                                    }
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'cardExpiryMonth',
                                                            event.target.value.replace(
                                                                /\D/g,
                                                                '',
                                                            ),
                                                        )
                                                    }
                                                />
                                            </Field>
                                            <Field
                                                label="Ano (AAAA)"
                                                error={
                                                    form.errors.cardExpiryYear
                                                }
                                            >
                                                <input
                                                    className="input"
                                                    inputMode="numeric"
                                                    autoComplete="cc-exp-year"
                                                    maxLength={4}
                                                    placeholder="AAAA"
                                                    value={
                                                        form.data.cardExpiryYear
                                                    }
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'cardExpiryYear',
                                                            event.target.value.replace(
                                                                /\D/g,
                                                                '',
                                                            ),
                                                        )
                                                    }
                                                />
                                            </Field>
                                            <Field
                                                label="Código de segurança"
                                                error={form.errors.cardCcv}
                                            >
                                                <input
                                                    type="password"
                                                    className="input"
                                                    inputMode="numeric"
                                                    autoComplete="cc-csc"
                                                    maxLength={4}
                                                    placeholder="CVV"
                                                    value={form.data.cardCcv}
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'cardCcv',
                                                            event.target.value.replace(
                                                                /\D/g,
                                                                '',
                                                            ),
                                                        )
                                                    }
                                                />
                                            </Field>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}

                        {customerSettings.privacyRequired !== false && (
                            <label className="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-bg-soft p-4 text-sm text-text-muted">
                                <input
                                    type="checkbox"
                                    checked={form.data.privacyAccepted}
                                    onChange={(e) =>
                                        form.setData(
                                            'privacyAccepted',
                                            e.target.checked,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 accent-navy"
                                />
                                <span>
                                    Li e aceito a{' '}
                                    <Link
                                        href={
                                            policySettings.privacyUrl ||
                                            '/privacidade'
                                        }
                                        className="font-bold text-navy underline"
                                    >
                                        politica de privacidade
                                    </Link>{' '}
                                    e os{' '}
                                    <Link
                                        href={
                                            policySettings.termsUrl || '/termos'
                                        }
                                        className="font-bold text-navy underline"
                                    >
                                        termos de uso
                                    </Link>
                                    .
                                </span>
                            </label>
                        )}
                        {form.errors.privacyAccepted && (
                            <div className="mt-2 text-xs font-semibold text-red-700">
                                {form.errors.privacyAccepted}
                            </div>
                        )}

                        {checkoutError && (
                            <div
                                role="alert"
                                className="mt-4 flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-800"
                            >
                                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                {checkoutError}
                            </div>
                        )}

                        <button
                            disabled={form.processing}
                            className="mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-yellow px-6 py-3.5 font-black text-navy shadow-sm transition hover:brightness-95 disabled:cursor-wait disabled:opacity-60"
                        >
                            <CheckCircle2 className="h-5 w-5" />{' '}
                            {form.processing
                                ? 'Finalizando...'
                                : form.data.checkoutType === 'quote'
                                  ? 'Solicitar orçamento'
                                  : form.data.paymentMethod === 'pix'
                                    ? 'Pagar com Pix'
                                    : form.data.paymentMethod === 'credit_card'
                                      ? 'Pagar com cartão'
                                      : form.data.paymentMethod === 'boleto'
                                        ? 'Gerar boleto'
                                        : 'Finalizar pedido'}
                        </button>
                        <p className="mt-3 flex items-center justify-center gap-2 text-center text-xs text-text-muted">
                            <ShieldCheck className="h-4 w-4" />
                            Ambiente seguro. Seus dados são protegidos durante o
                            envio.
                        </p>
                    </section>
                </form>

                <aside className="order-first h-fit rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] lg:sticky lg:top-32 lg:order-last">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="font-display text-lg font-black text-navy">
                            Resumo do pedido
                        </h2>
                        <span className="rounded-full bg-bg-soft px-2.5 py-1 text-xs font-bold text-text-muted">
                            {safeCart.items.reduce(
                                (total, item) => total + item.quantity,
                                0,
                            )}{' '}
                            {safeCart.items.reduce(
                                (total, item) => total + item.quantity,
                                0,
                            ) === 1
                                ? 'item'
                                : 'itens'}
                        </span>
                    </div>
                    <div className="mt-3 rounded-xl bg-bg-soft p-3 text-xs font-bold text-navy">
                        {form.data.checkoutType === 'quote'
                            ? 'Orçamento: nenhuma cobrança será feita agora.'
                            : form.data.paymentMethod === 'pix'
                              ? 'Pagamento escolhido: Pix'
                              : form.data.paymentMethod === 'credit_card'
                                ? 'Pagamento escolhido: cartão de crédito'
                                : 'Pagamento escolhido: boleto'}
                    </div>
                    <div className="mt-4 space-y-4">
                        {safeCart.items.map((item) => (
                            <div
                                key={item.cartItemKey}
                                className="flex gap-3 border-b border-border pb-4 last:border-0"
                            >
                                <div className="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-border bg-bg-soft">
                                    {item.imageUrl ? (
                                        <img
                                            src={item.imageUrl}
                                            alt=""
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        <PackageCheck className="m-auto h-full w-5 text-text-muted" />
                                    )}
                                </div>
                                <div className="flex-1">
                                    <div className="font-bold text-navy">
                                        {item.name}
                                    </div>
                                    {item.variationLabel && (
                                        <div className="text-xs font-semibold text-text-muted">
                                            {item.variationLabel}
                                        </div>
                                    )}
                                    <div className="mt-1 text-xs text-text-muted">
                                        {item.quantity} x{' '}
                                        {formatMoney(
                                            item.unitPriceAmount,
                                            item.priceCurrency,
                                        )}
                                    </div>
                                </div>
                                <div className="font-bold text-navy">
                                    {formatMoney(
                                        item.subtotalAmount,
                                        item.priceCurrency,
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="mt-5 space-y-3 border-t border-border pt-5">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-text-muted">Subtotal</span>
                            <strong className="text-navy">
                                {formatMoney(
                                    safeCart.subtotalAmount,
                                    safeCart.currency,
                                )}
                            </strong>
                        </div>
                        {safeCart.discountAmount > 0 && (
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-text-muted">
                                    Desconto{' '}
                                    {safeCart.coupon
                                        ? `(${safeCart.coupon.code})`
                                        : ''}
                                </span>
                                <strong className="text-green-700">
                                    -{' '}
                                    {formatMoney(
                                        safeCart.discountAmount,
                                        safeCart.currency,
                                    )}
                                </strong>
                            </div>
                        )}
                        {safeCart.shippingAmount > 0 && (
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-text-muted">
                                    Frete{' '}
                                    {safeCart.shipping
                                        ? `(${safeCart.shipping.companyName})`
                                        : ''}
                                </span>
                                <strong className="text-navy">
                                    {formatMoney(
                                        safeCart.shippingAmount,
                                        safeCart.currency,
                                    )}
                                </strong>
                            </div>
                        )}
                    </div>
                    <div className="mt-5 flex items-end justify-between border-t border-border pt-5">
                        <span className="text-text-muted">Total</span>
                        <strong className="font-display text-2xl text-navy">
                            {formatMoney(
                                safeCart.totalAmount,
                                safeCart.currency,
                            )}
                        </strong>
                    </div>
                    <div className="mt-5 flex items-center gap-2 rounded-lg bg-bg-soft p-3 text-xs text-text-muted">
                        {form.data.checkoutType === 'quote' ? (
                            <FileText className="h-4 w-4 shrink-0 text-navy" />
                        ) : (
                            <ShieldCheck className="h-4 w-4 shrink-0 text-navy" />
                        )}
                        {form.data.checkoutType === 'quote'
                            ? 'Você receberá o orçamento para análise antes de qualquer pagamento.'
                            : 'Pagamento protegido e processado pelo Asaas.'}
                    </div>
                </aside>
            </div>
        </div>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="mt-4 block text-sm font-bold text-navy">
            {label}
            <div
                className={`mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:transition [&_.input]:outline-none [&_.input]:placeholder:text-text-muted/70 focus-within:[&_.input]:border-navy focus-within:[&_.input]:ring-2 focus-within:[&_.input]:ring-navy/10 ${error ? '[&_.input]:border-red-400' : '[&_.input]:border-border'}`}
            >
                {children}
            </div>
            {error && (
                <span
                    role="alert"
                    className="mt-1 block text-xs font-semibold text-red-700"
                >
                    {error}
                </span>
            )}
        </label>
    );
}

function SectionHeading({
    step,
    title,
    description,
}: {
    step: string;
    title: string;
    description: string;
}) {
    return (
        <div className="flex gap-3">
            <span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-navy font-display text-sm font-black text-yellow">
                {step}
            </span>
            <div>
                <h2 className="font-display text-xl font-black text-navy">
                    {title}
                </h2>
                <p className="mt-1 text-sm text-text-muted">{description}</p>
            </div>
        </div>
    );
}

function PaymentOption({
    value,
    title,
    description,
    selected,
    onChange,
    icon,
}: {
    value: string;
    title: string;
    description: string;
    selected: boolean;
    onChange: (value: string) => void;
    icon: ReactNode;
}) {
    return (
        <label
            className={`relative flex cursor-pointer items-start gap-3 rounded-xl border-2 p-3 transition ${selected ? 'border-navy bg-bg-soft shadow-sm' : 'border-border hover:border-navy/40'}`}
        >
            <input
                type="radio"
                name="paymentMethod"
                value={value}
                checked={selected}
                onChange={() => onChange(value)}
                className="sr-only"
            />
            <span className="text-navy">{icon}</span>
            <span>
                <strong className="block text-sm text-navy">{title}</strong>
                <span className="mt-0.5 block text-xs text-text-muted">
                    {description}
                </span>
            </span>
            {selected && (
                <Check className="ml-auto h-4 w-4 shrink-0 text-navy" />
            )}
        </label>
    );
}
