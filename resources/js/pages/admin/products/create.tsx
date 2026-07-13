import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import { AdminProductForm } from '@/modules/catalog/ui/admin-product-form';

export default function CreateProduct({
    categories = [],
}: {
    categories?: string[];
}) {
    return (
        <AdminLayout title="Cadastrar produto">
            <Head title="Cadastrar produto" />
            <Link
                href="/admin/produtos"
                className="mb-5 inline-flex items-center gap-2 text-sm font-bold text-navy hover:underline"
            >
                <ArrowLeft className="h-4 w-4" /> Voltar para produtos
            </Link>
            <AdminProductForm categories={categories} />
        </AdminLayout>
    );
}
