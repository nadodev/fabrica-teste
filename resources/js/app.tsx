import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType, CSSProperties } from 'react';
import { createRoot } from 'react-dom/client';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';

const appName = import.meta.env.VITE_APP_NAME || 'FARDA+';

const defaultSiteSettings = {
    storeName: 'Fábrica de Fardamentos',
    logoUrl: '/storage/site/logo.png',
    primaryColor: '#123a6b',
    secondaryColor: '#f5c542',
};

type SiteSettings = {
    storeName: string;
    logoUrl: string;
    primaryColor: string;
    secondaryColor: string;
};

type CartSummary = {
    itemsCount: number;
};

type TopbarNotification = {
    message: string;
    linkLabel: string | null;
    linkUrl: string | null;
};

function shadeHex(hex: string, percent: number) {
    const clean = /^#[0-9a-fA-F]{6}$/.test(hex) ? hex.slice(1) : '123a6b';
    const num = Number.parseInt(clean, 16);
    const amt = Math.round(2.55 * percent);
    const r = Math.max(0, Math.min(255, (num >> 16) + amt));
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00ff) + amt));
    const b = Math.max(0, Math.min(255, (num & 0x0000ff) + amt));

    return `#${(0x1000000 + r * 0x10000 + g * 0x100 + b).toString(16).slice(1)}`;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob<{ default: ComponentType }>(
            './pages/**/*.tsx',
            { eager: true },
        );

        return pages[`./pages/${name}.tsx`];
    },
    setup({ el, App, props }) {
        const isBackOffice = props.initialPage.component.startsWith('admin/') || props.initialPage.component.startsWith('auth/');
        const settings = (props.initialPage.props.siteSettings as SiteSettings | undefined) ?? defaultSiteSettings;
        const cartSummary = props.initialPage.props.cartSummary as CartSummary | undefined;
        const topbarNotification = props.initialPage.props.topbarNotification as TopbarNotification | null | undefined;
        const primary = settings?.primaryColor ?? '#123a6b';
        const secondary = settings?.secondaryColor ?? '#f5c542';
        const themeStyle = {
            '--navy': primary,
            '--navy-deep': shadeHex(primary, -18),
            '--yellow': secondary,
            '--yellow-soft': shadeHex(secondary, 35),
            '--ring': primary,
        } as CSSProperties;

        createRoot(el).render(
            <div className="flex min-h-screen flex-col bg-white" style={themeStyle}>
                {!isBackOffice && <SiteHeader settings={settings} initialCartItemsCount={cartSummary?.itemsCount ?? 0} notification={topbarNotification ?? null} />}
                <main className="flex-1">
                    <App {...props} />
                </main>
                {!isBackOffice && <SiteFooter settings={settings} />}
            </div>,
        );
    },
    progress: {
        color: '#f5c542',
    },
});
