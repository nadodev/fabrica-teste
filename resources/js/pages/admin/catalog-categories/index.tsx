import { Head, router, useForm } from '@inertiajs/react';
import { ImagePlus, Save, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type Category = {
    id: string;
    name: string;
    slug: string | null;
    description: string;
    image_url: string | null;
    sort_order: number;
    is_active: boolean;
};

export default function CatalogCategoriesIndex({
    categories = [],
}: {
    categories?: Category[];
}) {
    const rows = Array.isArray(categories) ? categories : [];
    const form = useForm({
        name: '',
        slug: '',
        description: '',
        sort_order: 0,
        is_active: true,
        image: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/categorias-produtos', {
            forceFormData: true,
            headers: { 'Idempotency-Key': createIdempotencyKey() },
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const update = (category: Category, data: Partial<Category>) => {
        router.post(
            `/admin/categorias-produtos/${category.id}`,
            { ...category, ...data },
            {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    const uploadImage = (category: Category, file: File | null) => {
        if (!file) {
            return;
        }

        router.post(
            `/admin/categorias-produtos/${category.id}`,
            { ...category, image: file },
            {
                forceFormData: true,
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    const remove = (category: Category) => {
        if (window.confirm(`Remover categoria ${category.name}?`)) {
            router.delete(`/admin/categorias-produtos/${category.id}`, {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout title="Categorias de produto">
            <Head title="Categorias de produto" />

            <form
                onSubmit={submit}
                className="mb-6 rounded-xl border border-border bg-white p-5 shadow-[var(--shadow-soft)]"
            >
                <div className="mb-4">
                    <h2 className="font-display text-xl font-black text-navy">
                        Nova categoria
                    </h2>
                    <p className="text-sm text-text-muted">
                        As categorias aparecem nos filtros e no menu
                        Departamentos.
                    </p>
                </div>
                <div className="grid gap-4 lg:grid-cols-[1fr_1fr_100px_auto]">
                    <Field label="Nome">
                        <input
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            className="input"
                            placeholder="Uniformes empresariais"
                        />
                    </Field>
                    <Field label="Slug">
                        <input
                            value={form.data.slug}
                            onChange={(event) =>
                                form.setData('slug', event.target.value)
                            }
                            className="input"
                            placeholder="uniformes-empresariais"
                        />
                    </Field>
                    <Field label="Ordem">
                        <input
                            type="number"
                            value={form.data.sort_order}
                            onChange={(event) =>
                                form.setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                            className="input"
                        />
                    </Field>
                    <label className="flex items-end">
                        <span className="inline-flex h-[42px] cursor-pointer items-center gap-2 rounded-lg border border-border px-4 text-sm font-black text-navy hover:bg-bg-soft">
                            <ImagePlus className="h-4 w-4" /> Imagem
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(event) =>
                                    form.setData(
                                        'image',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                                className="sr-only"
                            />
                        </span>
                    </label>
                </div>
                <Field label="Descricao">
                    <textarea
                        rows={2}
                        value={form.data.description}
                        onChange={(event) =>
                            form.setData('description', event.target.value)
                        }
                        className="input"
                    />
                </Field>
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm font-bold text-navy">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) =>
                                form.setData('is_active', event.target.checked)
                            }
                        />{' '}
                        Categoria ativa
                    </label>
                    <button
                        disabled={form.processing}
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-yellow px-5 py-3 font-black text-navy disabled:opacity-60"
                    >
                        <Save className="h-4 w-4" /> Salvar categoria
                    </button>
                </div>
            </form>

            {rows.length === 0 ? (
                <div className="rounded-xl border border-border bg-white p-12 text-center text-text-muted">
                    Nenhuma categoria cadastrada.
                </div>
            ) : (
                <div className="grid gap-4 xl:grid-cols-2">
                    {rows.map((category) => (
                        <article
                            key={category.id}
                            className="rounded-xl border border-border bg-white p-4 shadow-[var(--shadow-soft)]"
                        >
                            <div className="grid gap-4 sm:grid-cols-[112px_1fr]">
                                <div className="overflow-hidden rounded-xl bg-bg-soft">
                                    {category.image_url ? (
                                        <img
                                            src={category.image_url}
                                            alt=""
                                            className="aspect-square w-full object-cover"
                                        />
                                    ) : (
                                        <div className="grid aspect-square place-items-center text-text-muted">
                                            <ImagePlus className="h-6 w-6" />
                                        </div>
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <Field label="Nome">
                                            <input
                                                defaultValue={category.name}
                                                onBlur={(event) =>
                                                    update(category, {
                                                        name: event.target
                                                            .value,
                                                    })
                                                }
                                                className="input"
                                            />
                                        </Field>
                                        <Field label="Slug">
                                            <input
                                                defaultValue={
                                                    category.slug ?? ''
                                                }
                                                onBlur={(event) =>
                                                    update(category, {
                                                        slug: event.target
                                                            .value,
                                                    })
                                                }
                                                className="input"
                                            />
                                        </Field>
                                    </div>
                                    <Field label="Descricao">
                                        <input
                                            defaultValue={category.description}
                                            onBlur={(event) =>
                                                update(category, {
                                                    description:
                                                        event.target.value,
                                                })
                                            }
                                            className="input"
                                        />
                                    </Field>
                                </div>
                            </div>
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
                                <div className="flex flex-wrap items-center gap-3">
                                    <label className="text-sm font-bold text-navy">
                                        Ordem{' '}
                                        <input
                                            type="number"
                                            defaultValue={category.sort_order}
                                            onBlur={(event) =>
                                                update(category, {
                                                    sort_order: Number(
                                                        event.target.value,
                                                    ),
                                                })
                                            }
                                            className="ml-2 w-20 rounded-lg border border-border px-2 py-1"
                                        />
                                    </label>
                                    <label className="flex items-center gap-2 text-sm font-bold text-navy">
                                        <input
                                            type="checkbox"
                                            defaultChecked={category.is_active}
                                            onChange={(event) =>
                                                update(category, {
                                                    is_active:
                                                        event.target.checked,
                                                })
                                            }
                                        />{' '}
                                        Ativa
                                    </label>
                                    <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-border px-3 py-2 text-xs font-black text-navy hover:bg-bg-soft">
                                        <ImagePlus className="h-4 w-4" /> Trocar
                                        imagem
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={(event) =>
                                                uploadImage(
                                                    category,
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                            className="sr-only"
                                        />
                                    </label>
                                </div>
                                <button
                                    onClick={() => remove(category)}
                                    className="rounded-md p-2 text-red-700 hover:bg-red-50"
                                    aria-label="Remover"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}

function Field({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <label className="block text-sm font-bold text-navy">
            {label}
            <div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:outline-none focus-within:[&_.input]:border-navy">
                {children}
            </div>
        </label>
    );
}
