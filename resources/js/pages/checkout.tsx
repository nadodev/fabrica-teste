import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ShieldCheck } from 'lucide-react';
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

export default function Checkout({
    cart = emptyCart,
    shippingZip = '',
    paymentMethods = ['pix', 'credit_card', 'boleto'],
    customerSettings = {},
    policySettings = {},
}: {
    cart?: Cart;
    shippingZip?: string;
    paymentMethods?: string[];
    customerSettings?: CustomerSettings;
    policySettings?: PolicySettings;
}) {
    const safeCart = {
        ...emptyCart,
        ...cart,
        items: Array.isArray(cart?.items) ? cart.items : [],
    };
    const form = useForm({
        customerName: '',
        customerEmail: '',
        customerPhone: '',
        customerDocument: '',
        shippingZip: formatPostalCode(shippingZip ?? ''),
        shippingAddress: '',
        shippingNumber: '',
        shippingCity: '',
        shippingState: '',
        checkoutType: 'quote',
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

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/checkout', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
        });
    };

    return (
        <div className="min-h-[70vh] bg-bg-soft">
            <header className="bg-navy text-white">
                <div className="mx-auto max-w-7xl px-4 py-8">
                    <Link
                        href="/carrinho"
                        className="inline-flex items-center gap-2 text-sm font-bold text-white/75 hover:text-yellow"
                    >
                        <ArrowLeft className="h-4 w-4" /> Voltar ao carrinho
                    </Link>
                    <h1 className="mt-3 font-display text-3xl font-black md:text-4xl">
                        Finalizar compra
                    </h1>
                    <p className="mt-2 text-white/75">
                        Preencha os dados para gerar o pedido e concluir o
                        pagamento com seguranca.
                    </p>
                </div>
            </header>

            <main className="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-[1fr_380px]">
                <form
                    onSubmit={submit}
                    className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6"
                >
                    <h2 className="font-display text-xl font-black text-navy">
                        Dados do cliente
                    </h2>
                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                        <Field
                            label="Nome completo"
                            error={form.errors.customerName}
                        >
                            <input
                                className="input"
                                value={form.data.customerName}
                                onChange={(e) =>
                                    form.setData('customerName', e.target.value)
                                }
                            />
                        </Field>
                        <Field label="E-mail" error={form.errors.customerEmail}>
                            <input
                                type="email"
                                className="input"
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
                                maxLength={18}
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

                    <h2 className="mt-8 font-display text-xl font-black text-navy">
                        Entrega
                    </h2>
                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        <label
                            className={`rounded-lg border p-4 text-sm ${form.data.deliveryMethod === 'shipping' ? 'border-navy bg-bg-soft' : 'border-border'}`}
                        >
                            <input
                                type="radio"
                                className="mr-2"
                                checked={
                                    form.data.deliveryMethod === 'shipping'
                                }
                                onChange={() =>
                                    form.setData('deliveryMethod', 'shipping')
                                }
                            />
                            Envio para endereco
                        </label>
                        <label
                            className={`rounded-lg border p-4 text-sm ${form.data.deliveryMethod === 'pickup' ? 'border-navy bg-bg-soft' : 'border-border'}`}
                        >
                            <input
                                type="radio"
                                className="mr-2"
                                checked={form.data.deliveryMethod === 'pickup'}
                                onChange={() =>
                                    form.setData('deliveryMethod', 'pickup')
                                }
                            />
                            Retirada na loja
                        </label>
                    </div>
                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                        <Field label="CEP" error={form.errors.shippingZip}>
                            <input
                                className="input"
                                inputMode="numeric"
                                autoComplete="postal-code"
                                maxLength={9}
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
                                value={form.data.shippingNumber}
                                onChange={(e) =>
                                    form.setData(
                                        'shippingNumber',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field label="Cidade" error={form.errors.shippingCity}>
                            <input
                                className="input"
                                value={form.data.shippingCity}
                                onChange={(e) =>
                                    form.setData('shippingCity', e.target.value)
                                }
                            />
                        </Field>
                        <Field label="Estado" error={form.errors.shippingState}>
                            <input
                                className="input"
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
                    <Field label="Observacoes" error={form.errors.notes}>
                        <textarea
                            rows={4}
                            className="input"
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                        />
                    </Field>

                    <h2 className="mt-8 font-display text-xl font-black text-navy">
                        Finalizacao
                    </h2>
                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        <label
                            className={`rounded-lg border p-4 text-sm ${form.data.checkoutType === 'quote' ? 'border-navy bg-bg-soft' : 'border-border'}`}
                        >
                            <input
                                type="radio"
                                className="mr-2"
                                checked={form.data.checkoutType === 'quote'}
                                onChange={() => {
                                    form.setData('checkoutType', 'quote');
                                    setPaymentMethod('combine');
                                }}
                            />
                            Gerar orcamento
                            <span className="mt-1 block text-xs text-text-muted">
                                Salva no admin e envia por e-mail sem pagamento
                                agora.
                            </span>
                        </label>
                        <label
                            className={`rounded-lg border p-4 text-sm ${form.data.checkoutType === 'payment' ? 'border-navy bg-bg-soft' : 'border-border'}`}
                        >
                            <input
                                type="radio"
                                className="mr-2"
                                checked={form.data.checkoutType === 'payment'}
                                onChange={() => {
                                    form.setData('checkoutType', 'payment');
                                    setPaymentMethod(
                                        paymentMethods[0] ?? 'combine',
                                    );
                                }}
                                disabled={paymentMethods.length === 0}
                            />
                            Fazer pedido
                            <span className="mt-1 block text-xs text-text-muted">
                                {paymentMethods.length > 0
                                    ? 'Escolha uma das formas habilitadas pela loja. Pagamento online entra depois.'
                                    : 'Nenhuma forma de pagamento habilitada.'}
                            </span>
                        </label>
                    </div>

                    {form.data.checkoutType === 'payment' && (
                        <>
                            <h2 className="mt-8 font-display text-xl font-black text-navy">
                                Pagamento
                            </h2>
                            <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                <Field
                                    label="Forma de pagamento"
                                    error={form.errors.paymentMethod}
                                >
                                    <select
                                        className="input"
                                        value={form.data.paymentMethod}
                                        onChange={(e) =>
                                            setPaymentMethod(e.target.value)
                                        }
                                    >
                                        {paymentMethods.includes('pix') && (
                                            <option value="pix">Pix</option>
                                        )}
                                        {paymentMethods.includes(
                                            'credit_card',
                                        ) && (
                                            <option value="credit_card">
                                                Cartao de credito
                                            </option>
                                        )}
                                        {paymentMethods.includes('boleto') && (
                                            <option value="boleto">
                                                Boleto
                                            </option>
                                        )}
                                    </select>
                                </Field>
                            </div>
                            {form.data.paymentMethod === 'credit_card' && (
                                <div className="mt-4 rounded-xl border border-border bg-bg-soft p-4">
                                    <h3 className="font-display text-lg font-black text-navy">
                                        Dados do cartao
                                    </h3>
                                    <p className="mt-1 text-xs text-text-muted">
                                        Os dados sao enviados diretamente para
                                        processar esta compra e nao ficam salvos
                                        na loja.
                                    </p>
                                    <div className="grid gap-x-4 sm:grid-cols-2">
                                        <Field
                                            label="Nome impresso no cartao"
                                            error={form.errors.cardHolderName}
                                        >
                                            <input
                                                className="input"
                                                autoComplete="cc-name"
                                                value={form.data.cardHolderName}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'cardHolderName',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                        <Field
                                            label="Numero do cartao"
                                            error={form.errors.cardNumber}
                                        >
                                            <input
                                                className="input"
                                                inputMode="numeric"
                                                autoComplete="cc-number"
                                                maxLength={19}
                                                value={form.data.cardNumber}
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
                                        <Field
                                            label="Mes de validade (MM)"
                                            error={form.errors.cardExpiryMonth}
                                        >
                                            <input
                                                className="input"
                                                inputMode="numeric"
                                                autoComplete="cc-exp-month"
                                                maxLength={2}
                                                placeholder="MM"
                                                value={
                                                    form.data.cardExpiryMonth
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
                                            label="Ano de validade (AAAA)"
                                            error={form.errors.cardExpiryYear}
                                        >
                                            <input
                                                className="input"
                                                inputMode="numeric"
                                                autoComplete="cc-exp-year"
                                                maxLength={4}
                                                placeholder="AAAA"
                                                value={form.data.cardExpiryYear}
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
                                            label="Codigo de seguranca"
                                            error={form.errors.cardCcv}
                                        >
                                            <input
                                                type="password"
                                                className="input"
                                                inputMode="numeric"
                                                autoComplete="cc-csc"
                                                maxLength={4}
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
                        <label className="mt-5 flex items-start gap-3 rounded-lg bg-bg-soft p-3 text-sm text-text-muted">
                            <input
                                type="checkbox"
                                checked={form.data.privacyAccepted}
                                onChange={(e) =>
                                    form.setData(
                                        'privacyAccepted',
                                        e.target.checked,
                                    )
                                }
                                className="mt-1"
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
                                    href={policySettings.termsUrl || '/termos'}
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
                        <div className="mt-4 rounded-lg bg-red-50 p-3 text-sm font-semibold text-red-800">
                            {checkoutError}
                        </div>
                    )}

                    <button
                        disabled={form.processing}
                        className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60"
                    >
                        <CheckCircle2 className="h-5 w-5" />{' '}
                        {form.processing
                            ? 'Finalizando...'
                            : form.data.checkoutType === 'quote'
                              ? 'Gerar orcamento'
                              : 'Gerar pedido'}
                    </button>
                </form>

                <aside className="h-fit rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] lg:sticky lg:top-32">
                    <h2 className="font-display text-lg font-black text-navy">
                        Resumo
                    </h2>
                    <div className="mt-3 rounded-lg bg-bg-soft p-3 text-xs font-bold text-navy">
                        {form.data.checkoutType === 'quote'
                            ? 'Tipo: orcamento sem pagamento agora'
                            : 'Tipo: pedido com pagamento a combinar'}
                    </div>
                    <div className="mt-4 space-y-4">
                        {safeCart.items.map((item) => (
                            <div
                                key={item.cartItemKey}
                                className="flex gap-3 border-b border-border pb-4 last:border-0"
                            >
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
                        <ShieldCheck className="h-4 w-4 text-navy" /> Pagamento
                        protegido e processado pelo Asaas.
                    </div>
                </aside>
            </main>
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
            <div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:outline-none focus-within:[&_.input]:border-navy">
                {children}
            </div>
            {error && (
                <span className="mt-1 block text-xs text-red-700">{error}</span>
            )}
        </label>
    );
}
