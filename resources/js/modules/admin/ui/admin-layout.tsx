import { Link, router, usePage } from '@inertiajs/react';
import {
    BadgePercent,
    BarChart3,
    Bell,
    Boxes,
    ClipboardList,
    ExternalLink,
    Image,
    LayoutDashboard,
    LogOut,
    MapPin,
    Megaphone,
    Settings,
    Tags,
    Text,
    Truck,
    Users,
    Warehouse,
} from 'lucide-react';
import type { ReactNode } from 'react';

export function AdminLayout({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const page = usePage<{
        auth: { user: { name: string } };
        siteSettings: { storeName: string; logoUrl: string };
    }>().props;
    const user = page.auth.user;
    const settings = page.siteSettings;

    return (
        <div className="min-h-screen bg-bg-soft lg:grid lg:grid-cols-[250px_1fr]">
            <aside className="bg-navy p-5 text-white">
                <Link
                    href="/admin"
                    className="flex items-center gap-3 font-display text-xl font-black"
                >
                    <span className="inline-flex rounded bg-white p-1">
                        <img
                            src={settings.logoUrl}
                            alt={settings.storeName}
                            className="h-8 w-auto"
                        />
                    </span>{' '}
                    Admin
                </Link>
                <nav className="mt-8 space-y-2">
                    <Link
                        href="/admin"
                        className="flex items-center gap-3 rounded-lg bg-white/10 px-3 py-2.5 text-sm font-bold"
                    >
                        <LayoutDashboard className="h-4 w-4" /> Dashboard
                    </Link>
                    <Link
                        href="/admin/relatorios"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <BarChart3 className="h-4 w-4" /> Relatorios
                    </Link>
                    <Link
                        href="/admin/marketing"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Megaphone className="h-4 w-4" /> Marketing
                    </Link>
                    <Link
                        href="/admin/clientes"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Users className="h-4 w-4" /> Clientes
                    </Link>
                    <Link
                        href="/admin/pedidos"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <ClipboardList className="h-4 w-4" /> Pedidos
                    </Link>
                    <Link
                        href="/admin/produtos"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Boxes className="h-4 w-4" /> Produtos
                    </Link>
                    <Link
                        href="/admin/estoque"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Warehouse className="h-4 w-4" /> Estoque
                    </Link>
                    <Link
                        href="/admin/frete"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Truck className="h-4 w-4" /> Frete
                    </Link>
                    <Link
                        href="/admin/categorias-produtos"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Tags className="h-4 w-4" /> Categorias produto
                    </Link>
                    <Link
                        href="/admin/cupons"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <BadgePercent className="h-4 w-4" /> Cupons
                    </Link>
                    <Link
                        href="/admin/conteudo/banners"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Image className="h-4 w-4" /> Banners
                    </Link>
                    <Link
                        href="/admin/conteudo/notificacoes"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Bell className="h-4 w-4" /> Notificacoes
                    </Link>
                    <Link
                        href="/admin/conteudo/lojas"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <MapPin className="h-4 w-4" /> Lojas
                    </Link>
                    <Link
                        href="/admin/conteudo/historia"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Text className="h-4 w-4" /> Nossa historia
                    </Link>
                    <Link
                        href="/admin/configuracoes"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <Settings className="h-4 w-4" /> Configuracoes
                    </Link>
                    <Link
                        href="/produtos"
                        className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                    >
                        <ExternalLink className="h-4 w-4" /> Ver loja
                    </Link>
                </nav>
            </aside>
            <div>
                <header className="flex items-center justify-between border-b border-border bg-white px-5 py-4 md:px-8">
                    <div>
                        <div className="text-xs text-text-muted">
                            Painel administrativo
                        </div>
                        <h1 className="font-display text-xl font-black text-navy">
                            {title}
                        </h1>
                    </div>
                    <div className="flex items-center gap-3 text-sm">
                        <span className="hidden text-text-muted sm:inline">
                            {user.name}
                        </span>
                        <button
                            onClick={() => router.post('/admin/logout')}
                            className="rounded-md p-2 text-navy hover:bg-bg-soft"
                            aria-label="Sair"
                        >
                            <LogOut className="h-4 w-4" />
                        </button>
                    </div>
                </header>
                <main className="p-5 md:p-8">{children}</main>
            </div>
        </div>
    );
}
