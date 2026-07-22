import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Crown, ShieldCheck, UserPlus, UserRoundCog } from 'lucide-react';
import type { FormEvent } from 'react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type Permission = { value: string; label: string; group: string };
type Administrator = {
    id: number;
    name: string;
    email: string;
    emailVerified: boolean;
    superAdministrator: boolean;
    permissions: string[];
};

type Props = {
    administrators: Administrator[];
    permissions: Permission[];
};

export default function Administrators({ administrators, permissions }: Props) {
    const page = usePage<{ auth: { user: { id: number } } }>();
    const promotion = useForm({
        email: '',
        permissions: ['admin.dashboard.view'],
    });

    const submitPromotion = (event: FormEvent) => {
        event.preventDefault();
        promotion.post('/admin/usuarios', {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
            onSuccess: () => promotion.reset(),
        });
    };

    return (
        <AdminLayout title="Usuários e permissões">
            <Head title="Usuários e permissões" />
            <div className="space-y-6">
                <section className="rounded-xl border border-border bg-white p-5">
                    <div className="flex items-center gap-3">
                        <div className="grid h-10 w-10 place-items-center rounded-lg bg-yellow text-navy">
                            <UserPlus className="h-5 w-5" />
                        </div>
                        <div>
                            <h2 className="font-display text-lg font-black text-navy">
                                Conceder acesso administrativo
                            </h2>
                            <p className="text-sm text-text-muted">
                                O cliente deve estar cadastrado e com o e-mail
                                confirmado.
                            </p>
                        </div>
                    </div>
                    <form onSubmit={submitPromotion} className="mt-5 space-y-5">
                        <label className="block max-w-xl text-sm font-semibold text-navy">
                            E-mail do cliente
                            <input
                                type="email"
                                required
                                value={promotion.data.email}
                                onChange={(event) =>
                                    promotion.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                                className="mt-1 w-full rounded-lg border border-border px-3 py-2.5 outline-none focus:border-navy"
                            />
                            {promotion.errors.email && (
                                <span className="mt-1 block text-xs text-red-700">
                                    {promotion.errors.email}
                                </span>
                            )}
                        </label>
                        <PermissionGrid
                            permissions={permissions}
                            selected={promotion.data.permissions}
                            onChange={(selected) =>
                                promotion.setData('permissions', selected)
                            }
                        />
                        {promotion.errors.permissions && (
                            <p className="text-sm text-red-700">
                                {promotion.errors.permissions}
                            </p>
                        )}
                        <button
                            disabled={promotion.processing}
                            className="rounded-lg bg-navy px-5 py-3 font-bold text-white disabled:opacity-60"
                        >
                            {promotion.processing
                                ? 'Concedendo...'
                                : 'Conceder acesso'}
                        </button>
                    </form>
                </section>

                <section className="space-y-4">
                    {administrators.map((administrator) => (
                        <AdministratorCard
                            key={administrator.id}
                            administrator={administrator}
                            permissions={permissions}
                            currentUserId={page.props.auth.user.id}
                        />
                    ))}
                </section>
            </div>
        </AdminLayout>
    );
}

function AdministratorCard({
    administrator,
    permissions,
    currentUserId,
}: {
    administrator: Administrator;
    permissions: Permission[];
    currentUserId: number;
}) {
    const form = useForm({ permissions: administrator.permissions });
    const protectedAccount =
        administrator.superAdministrator || administrator.id === currentUserId;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/usuarios/${administrator.id}`, {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    const revoke = () => {
        if (
            !window.confirm(
                `Remover o acesso administrativo de ${administrator.name}? As sessões serão encerradas.`,
            )
        ) {
            return;
        }

        router.delete(`/admin/usuarios/${administrator.id}`, {
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
        });
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-border bg-white p-5"
        >
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-center gap-3">
                    <div className="grid h-10 w-10 place-items-center rounded-lg bg-bg-soft text-navy">
                        {administrator.superAdministrator ? (
                            <Crown className="h-5 w-5" />
                        ) : (
                            <UserRoundCog className="h-5 w-5" />
                        )}
                    </div>
                    <div>
                        <h2 className="font-bold text-navy">
                            {administrator.name}
                        </h2>
                        <p className="text-sm text-text-muted">
                            {administrator.email}
                        </p>
                    </div>
                </div>
                {administrator.superAdministrator && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-yellow-soft px-3 py-1 text-xs font-black text-navy">
                        <ShieldCheck className="h-3.5 w-3.5" /> Proprietário
                    </span>
                )}
            </div>

            {administrator.superAdministrator ? (
                <p className="mt-4 text-sm text-text-muted">
                    O proprietário possui todas as permissões e não pode ser
                    alterado por esta tela.
                </p>
            ) : (
                <div className="mt-5">
                    <PermissionGrid
                        permissions={permissions}
                        selected={form.data.permissions}
                        disabled={protectedAccount}
                        onChange={(selected) =>
                            form.setData('permissions', selected)
                        }
                    />
                    {form.errors.permissions && (
                        <p className="mt-2 text-sm text-red-700">
                            {form.errors.permissions}
                        </p>
                    )}
                    {!protectedAccount && (
                        <div className="mt-5 flex flex-wrap gap-3">
                            <button
                                disabled={form.processing}
                                className="rounded-lg bg-navy px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60"
                            >
                                Salvar permissões
                            </button>
                            <button
                                type="button"
                                onClick={revoke}
                                className="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-50"
                            >
                                Remover acesso
                            </button>
                        </div>
                    )}
                    {administrator.id === currentUserId && (
                        <p className="mt-4 text-sm text-text-muted">
                            Suas próprias permissões não podem ser alteradas
                            nesta sessão.
                        </p>
                    )}
                </div>
            )}
        </form>
    );
}

function PermissionGrid({
    permissions,
    selected,
    disabled = false,
    onChange,
}: {
    permissions: Permission[];
    selected: string[];
    disabled?: boolean;
    onChange: (selected: string[]) => void;
}) {
    const groups = permissions.reduce<Record<string, Permission[]>>(
        (result, permission) => {
            (result[permission.group] ??= []).push(permission);

            return result;
        },
        {},
    );

    const toggle = (permission: string, checked: boolean) => {
        if (permission === 'admin.dashboard.view') {
            return;
        }

        onChange(
            checked
                ? [...new Set([...selected, permission])]
                : selected.filter((value) => value !== permission),
        );
    };

    return (
        <div className="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            {Object.entries(groups).map(([group, entries]) => (
                <fieldset
                    key={group}
                    disabled={disabled}
                    className="rounded-lg border border-border p-4"
                >
                    <legend className="px-1 text-sm font-black text-navy">
                        {group}
                    </legend>
                    <div className="space-y-2">
                        {entries?.map((permission) => {
                            const required =
                                permission.value === 'admin.dashboard.view';

                            return (
                                <label
                                    key={permission.value}
                                    className="flex items-start gap-2 text-sm text-text-dark"
                                >
                                    <input
                                        type="checkbox"
                                        checked={
                                            required ||
                                            selected.includes(permission.value)
                                        }
                                        disabled={disabled || required}
                                        onChange={(event) =>
                                            toggle(
                                                permission.value,
                                                event.target.checked,
                                            )
                                        }
                                        className="mt-0.5"
                                    />
                                    <span>{permission.label}</span>
                                </label>
                            );
                        })}
                    </div>
                </fieldset>
            ))}
        </div>
    );
}
