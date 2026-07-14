import { useForm } from '@inertiajs/react';
import { ImagePlus, Plus, Save, Trash2 } from 'lucide-react';
import type { ChangeEvent, FormEvent, ReactNode } from 'react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import type { CatalogProduct } from '@/modules/catalog/domain/product';

type VariationForm = {
    id: string;
    name: string;
    value: string;
    sku: string;
    stock: string;
    lowStockThreshold: string;
};

type ProductFormData = {
    sku: string;
    name: string;
    description: string;
    category: string;
    price: string;
    stock: string;
    weightGrams: string;
    widthCentimeters: string;
    heightCentimeters: string;
    lengthCentimeters: string;
    status: string;
    imageUrl: string;
    existingGalleryImages: string[];
    galleryImages: File[];
    variations: VariationForm[];
    image: File | null;
    removeImage: boolean;
};

const emptyVariation = (): VariationForm => ({
    id: '',
    name: '',
    value: '',
    sku: '',
    stock: '0',
    lowStockThreshold: '5',
});

export function AdminProductForm({
    product,
    categories = [],
}: {
    product?: CatalogProduct;
    categories?: string[];
}) {
    const categoryOptions = Array.isArray(categories) ? categories : [];
    const form = useForm<ProductFormData>({
        sku: product?.sku ?? '',
        name: product?.name ?? '',
        description: product?.description ?? '',
        category: product?.category ?? 'Uniformes',
        price: product
            ? (product.priceAmount / 100).toFixed(2).replace('.', ',')
            : '',
        stock: String(product?.stockAvailable ?? 0),
        weightGrams: String(product?.weightGrams ?? 300),
        widthCentimeters: String(product?.widthCentimeters ?? 20),
        heightCentimeters: String(product?.heightCentimeters ?? 5),
        lengthCentimeters: String(product?.lengthCentimeters ?? 30),
        status: product?.status === 'active' ? 'active' : 'draft',
        imageUrl: product?.imageUrl?.startsWith('http') ? product.imageUrl : '',
        existingGalleryImages:
            product?.galleryImages?.filter((url) => url !== product.imageUrl) ??
            [],
        galleryImages: [],
        variations:
            product?.variations?.map((variation) => ({
                id: variation.id,
                name: variation.name,
                value: variation.value,
                sku: variation.sku,
                stock: String(variation.stock),
                lowStockThreshold: String(variation.lowStockThreshold),
            })) ?? [],
        image: null,
        removeImage: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) =>
            product ? { ...data, _method: 'put' } : data,
        );
        form.post(
            product ? `/admin/produtos/${product.id}` : '/admin/produtos',
            {
                forceFormData: true,
                headers: { 'Idempotency-Key': createIdempotencyKey() },
            },
        );
    };

    const setGalleryFiles = (event: ChangeEvent<HTMLInputElement>) => {
        form.setData('galleryImages', Array.from(event.target.files ?? []));
    };

    const updateVariation = (
        index: number,
        field: keyof VariationForm,
        value: string,
    ) => {
        form.setData(
            'variations',
            form.data.variations.map((variation, current) =>
                current === index
                    ? { ...variation, [field]: value }
                    : variation,
            ),
        );
    };

    const removeVariation = (index: number) => {
        form.setData(
            'variations',
            form.data.variations.filter((_, current) => current !== index),
        );
    };

    return (
        <form
            onSubmit={submit}
            className="max-w-4xl space-y-6 rounded-xl border border-border bg-white p-6"
        >
            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Nome do produto" error={form.errors.name}>
                    <input
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        className="input"
                        placeholder="Camisa Polo Empresarial"
                    />
                </Field>
                <Field label="SKU" error={form.errors.sku}>
                    <input
                        disabled={Boolean(product)}
                        value={form.data.sku}
                        onChange={(event) =>
                            form.setData(
                                'sku',
                                event.target.value.toUpperCase(),
                            )
                        }
                        className="input disabled:bg-bg-soft"
                        placeholder="POLO-001"
                    />
                </Field>
            </div>

            <section className="rounded-xl border border-border bg-bg-soft p-4">
                <div className="mb-4">
                    <h3 className="font-display text-lg font-black text-navy">
                        Peso e dimensoes para o frete
                    </h3>
                    <p className="text-xs text-text-muted">
                        Informe as medidas do produto embalado. Estes dados sao
                        enviados ao Melhor Envio no calculo e na confirmacao do
                        pedido.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Field
                        label="Peso (gramas)"
                        error={form.errors.weightGrams}
                    >
                        <input
                            type="number"
                            min={1}
                            max={30000}
                            value={form.data.weightGrams}
                            onChange={(event) =>
                                form.setData('weightGrams', event.target.value)
                            }
                            className="input"
                        />
                    </Field>
                    <Field
                        label="Largura (cm)"
                        error={form.errors.widthCentimeters}
                    >
                        <input
                            type="number"
                            min={1}
                            max={200}
                            value={form.data.widthCentimeters}
                            onChange={(event) =>
                                form.setData(
                                    'widthCentimeters',
                                    event.target.value,
                                )
                            }
                            className="input"
                        />
                    </Field>
                    <Field
                        label="Altura (cm)"
                        error={form.errors.heightCentimeters}
                    >
                        <input
                            type="number"
                            min={1}
                            max={200}
                            value={form.data.heightCentimeters}
                            onChange={(event) =>
                                form.setData(
                                    'heightCentimeters',
                                    event.target.value,
                                )
                            }
                            className="input"
                        />
                    </Field>
                    <Field
                        label="Comprimento (cm)"
                        error={form.errors.lengthCentimeters}
                    >
                        <input
                            type="number"
                            min={1}
                            max={200}
                            value={form.data.lengthCentimeters}
                            onChange={(event) =>
                                form.setData(
                                    'lengthCentimeters',
                                    event.target.value,
                                )
                            }
                            className="input"
                        />
                    </Field>
                </div>
            </section>

            <Field label="Descricao" error={form.errors.description}>
                <textarea
                    rows={5}
                    value={form.data.description}
                    onChange={(event) =>
                        form.setData('description', event.target.value)
                    }
                    className="input"
                    placeholder="Caracteristicas, material e acabamento..."
                />
            </Field>

            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Categoria" error={form.errors.category}>
                    <input
                        list="catalog-categories"
                        value={form.data.category}
                        onChange={(event) =>
                            form.setData('category', event.target.value)
                        }
                        className="input"
                        placeholder="Uniformes profissionais"
                    />
                    <datalist id="catalog-categories">
                        {categoryOptions.map((category) => (
                            <option key={category} value={category} />
                        ))}
                    </datalist>
                </Field>
                <Field label="Estoque geral" error={form.errors.stock}>
                    <input
                        type="number"
                        min={0}
                        value={form.data.stock}
                        onChange={(event) =>
                            form.setData('stock', event.target.value)
                        }
                        className="input"
                        placeholder="Usado se nao houver variacoes"
                    />
                </Field>
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <Field label="Preco em reais" error={form.errors.price}>
                    <input
                        inputMode="decimal"
                        value={form.data.price}
                        onChange={(event) =>
                            form.setData('price', event.target.value)
                        }
                        className="input"
                        placeholder="79,90"
                    />
                </Field>
                <Field label="Status" error={form.errors.status}>
                    <select
                        value={form.data.status}
                        onChange={(event) =>
                            form.setData('status', event.target.value)
                        }
                        className="input"
                    >
                        <option value="draft">Rascunho</option>
                        <option value="active">Ativo no catalogo</option>
                    </select>
                </Field>
            </div>

            <Field label="Imagem principal" error={form.errors.image}>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    onChange={(event) =>
                        form.setData('image', event.target.files?.[0] ?? null)
                    }
                    className="input"
                />
            </Field>

            <Field
                label="Ou URL externa da imagem principal"
                error={form.errors.imageUrl}
            >
                <input
                    type="url"
                    value={form.data.imageUrl}
                    onChange={(event) =>
                        form.setData('imageUrl', event.target.value)
                    }
                    className="input"
                    placeholder="https://..."
                />
            </Field>

            {product?.imageUrl && (
                <label className="flex items-center gap-2 text-sm text-text-muted">
                    <input
                        type="checkbox"
                        checked={form.data.removeImage}
                        onChange={(event) =>
                            form.setData('removeImage', event.target.checked)
                        }
                    />{' '}
                    Remover imagem principal atual
                </label>
            )}

            <section className="rounded-xl border border-border bg-bg-soft p-4">
                <div className="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 className="font-display text-lg font-black text-navy">
                            Galeria de fotos
                        </h3>
                        <p className="text-xs text-text-muted">
                            Envie varias fotos do produto. As imagens atuais
                            podem ser removidas antes de salvar.
                        </p>
                    </div>
                    <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-navy px-4 py-2 text-sm font-black text-white">
                        <ImagePlus className="h-4 w-4" /> Upload
                        <input
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            onChange={setGalleryFiles}
                            className="sr-only"
                        />
                    </label>
                </div>

                {form.data.existingGalleryImages.length > 0 && (
                    <div className="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
                        {form.data.existingGalleryImages.map((url) => (
                            <div
                                key={url}
                                className="relative overflow-hidden rounded-lg border border-border bg-white"
                            >
                                <img
                                    src={url}
                                    alt=""
                                    className="aspect-square w-full object-cover"
                                />
                                <button
                                    type="button"
                                    onClick={() =>
                                        form.setData(
                                            'existingGalleryImages',
                                            form.data.existingGalleryImages.filter(
                                                (item) => item !== url,
                                            ),
                                        )
                                    }
                                    className="absolute top-1 right-1 rounded-md bg-white/90 p-1 text-red-700"
                                >
                                    <Trash2 className="h-3.5 w-3.5" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                {form.data.galleryImages.length > 0 && (
                    <div className="text-sm font-semibold text-navy">
                        {form.data.galleryImages.length} nova(s) foto(s)
                        selecionada(s)
                    </div>
                )}
            </section>

            <section className="rounded-xl border border-border bg-white p-4">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 className="font-display text-lg font-black text-navy">
                            Variacoes e estoque
                        </h3>
                        <p className="text-xs text-text-muted">
                            Cada variacao possui SKU e saldo proprios. O alerta
                            sinaliza estoque baixo sem bloquear unidades ainda
                            disponiveis.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            form.setData('variations', [
                                ...form.data.variations,
                                emptyVariation(),
                            ])
                        }
                        className="inline-flex items-center gap-2 rounded-lg bg-yellow px-4 py-2 text-sm font-black text-navy"
                    >
                        <Plus className="h-4 w-4" /> Adicionar variacao
                    </button>
                </div>

                <div className="space-y-3">
                    {form.data.variations.map((variation, index) => {
                        const stock = Number(variation.stock || 0);
                        const low = Number(variation.lowStockThreshold || 0);
                        const unavailable = stock <= 0;
                        const lowStock = !unavailable && stock <= low;

                        return (
                            <div
                                key={`${variation.id}-${index}`}
                                className="grid gap-3 rounded-lg border border-border bg-bg-soft p-3 md:grid-cols-[1fr_1fr_1fr_100px_100px_auto]"
                            >
                                <input
                                    type="hidden"
                                    value={variation.id}
                                    name={`variations.${index}.id`}
                                />
                                <Field label="Nome">
                                    <input
                                        value={variation.name}
                                        onChange={(event) =>
                                            updateVariation(
                                                index,
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                        className="input"
                                        placeholder="Tamanho"
                                    />
                                </Field>
                                <Field label="Valor">
                                    <input
                                        value={variation.value}
                                        onChange={(event) =>
                                            updateVariation(
                                                index,
                                                'value',
                                                event.target.value,
                                            )
                                        }
                                        className="input"
                                        placeholder="M"
                                    />
                                </Field>
                                <Field label="SKU da variacao">
                                    <input
                                        value={variation.sku}
                                        onChange={(event) =>
                                            updateVariation(
                                                index,
                                                'sku',
                                                event.target.value.toUpperCase(),
                                            )
                                        }
                                        className="input"
                                        placeholder="POLO-001-M"
                                    />
                                </Field>
                                <Field label="Estoque">
                                    <input
                                        type="number"
                                        min={0}
                                        value={variation.stock}
                                        onChange={(event) =>
                                            updateVariation(
                                                index,
                                                'stock',
                                                event.target.value,
                                            )
                                        }
                                        className="input"
                                    />
                                </Field>
                                <Field label="Alerta">
                                    <input
                                        type="number"
                                        min={0}
                                        value={variation.lowStockThreshold}
                                        onChange={(event) =>
                                            updateVariation(
                                                index,
                                                'lowStockThreshold',
                                                event.target.value,
                                            )
                                        }
                                        className="input"
                                    />
                                </Field>
                                <div className="flex items-end gap-2">
                                    <span
                                        className={`rounded-full px-2 py-1 text-[11px] font-black ${unavailable ? 'bg-red-100 text-red-800' : lowStock ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'}`}
                                    >
                                        {unavailable
                                            ? 'Sem estoque'
                                            : lowStock
                                              ? 'Estoque baixo'
                                              : 'Compra ok'}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => removeVariation(index)}
                                        className="rounded-md p-2 text-red-700 hover:bg-red-50"
                                        aria-label="Remover variacao"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </section>

            <div className="flex justify-end border-t border-border pt-5">
                <button
                    disabled={form.processing}
                    className="inline-flex items-center gap-2 rounded-lg bg-yellow px-6 py-3 font-black text-navy disabled:opacity-60"
                >
                    <Save className="h-4 w-4" />{' '}
                    {form.processing ? 'Salvando...' : 'Salvar produto'}
                </button>
            </div>
        </form>
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
            <div className="mt-1 [&_.input]:w-full [&_.input]:rounded-lg [&_.input]:border [&_.input]:border-border [&_.input]:bg-white [&_.input]:px-3 [&_.input]:py-2.5 [&_.input]:font-normal [&_.input]:text-text-dark [&_.input]:outline-none focus-within:[&_.input]:border-navy">
                {children}
            </div>
            {error && (
                <span className="mt-1 block text-xs text-red-700">{error}</span>
            )}
        </label>
    );
}
