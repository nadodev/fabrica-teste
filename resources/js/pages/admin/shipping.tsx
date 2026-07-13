import { Head, useForm } from '@inertiajs/react';
import { Save, Truck } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { formatPostalCode } from '@/lib/input-masks';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type ShippingSettings = {
    isEnabled: boolean;
    environment: 'sandbox' | 'production';
    originZip: string;
    hasConfiguredToken: boolean;
    options?: Record<string, string | boolean | number | null>;
};

export default function AdminShipping({
    shipping,
}: {
    shipping: ShippingSettings;
}) {
    const form = useForm({
        isEnabled: shipping.isEnabled,
        environment: shipping.environment,
        originZip: formatPostalCode(shipping.originZip ?? ''),
        options: {
            postingAddress: String(shipping.options?.postingAddress ?? ''),
            productionExtraDays: String(
                shipping.options?.productionExtraDays ?? '0',
            ),
            freeShippingEnabled: Boolean(
                shipping.options?.freeShippingEnabled ?? false,
            ),
            freeShippingMinimum: String(
                shipping.options?.freeShippingMinimum ?? '',
            ),
            fixedRateEnabled: Boolean(
                shipping.options?.fixedRateEnabled ?? false,
            ),
            fixedRateAmount: String(shipping.options?.fixedRateAmount ?? ''),
            servedRegions: String(shipping.options?.servedRegions ?? ''),
            defaultWeight: String(shipping.options?.defaultWeight ?? '0.3'),
            defaultWidth: String(shipping.options?.defaultWidth ?? '20'),
            defaultHeight: String(shipping.options?.defaultHeight ?? '5'),
            defaultLength: String(shipping.options?.defaultLength ?? '30'),
            estimatedDays: String(shipping.options?.estimatedDays ?? ''),
        },
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/frete', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Frete">
            <Head title="Configuracao de frete" />
            <form
                onSubmit={submit}
                className="grid gap-6 lg:grid-cols-[1fr_360px]"
            >
                <section className="rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
                    <div className="flex items-center gap-3">
                        <span className="grid h-11 w-11 place-items-center rounded-lg bg-yellow text-navy">
                            <Truck className="h-5 w-5" />
                        </span>
                        <div>
                            <h2 className="font-display text-xl font-black text-navy">
                                Melhor Envio
                            </h2>
                            <p className="text-sm text-text-muted">
                                Use o token da sua conta para calcular frete por
                                CEP no carrinho.
                            </p>
                        </div>
                    </div>

                    <label className="mt-6 flex items-center gap-3 rounded-lg bg-bg-soft p-4 text-sm font-bold text-navy">
                        <input
                            type="checkbox"
                            checked={form.data.isEnabled}
                            onChange={(event) =>
                                form.setData('isEnabled', event.target.checked)
                            }
                        />
                        Ativar calculo de frete na loja
                    </label>

                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                        <Field label="Ambiente" error={form.errors.environment}>
                            <select
                                className="input"
                                value={form.data.environment}
                                onChange={(event) =>
                                    form.setData(
                                        'environment',
                                        event.target.value as
                                            'sandbox' | 'production',
                                    )
                                }
                            >
                                <option value="sandbox">
                                    Sandbox / testes
                                </option>
                                <option value="production">Producao</option>
                            </select>
                        </Field>
                        <Field
                            label="CEP de origem"
                            error={form.errors.originZip}
                        >
                            <input
                                className="input"
                                inputMode="numeric"
                                autoComplete="postal-code"
                                maxLength={9}
                                value={form.data.originZip}
                                onChange={(event) =>
                                    form.setData(
                                        'originZip',
                                        formatPostalCode(event.target.value),
                                    )
                                }
                                placeholder="54325440"
                            />
                        </Field>
                    </div>

                    <div
                        className={`mt-5 rounded-lg px-3 py-3 text-sm font-semibold ${
                            shipping.hasConfiguredToken
                                ? 'bg-green-50 text-green-800'
                                : 'bg-red-50 text-red-800'
                        }`}
                    >
                        {form.errors.isEnabled ??
                            (shipping.hasConfiguredToken
                                ? 'Token configurado com seguranca no ambiente do servidor.'
                                : 'Configure MELHOR_ENVIO_TOKEN no ambiente do servidor.')}
                    </div>

                    <section className="mt-6 rounded-xl border border-border bg-bg-soft p-4">
                        <h3 className="font-display text-lg font-black text-navy">
                            Entrega e regras comerciais
                        </h3>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <Field label="Endereco de postagem">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .postingAddress as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            postingAddress: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Prazo adicional de producao">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .productionExtraDays as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            productionExtraDays: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Valor minimo para frete gratis (R$)">
                                <input
                                    className="input"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={
                                        form.data.options
                                            .freeShippingMinimum as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            freeShippingMinimum: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Taxa fixa">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .fixedRateAmount as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            fixedRateAmount: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Regioes atendidas">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .servedRegions as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            servedRegions: e.target.value,
                                        })
                                    }
                                    placeholder="PE, SP, Brasil"
                                />
                            </Field>
                            <Field label="Prazo estimado">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .estimatedDays as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            estimatedDays: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                        </div>
                        <div className="mt-4 grid gap-3 sm:grid-cols-2">
                            <Toggle
                                label="Frete gratis"
                                checked={Boolean(
                                    form.data.options.freeShippingEnabled,
                                )}
                                onChange={(value) =>
                                    form.setData('options', {
                                        ...form.data.options,
                                        freeShippingEnabled: value,
                                    })
                                }
                            />
                            <Toggle
                                label="Usar taxa fixa"
                                checked={Boolean(
                                    form.data.options.fixedRateEnabled,
                                )}
                                onChange={(value) =>
                                    form.setData('options', {
                                        ...form.data.options,
                                        fixedRateEnabled: value,
                                    })
                                }
                            />
                        </div>
                    </section>

                    <section className="mt-6 rounded-xl border border-border bg-bg-soft p-4">
                        <h3 className="font-display text-lg font-black text-navy">
                            Dimensoes e peso padrao
                        </h3>
                        <div className="mt-4 grid gap-4 sm:grid-cols-4">
                            <Field label="Peso kg">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .defaultWeight as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            defaultWeight: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Largura cm">
                                <input
                                    className="input"
                                    value={
                                        form.data.options.defaultWidth as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            defaultWidth: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Altura cm">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .defaultHeight as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            defaultHeight: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field label="Comprimento cm">
                                <input
                                    className="input"
                                    value={
                                        form.data.options
                                            .defaultLength as string
                                    }
                                    onChange={(e) =>
                                        form.setData('options', {
                                            ...form.data.options,
                                            defaultLength: e.target.value,
                                        })
                                    }
                                />
                            </Field>
                        </div>
                    </section>

                    <button
                        disabled={form.processing}
                        className="mt-6 inline-flex items-center gap-2 rounded-md bg-navy px-5 py-3 font-black text-white disabled:opacity-60"
                    >
                        <Save className="h-4 w-4" /> Salvar configuracao
                    </button>
                </section>

                <aside className="h-fit rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]">
                    <h3 className="font-display text-lg font-black text-navy">
                        Checklist
                    </h3>
                    <div className="mt-4 space-y-3 text-sm text-text-muted">
                        <p>
                            <strong className="text-navy">1.</strong> Gere o
                            token de acesso no Melhor Envio.
                        </p>
                        <p>
                            <strong className="text-navy">2.</strong> Cole o
                            token em MELHOR_ENVIO_TOKEN no servidor.
                        </p>
                        <p>
                            <strong className="text-navy">3.</strong> Ative em
                            exatamente o ambiente em que o token foi gerado.
                        </p>
                    </div>
                    <div className="mt-5 rounded-lg bg-bg-soft p-3 text-xs text-text-muted">
                        Como os produtos ainda nao possuem peso e dimensoes
                        proprias, a cotacao usa uma embalagem padrao por item.
                        Quando cadastrar peso/dimensoes por produto, o frete
                        fica mais preciso.
                    </div>
                </aside>
            </form>
        </AdminLayout>
    );
}

function Toggle({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (value: boolean) => void;
}) {
    return (
        <label className="flex items-center gap-2 rounded-lg bg-white p-3 text-sm font-bold text-navy">
            <input
                type="checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
            />{' '}
            {label}
        </label>
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
