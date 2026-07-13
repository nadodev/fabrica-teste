import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import type { CatalogProduct } from '@/modules/catalog/domain/product';
import { AdminProductForm } from '@/modules/catalog/ui/admin-product-form';

export default function EditProduct({
    product,
    categories = [],
}: {
    product: CatalogProduct;
    categories?: string[];
}) {
    return (
        <AdminLayout title="Editar produto">
            <Head title={`Editar ${product.name}`} />
            <Link
                href="/admin/produtos"
                className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline"
            >
                <ArrowLeft className="h-4 w-4" /> Voltar para produtos
            </Link>
            <AdminProductForm product={product} categories={categories} />
        </AdminLayout>
    );
}
