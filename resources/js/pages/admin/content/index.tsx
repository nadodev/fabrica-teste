import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type Item = {
    id: string;
    name?: string;
    title?: string;
    city?: string;
    type?: string;
    message?: string;
    image_url?: string | null;
    is_active: boolean;
    sort_order?: number;
};

export default function ContentIndex({
    type,
    title,
    items = [],
}: {
    type: string;
    title: string;
    items?: Item[];
}) {
    const safeItems = Array.isArray(items) ? items : [];
    const remove = (item: Item) => {
        if (window.confirm(`Remover ${label(item)}?`)) {
            router.delete(`/admin/conteudo/${type}/${item.id}`, {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout title={title}>
            <Head title={title} />
            <div className="mb-5 flex items-center justify-between gap-3">
                <p className="text-sm text-text-muted">
                    {safeItems.length} registro(s)
                </p>
                <Link
                    href={`/admin/conteudo/${type}/novo`}
                    className="inline-flex items-center gap-2 rounded-lg bg-yellow px-4 py-2.5 text-sm font-black text-navy"
                >
                    <Plus className="h-4 w-4" /> Novo
                </Link>
            </div>
            <div className="overflow-hidden rounded-xl border border-border bg-white">
                {safeItems.length === 0 ? (
                    <div className="p-12 text-center text-text-muted">
                        Nenhum registro cadastrado.
                    </div>
                ) : (
                    <div className="divide-y divide-border">
                        {safeItems.map((item) => (
                            <div
                                key={item.id}
                                className="flex items-center gap-4 p-4"
                            >
                                {item.image_url && (
                                    <img
                                        src={item.image_url}
                                        alt=""
                                        className="h-14 w-20 rounded-lg object-cover"
                                    />
                                )}
                                <div className="min-w-0 flex-1">
                                    <div className="font-display text-lg font-black text-navy">
                                        {label(item)}
                                    </div>
                                    <div className="text-xs text-text-muted">
                                        Ordem {item.sort_order ?? 0} -{' '}
                                        {item.is_active ? 'Ativo' : 'Inativo'}
                                    </div>
                                </div>
                                <Link
                                    href={`/admin/conteudo/${type}/${item.id}/editar`}
                                    className="rounded-md p-2 text-navy hover:bg-bg-soft"
                                    aria-label="Editar"
                                >
                                    <Pencil className="h-4 w-4" />
                                </Link>
                                <button
                                    onClick={() => remove(item)}
                                    className="rounded-md p-2 text-red-700 hover:bg-red-50"
                                    aria-label="Remover"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

function label(item: Item) {
    return (
        item.name ??
        item.title ??
        item.city ??
        item.type ??
        item.message ??
        'Registro'
    );
}
