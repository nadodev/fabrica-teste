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
    ShieldCheck,
    Tags,
    Text,
    Truck,
    Users,
    Warehouse,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type NavigationItem = {
    href: string;
    label: string;
    icon: LucideIcon;
};

const navigationGroups: Array<{
    label: string;
    items: NavigationItem[];
}> = [
    {
        label: 'Catálogo',
        items: [
            { href: '/admin/produtos', label: 'Produtos', icon: Boxes },
            {
                href: '/admin/categorias-produtos',
                label: 'Categorias',
                icon: Tags,
            },
            { href: '/admin/estoque', label: 'Estoque', icon: Warehouse },
        ],
    },
    {
        label: 'Vendas',
        items: [
            { href: '/admin/pedidos', label: 'Pedidos', icon: ClipboardList },
            {
                href: '/admin/cupons',
                label: 'Cupons e promoções',
                icon: BadgePercent,
            },
        ],
    },
    {
        label: 'Clientes',
        items: [{ href: '/admin/clientes', label: 'Clientes', icon: Users }],
    },
    {
        label: 'Conteúdo',
        items: [
            {
                href: '/admin/conteudo/banners',
                label: 'Banners',
                icon: Image,
            },
            {
                href: '/admin/conteudo/notificacoes',
                label: 'Notificações',
                icon: Bell,
            },
            {
                href: '/admin/conteudo/lojas',
                label: 'Lojas',
                icon: MapPin,
            },
            {
                href: '/admin/conteudo/historia',
                label: 'Nossa história',
                icon: Text,
            },
            { href: '/admin/marketing', label: 'Marketing', icon: Megaphone },
        ],
    },
    {
        label: 'Relatórios',
        items: [
            {
                href: '/admin/relatorios',
                label: 'Relatórios',
                icon: BarChart3,
            },
        ],
    },
    {
        label: 'Configurações',
        items: [
            {
                href: '/admin/configuracoes',
                label: 'Geral',
                icon: Settings,
            },
            {
                href: '/admin/frete',
                label: 'Frete e entrega',
                icon: Truck,
            },
            {
                href: '/admin/operacao',
                label: 'Sistema e segurança',
                icon: ShieldCheck,
            },
        ],
    },
];

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
    }>();
    const user = page.props.auth.user;
    const settings = page.props.siteSettings;
    const currentPath = page.url.split('?')[0];

    return (
        <div className="min-h-screen bg-bg-soft lg:grid lg:grid-cols-[250px_1fr]">
            <aside className="bg-navy p-5 text-white lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto">
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
                <nav
                    className="mt-7 space-y-5"
                    aria-label="Navegação administrativa"
                >
                    <AdminNavLink
                        item={{
                            href: '/admin',
                            label: 'Dashboard',
                            icon: LayoutDashboard,
                        }}
                        active={currentPath === '/admin'}
                    />
                    {navigationGroups.map((group) => (
                        <section key={group.label}>
                            <h2 className="px-3 text-[11px] font-black tracking-[0.14em] text-white/45 uppercase">
                                {group.label}
                            </h2>
                            <div className="mt-1 space-y-1 border-l border-white/10 pl-2">
                                {group.items.map((item) => (
                                    <AdminNavLink
                                        key={item.href}
                                        item={item}
                                        active={
                                            currentPath === item.href ||
                                            currentPath.startsWith(
                                                `${item.href}/`,
                                            )
                                        }
                                    />
                                ))}
                            </div>
                        </section>
                    ))}
                    <div className="border-t border-white/10 pt-4">
                        <Link
                            href="/produtos"
                            className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10"
                        >
                            <ExternalLink className="h-4 w-4" /> Ver loja
                        </Link>
                    </div>
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

function AdminNavLink({
    item,
    active,
}: {
    item: NavigationItem;
    active: boolean;
}) {
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            aria-current={active ? 'page' : undefined}
            className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition ${active ? 'bg-white/15 font-bold text-white shadow-sm' : 'font-semibold text-white/80 hover:bg-white/10 hover:text-white'}`}
        >
            <Icon className="h-4 w-4 shrink-0" />
            <span>{item.label}</span>
        </Link>
    );
}
