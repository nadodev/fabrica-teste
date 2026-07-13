import { Head, Link } from '@inertiajs/react';
import { BadgePercent, Bell, Image, Megaphone } from 'lucide-react';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';
import { formatMoney } from '@/modules/catalog/domain/product';

type Props = {
    stats: {
        activeBanners: number;
        activeCoupons: number;
        activeNotifications: number;
        couponDiscounts: number;
    };
    coupons: {
        id: string;
        code: string;
        is_active: boolean;
        used_count: number;
    }[];
    banners: { id: string; title: string; is_active: boolean }[];
    notifications: { id: string; message: string; is_active: boolean }[];
};

export default function Marketing({
    stats,
    coupons = [],
    banners = [],
    notifications = [],
}: Props) {
    return (
        <AdminLayout title="Marketing">
            <Head title="Marketing" />
            <div className="grid gap-4 md:grid-cols-4">
                <Metric
                    icon={Image}
                    label="Banners ativos"
                    value={String(stats.activeBanners)}
                    href="/admin/conteudo/banners"
                />
                <Metric
                    icon={BadgePercent}
                    label="Cupons ativos"
                    value={String(stats.activeCoupons)}
                    href="/admin/cupons"
                />
                <Metric
                    icon={Bell}
                    label="Notificacoes ativas"
                    value={String(stats.activeNotifications)}
                    href="/admin/conteudo/notificacoes"
                />
                <Metric
                    icon={Megaphone}
                    label="Descontos dados"
                    value={formatMoney(stats.couponDiscounts, 'BRL')}
                    href="/admin/relatorios"
                />
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-3">
                <Panel title="Cupons recentes" href="/admin/cupons">
                    {coupons.map((item) => (
                        <Row
                            key={item.id}
                            label={item.code}
                            value={`${item.used_count ?? 0} uso(s)`}
                            active={item.is_active}
                        />
                    ))}
                </Panel>
                <Panel title="Banners" href="/admin/conteudo/banners">
                    {banners.map((item) => (
                        <Row
                            key={item.id}
                            label={item.title}
                            value={item.is_active ? 'Ativo' : 'Inativo'}
                            active={item.is_active}
                        />
                    ))}
                </Panel>
                <Panel title="Topbar" href="/admin/conteudo/notificacoes">
                    {notifications.map((item) => (
                        <Row
                            key={item.id}
                            label={item.message}
                            value={item.is_active ? 'Ativa' : 'Inativa'}
                            active={item.is_active}
                        />
                    ))}
                </Panel>
            </div>
        </AdminLayout>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
    href,
}: {
    icon: typeof Image;
    label: string;
    value: string;
    href: string;
}) {
    return (
        <Link
            href={href}
            className="rounded-xl border border-border bg-white p-5 transition hover:shadow-[var(--shadow-soft)]"
        >
            <Icon className="h-5 w-5 text-yellow" />
            <div className="mt-3 text-xs font-bold text-text-muted uppercase">
                {label}
            </div>
            <div className="mt-1 font-display text-2xl font-black text-navy">
                {value}
            </div>
        </Link>
    );
}

function Panel({
    title,
    href,
    children,
}: {
    title: string;
    href: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-xl border border-border bg-white p-5">
            <div className="mb-3 flex items-center justify-between">
                <h2 className="font-display text-lg font-black text-navy">
                    {title}
                </h2>
                <Link
                    href={href}
                    className="text-xs font-black text-navy hover:underline"
                >
                    Editar
                </Link>
            </div>
            <div className="divide-y divide-border">{children}</div>
        </section>
    );
}

function Row({
    label,
    value,
    active,
}: {
    label: string;
    value: string;
    active: boolean;
}) {
    return (
        <div className="flex justify-between gap-4 py-3 text-sm">
            <span className="line-clamp-1 text-text-muted">{label}</span>
            <strong className={active ? 'text-green-700' : 'text-text-muted'}>
                {value}
            </strong>
        </div>
    );
}
