import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    Home,
    LogOut,
    MapPin,
    PackageCheck,
    Pencil,
    Plus,
    Trash2,
    UserRound,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import {
    formatDocument,
    formatPhone,
    formatPostalCode,
} from '@/lib/input-masks';
import { formatMoney } from '@/modules/catalog/domain/product';

type Profile = { name: string; email: string; phone: string; document: string };
type Address = {
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
type AddressForm = Omit<Address, 'id'>;
type Order = {
    id: string;
    number: string;
    status: string;
    checkoutType: string;
    totalAmount: number;
    currency: string;
    paymentMethod: string | null;
    paymentStatus: string | null;
    createdAt: string;
};

const emptyAddress: AddressForm = {
    type: 'shipping',
    label: '',
    postalCode: '',
    street: '',
    number: '',
    city: '',
    state: '',
    isDefault: true,
};

export default function CustomerAccount({
    profile,
    addresses = [],
    orders = [],
}: {
    profile: Profile;
    addresses?: Address[];
    orders?: Order[];
}) {
    const safeAddresses = Array.isArray(addresses) ? addresses : [];
    const safeOrders = Array.isArray(orders) ? orders : [];
    const [editingAddressId, setEditingAddressId] = useState<string | null>(
        null,
    );
    const [showAddressForm, setShowAddressForm] = useState(
        safeAddresses.length === 0,
    );
    const profileForm = useForm({
        name: profile.name ?? '',
        phone: formatPhone(profile.phone ?? ''),
        document: formatDocument(profile.document ?? ''),
    });
    const addressForm = useForm<AddressForm>({ ...emptyAddress });

    const submitProfile = (event: FormEvent) => {
        event.preventDefault();
        profileForm.put('/minha-conta/perfil', { preserveScroll: true });
    };

    const newAddress = () => {
        setEditingAddressId(null);
        addressForm.setData({ ...emptyAddress });
        addressForm.clearErrors();
        setShowAddressForm(true);
    };

    const editAddress = (address: Address) => {
        setEditingAddressId(address.id);
        addressForm.setData({
            type: address.type,
            label: address.label,
            postalCode: formatPostalCode(address.postalCode),
            street: address.street,
            number: address.number,
            city: address.city,
            state: address.state,
            isDefault: address.isDefault,
        });
        addressForm.clearErrors();
        setShowAddressForm(true);
    };

    const submitAddress = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setShowAddressForm(false);
                setEditingAddressId(null);
                addressForm.setData({ ...emptyAddress });
            },
        };

        if (editingAddressId) {
            addressForm.put(
                `/minha-conta/enderecos/${editingAddressId}`,
                options,
            );
        } else {
            addressForm.post('/minha-conta/enderecos', options);
        }
    };

    const lookupAddress = async (postalCode: string) => {
        const zip = postalCode.replace(/\D/g, '');

        if (zip.length !== 8) {
            return;
        }

        const response = await fetch(`/endereco/cep?zip=${zip}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as {
            street: string;
            city: string;
            state: string;
        };
        addressForm.setData((current) => ({
            ...current,
            street: data.street || current.street,
            city: data.city || current.city,
            state: data.state || current.state,
        }));
    };

    return (
        <main className="mx-auto max-w-7xl px-4 py-10">
            <Head title="Minha conta" />
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="font-display text-3xl font-black text-navy">
                        Minha conta
                    </h1>
                    <p className="mt-1 text-sm text-text-muted">
                        Seus dados, endereços, pedidos e orçamentos em um só
                        lugar.
                    </p>
                </div>
                <button
                    onClick={() => router.post('/sair')}
                    className="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-bold text-navy hover:bg-bg-soft"
                >
                    <LogOut className="h-4 w-4" /> Sair
                </button>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
                    <SectionTitle
                        icon={<UserRound className="h-5 w-5" />}
                        title="Dados pessoais"
                        description="Essas informações serão preenchidas automaticamente no checkout."
                    />
                    <form onSubmit={submitProfile} className="mt-5 grid gap-4">
                        <Field
                            label="Nome completo"
                            error={profileForm.errors.name}
                        >
                            <input
                                className="input"
                                autoComplete="name"
                                value={profileForm.data.name}
                                onChange={(event) =>
                                    profileForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field label="E-mail da conta">
                            <input
                                className="input bg-bg-soft"
                                value={profile.email}
                                disabled
                            />
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Telefone / WhatsApp"
                                error={profileForm.errors.phone}
                            >
                                <input
                                    className="input"
                                    inputMode="tel"
                                    maxLength={15}
                                    value={profileForm.data.phone}
                                    onChange={(event) =>
                                        profileForm.setData(
                                            'phone',
                                            formatPhone(event.target.value),
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="CPF/CNPJ"
                                error={profileForm.errors.document}
                            >
                                <input
                                    className="input"
                                    inputMode="numeric"
                                    maxLength={18}
                                    value={profileForm.data.document}
                                    onChange={(event) =>
                                        profileForm.setData(
                                            'document',
                                            formatDocument(event.target.value),
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <button
                            disabled={profileForm.processing}
                            className="inline-flex w-fit items-center gap-2 rounded-lg bg-navy px-5 py-2.5 text-sm font-black text-white disabled:opacity-60"
                        >
                            <CheckCircle2 className="h-4 w-4" />
                            {profileForm.processing
                                ? 'Salvando...'
                                : 'Salvar dados'}
                        </button>
                    </form>
                </section>

                <section className="rounded-2xl border border-border bg-white p-5 shadow-[var(--shadow-soft)] md:p-6">
                    <div className="flex items-start justify-between gap-3">
                        <SectionTitle
                            icon={<MapPin className="h-5 w-5" />}
                            title="Endereços"
                            description="Cadastre o endereço principal e os locais de entrega."
                        />
                        {!showAddressForm && (
                            <button
                                onClick={newAddress}
                                className="inline-flex shrink-0 items-center gap-1 rounded-lg bg-yellow px-3 py-2 text-xs font-black text-navy"
                            >
                                <Plus className="h-4 w-4" /> Adicionar
                            </button>
                        )}
                    </div>

                    {safeAddresses.length > 0 && (
                        <div className="mt-5 grid gap-3">
                            {safeAddresses.map((address) => (
                                <article
                                    key={address.id}
                                    className="rounded-xl border border-border bg-bg-soft p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <strong className="text-sm text-navy">
                                                    {address.label}
                                                </strong>
                                                <span className="rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-text-muted">
                                                    {address.type === 'shipping'
                                                        ? 'Entrega'
                                                        : 'Principal'}
                                                </span>
                                                {address.isDefault && (
                                                    <span className="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-bold text-green-800">
                                                        Padrão
                                                    </span>
                                                )}
                                            </div>
                                            <p className="mt-1 text-sm text-text-muted">
                                                {address.street},{' '}
                                                {address.number} ·{' '}
                                                {address.city}/{address.state} ·{' '}
                                                {formatPostalCode(
                                                    address.postalCode,
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex gap-1">
                                            <button
                                                onClick={() =>
                                                    editAddress(address)
                                                }
                                                aria-label={`Editar ${address.label}`}
                                                className="rounded-md p-2 text-navy hover:bg-white"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() =>
                                                    router.delete(
                                                        `/minha-conta/enderecos/${address.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                                aria-label={`Excluir ${address.label}`}
                                                className="rounded-md p-2 text-red-700 hover:bg-white"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}

                    {showAddressForm && (
                        <form
                            onSubmit={submitAddress}
                            className="mt-5 rounded-xl border border-border p-4"
                        >
                            <div className="flex items-center justify-between">
                                <h3 className="font-display font-black text-navy">
                                    {editingAddressId
                                        ? 'Editar endereço'
                                        : 'Novo endereço'}
                                </h3>
                                {safeAddresses.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowAddressForm(false)
                                        }
                                        className="rounded-md p-1 text-text-muted"
                                        aria-label="Fechar formulário"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                <Field
                                    label="Tipo"
                                    error={addressForm.errors.type}
                                >
                                    <select
                                        className="input"
                                        value={addressForm.data.type}
                                        onChange={(event) =>
                                            addressForm.setData(
                                                'type',
                                                event.target.value as
                                                    'personal' | 'shipping',
                                            )
                                        }
                                    >
                                        <option value="personal">
                                            Endereço principal
                                        </option>
                                        <option value="shipping">
                                            Endereço de entrega
                                        </option>
                                    </select>
                                </Field>
                                <Field
                                    label="Nome do endereço"
                                    error={addressForm.errors.label}
                                >
                                    <input
                                        className="input"
                                        placeholder="Ex.: Casa, Trabalho"
                                        value={addressForm.data.label}
                                        onChange={(event) =>
                                            addressForm.setData(
                                                'label',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="CEP"
                                    error={addressForm.errors.postalCode}
                                >
                                    <input
                                        className="input"
                                        inputMode="numeric"
                                        maxLength={9}
                                        value={addressForm.data.postalCode}
                                        onChange={(event) => {
                                            const value = formatPostalCode(
                                                event.target.value,
                                            );
                                            addressForm.setData(
                                                'postalCode',
                                                value,
                                            );
                                            void lookupAddress(value);
                                        }}
                                    />
                                </Field>
                                <Field
                                    label="Endereço"
                                    error={addressForm.errors.street}
                                >
                                    <input
                                        className="input"
                                        value={addressForm.data.street}
                                        onChange={(event) =>
                                            addressForm.setData(
                                                'street',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Número"
                                    error={addressForm.errors.number}
                                >
                                    <input
                                        className="input"
                                        value={addressForm.data.number}
                                        onChange={(event) =>
                                            addressForm.setData(
                                                'number',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Cidade"
                                    error={addressForm.errors.city}
                                >
                                    <input
                                        className="input"
                                        value={addressForm.data.city}
                                        onChange={(event) =>
                                            addressForm.setData(
                                                'city',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Estado"
                                    error={addressForm.errors.state}
                                >
                                    <input
                                        className="input"
                                        maxLength={2}
                                        value={addressForm.data.state}
                                        onChange={(event) =>
                                            addressForm.setData(
                                                'state',
                                                event.target.value
                                                    .replace(/[^a-z]/gi, '')
                                                    .toUpperCase(),
                                            )
                                        }
                                    />
                                </Field>
                            </div>
                            <label className="mt-4 flex cursor-pointer items-center gap-2 text-sm font-semibold text-navy">
                                <input
                                    type="checkbox"
                                    checked={addressForm.data.isDefault}
                                    onChange={(event) =>
                                        addressForm.setData(
                                            'isDefault',
                                            event.target.checked,
                                        )
                                    }
                                    className="accent-navy"
                                />
                                Usar como padrão deste tipo
                            </label>
                            <button
                                disabled={addressForm.processing}
                                className="mt-4 inline-flex items-center gap-2 rounded-lg bg-navy px-5 py-2.5 text-sm font-black text-white disabled:opacity-60"
                            >
                                <Home className="h-4 w-4" />{' '}
                                {addressForm.processing
                                    ? 'Salvando...'
                                    : 'Salvar endereço'}
                            </button>
                        </form>
                    )}
                </section>
            </div>

            <section className="mt-6 overflow-hidden rounded-xl border border-border bg-white shadow-[var(--shadow-soft)]">
                <div className="border-b border-border px-5 py-4">
                    <h2 className="font-display text-xl font-black text-navy">
                        Pedidos e orçamentos
                    </h2>
                </div>
                {safeOrders.length === 0 ? (
                    <div className="grid place-items-center px-4 py-16 text-center">
                        <PackageCheck className="h-10 w-10 text-navy" />
                        <h3 className="mt-4 font-display text-xl font-black text-navy">
                            Nenhum pedido ainda
                        </h3>
                        <p className="mt-1 text-sm text-text-muted">
                            Quando você finalizar um carrinho, ele aparece aqui.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-bg-soft text-xs text-text-muted uppercase">
                                <tr>
                                    <th className="px-5 py-3">Pedido</th>
                                    <th className="px-5 py-3">Tipo</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3">Total</th>
                                    <th className="px-5 py-3">Pagamento</th>
                                    <th className="px-5 py-3 text-right">
                                        Detalhes
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {safeOrders.map((order) => (
                                    <tr key={order.id}>
                                        <td className="px-5 py-4">
                                            <Link
                                                href={`/minha-conta/pedidos/${order.id}`}
                                                className="font-bold text-navy hover:underline"
                                            >
                                                {order.number}
                                            </Link>
                                            <div className="text-xs text-text-muted">
                                                {new Date(
                                                    order.createdAt,
                                                ).toLocaleString('pt-BR')}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4">
                                            {order.checkoutType === 'quote'
                                                ? 'Orçamento'
                                                : 'Pedido'}
                                        </td>
                                        <td className="px-5 py-4">
                                            {statusLabel(order.status)}
                                        </td>
                                        <td className="px-5 py-4 font-black text-navy">
                                            {formatMoney(
                                                order.totalAmount,
                                                order.currency,
                                            )}
                                        </td>
                                        <td className="px-5 py-4">
                                            {paymentLabel(order.paymentMethod)}
                                            <div className="text-xs text-text-muted">
                                                {paymentStatusLabel(
                                                    order.paymentStatus,
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 text-right">
                                            <Link
                                                href={`/minha-conta/pedidos/${order.id}`}
                                                className="inline-flex rounded-lg border border-border px-3 py-2 text-xs font-black text-navy hover:bg-bg-soft"
                                            >
                                                Ver pedido
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </main>
    );
}

function SectionTitle({
    icon,
    title,
    description,
}: {
    icon: ReactNode;
    title: string;
    description: string;
}) {
    return (
        <div className="flex gap-3">
            <span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-bg-soft text-navy">
                {icon}
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
        <label className="block text-sm font-bold text-navy">
            {label}
            <div
                className={`mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:outline-none focus-within:[&_.input]:border-navy ${error ? '[&_.input]:border-red-400' : '[&_.input]:border-border'}`}
            >
                {children}
            </div>
            {error && (
                <span role="alert" className="mt-1 block text-xs text-red-700">
                    {error}
                </span>
            )}
        </label>
    );
}

function statusLabel(status: string) {
    return (
        {
            quote_requested: 'Orçamento recebido',
            awaiting_payment: 'Aguardando pagamento',
            paid: 'Pago',
            processing: 'Em produção',
            shipped: 'Enviado',
            delivered: 'Entregue',
            cancelled: 'Cancelado',
            refunded: 'Reembolsado',
        }[status] ?? status
    );
}
function paymentLabel(method: string | null) {
    return (
        {
            pix: 'Pix',
            credit_card: 'Cartão',
            boleto: 'Boleto',
            combine: 'A combinar',
        }[method ?? ''] ?? 'A combinar'
    );
}
function paymentStatusLabel(status: string | null) {
    return (
        {
            pending: 'Pendente',
            paid: 'Aprovado',
            refused: 'Recusado',
            refunded: 'Estornado',
            cancelled: 'Cancelado',
        }[status ?? ''] ?? 'Pendente'
    );
}
