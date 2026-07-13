import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Archive,
    Download,
    Pencil,
    Plus,
    Upload,
} from 'lucide-react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import type { CatalogProduct } from '@/modules/catalog/domain/product';
import { formatMoney } from '@/modules/catalog/domain/product';

export default function AdminProducts({
    products = [],
}: {
    products?: CatalogProduct[];
}) {
    const safeProducts = Array.isArray(products) ? products : [];
    const commerceSettings = usePage<{
        commerceSettings?: {
            system?: { productImportExport?: boolean };
            products?: { allowOutOfStock?: boolean };
        };
    }>().props.commerceSettings;
    const importExportEnabled =
        commerceSettings?.system?.productImportExport !== false;
    const showStockAlerts =
        commerceSettings?.products?.allowOutOfStock !== true;
    const archiveProduct = (product: CatalogProduct) => {
        if (
            window.confirm(
                `Arquivar ${product.name}? O produto deixara de aparecer na loja.`,
            )
        ) {
            router.delete(`/admin/produtos/${product.id}`, {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            });
        }
    };

    const importProducts = (file: File | null) => {
        if (!file) {
            return;
        }

        router.post(
            '/admin/produtos/importar',
            { file },
            {
                forceFormData: true,
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    return (
        <AdminLayout title="Produtos">
            <Head title="Produtos do painel" />
            <div className="mb-5 flex items-center justify-between gap-3">
                <p className="text-sm text-text-muted">
                    {safeProducts.length} produtos cadastrados
                </p>
                <div className="flex flex-wrap items-center justify-end gap-2">
                    {importExportEnabled && (
                        <>
                            <a
                                href="/admin/produtos/exportar"
                                className="inline-flex items-center gap-2 rounded-lg border border-border bg-white px-4 py-2.5 text-sm font-black text-navy hover:bg-bg-soft"
                            >
                                <Download className="h-4 w-4" /> Exportar
                            </a>
                            <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-border bg-white px-4 py-2.5 text-sm font-black text-navy hover:bg-bg-soft">
                                <Upload className="h-4 w-4" /> Importar
                                <input
                                    type="file"
                                    accept=".csv,text/csv"
                                    onChange={(event) =>
                                        importProducts(
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="sr-only"
                                />
                            </label>
                        </>
                    )}
                    <Link
                        href="/admin/produtos/novo"
                        className="inline-flex items-center gap-2 rounded-lg bg-yellow px-4 py-2.5 text-sm font-black text-navy"
                    >
                        <Plus className="h-4 w-4" /> Cadastrar produto
                    </Link>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border border-border bg-white">
                {safeProducts.length === 0 ? (
                    <div className="p-12 text-center text-text-muted">
                        Nenhum produto cadastrado.
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-bg-soft text-xs text-text-muted uppercase">
                                <tr>
                                    <th className="px-5 py-3">Produto</th>
                                    <th className="px-5 py-3">Categoria</th>
                                    <th className="px-5 py-3">Preco</th>
                                    <th className="px-5 py-3">Estoque</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3 text-right">
                                        Acoes
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {safeProducts.map((product) => {
                                    const lowStockCount = showStockAlerts
                                        ? (product.variations ?? []).filter(
                                              (variation) => variation.lowStock,
                                          ).length
                                        : 0;

                                    return (
                                        <tr
                                            key={product.id}
                                            className={
                                                product.status === 'archived'
                                                    ? 'opacity-60'
                                                    : ''
                                            }
                                        >
                                            <td className="px-5 py-4">
                                                <div className="font-bold text-navy">
                                                    {product.name}
                                                </div>
                                                <div className="text-xs text-text-muted">
                                                    {product.sku}
                                                </div>
                                            </td>
                                            <td className="px-5 py-4 text-text-muted">
                                                {product.category}
                                            </td>
                                            <td className="px-5 py-4">
                                                {formatMoney(
                                                    product.priceAmount,
                                                    product.priceCurrency,
                                                )}
                                            </td>
                                            <td className="px-5 py-4">
                                                {lowStockCount > 0 ? (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-black text-red-800">
                                                        <AlertTriangle className="h-3.5 w-3.5" />{' '}
                                                        {lowStockCount}{' '}
                                                        alerta(s)
                                                    </span>
                                                ) : product.canSellWithoutStock ? (
                                                    <span className="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-black text-blue-800">
                                                        Venda sem estoque
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-green-100 px-2.5 py-1 text-xs font-black text-green-800">
                                                        {product.stockAvailable}{' '}
                                                        disponiveis
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-4">
                                                <span
                                                    className={`rounded-full px-2.5 py-1 text-xs font-bold ${product.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700'}`}
                                                >
                                                    {product.status === 'active'
                                                        ? 'Ativo'
                                                        : product.status ===
                                                            'archived'
                                                          ? 'Arquivado'
                                                          : 'Rascunho'}
                                                </span>
                                            </td>
                                            <td className="px-5 py-4">
                                                <div className="flex justify-end gap-1">
                                                    <Link
                                                        href={`/admin/produtos/${product.id}/editar`}
                                                        className="rounded-md p-2 text-navy hover:bg-bg-soft"
                                                        aria-label={`Editar ${product.name}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                    {product.status !==
                                                        'archived' && (
                                                        <button
                                                            onClick={() =>
                                                                archiveProduct(
                                                                    product,
                                                                )
                                                            }
                                                            className="rounded-md p-2 text-text-muted hover:bg-red-50 hover:text-red-700"
                                                            aria-label={`Arquivar ${product.name}`}
                                                        >
                                                            <Archive className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
