import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType, CSSProperties } from 'react';
import { createRoot } from 'react-dom/client';
import { CookieNotice } from '@/components/cookie-notice';
import { FlashToast } from '@/components/flash-toast';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';

const appName = import.meta.env.VITE_APP_NAME || 'FARDA+';
let runtimeAppName = appName;

const defaultSiteSettings: SiteSettings = {
    storeName: 'Fábrica de Fardamentos',
    logoUrl: '/storage/site/logo.png',
    primaryColor: '#123a6b',
    secondaryColor: '#f5c542',
    socialLinks: [],
};

type SiteSettings = {
    storeName: string;
    logoUrl: string;
    footerLogoUrl?: string;
    faviconUrl?: string | null;
    shareImageUrl?: string | null;
    legalName?: string;
    documentNumber?: string;
    primaryColor: string;
    secondaryColor: string;
    contactEmail?: string;
    contactPhone?: string;
    whatsapp?: string;
    businessHours?: string;
    companyAddress?: string;
    payments?: {
        pixEnabled?: boolean;
        cardEnabled?: boolean;
        boletoEnabled?: boolean;
    };
    policies?: {
        termsUrl?: string;
        privacyUrl?: string;
        cookieNotice?: boolean;
        warrantyInfo?: string;
    };
    seo?: {
        title?: string;
        description?: string;
        keywords?: string;
        googleAnalytics?: string;
        googleTagManager?: string;
        metaPixel?: string;
        socialIntegration?: boolean;
    };
    socialLinks?: { label: string; url: string }[];
};

type CartSummary = {
    itemsCount: number;
};

type TopbarNotification = {
    message: string;
    linkLabel: string | null;
    linkUrl: string | null;
};

type AuthUser = {
    name: string;
    email: string;
    is_admin?: boolean;
};

type Flash = { success?: string | null };
type CatalogCategory = {
    name: string;
    slug: string | null;
    imageUrl: string | null;
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
    title: (title) => (title ? `${title} - ${runtimeAppName}` : runtimeAppName),
    resolve: (name) => {
        const pages = import.meta.glob<{ default: ComponentType }>(
            './pages/**/*.tsx',
            { eager: true },
        );

        return pages[`./pages/${name}.tsx`];
    },
    setup({ el, App, props }) {
        const isBackOffice =
            props.initialPage.component.startsWith('admin/') ||
            props.initialPage.component.startsWith('auth/');
        const settings =
            (props.initialPage.props.siteSettings as
                SiteSettings | undefined) ?? defaultSiteSettings;
        runtimeAppName = settings.seo?.title || settings.storeName || appName;
        const cartSummary = props.initialPage.props.cartSummary as
            CartSummary | undefined;
        const topbarNotification = props.initialPage.props
            .topbarNotification as TopbarNotification | null | undefined;
        const catalogCategories = props.initialPage.props.catalogCategories as
            CatalogCategory[] | undefined;
        const auth = props.initialPage.props.auth as
            { user?: AuthUser | null } | undefined;
        const flash = props.initialPage.props.flash as Flash | null | undefined;
        const primary = settings?.primaryColor ?? '#123a6b';
        const secondary = settings?.secondaryColor ?? '#f5c542';
        const themeStyle = {
            '--navy': primary,
            '--navy-deep': shadeHex(primary, -18),
            '--yellow': secondary,
            '--yellow-soft': shadeHex(secondary, 35),
            '--ring': primary,
        } as CSSProperties;
        applyRuntimeSettings(settings);

        createRoot(el).render(
            <div
                className="flex min-h-screen flex-col bg-white"
                style={themeStyle}
            >
                {!isBackOffice && (
                    <SiteHeader
                        settings={settings}
                        initialCartItemsCount={cartSummary?.itemsCount ?? 0}
                        notification={topbarNotification ?? null}
                        user={auth?.user ?? null}
                        categories={catalogCategories ?? []}
                    />
                )}
                <main className="flex-1">
                    <App {...props} />
                </main>
                {!isBackOffice && <SiteFooter settings={settings} />}
                {!isBackOffice && (
                    <CookieNotice
                        enabled={settings.policies?.cookieNotice !== false}
                        privacyUrl={
                            settings.policies?.privacyUrl || '/privacidade'
                        }
                    />
                )}
                <FlashToast initialFlash={flash ?? null} />
            </div>,
        );
    },
    progress: {
        color: '#f5c542',
    },
});

function applyRuntimeSettings(settings: SiteSettings) {
    if (settings.faviconUrl) {
        let favicon =
            document.querySelector<HTMLLinkElement>('link[rel="icon"]');

        if (!favicon) {
            favicon = document.createElement('link');
            favicon.rel = 'icon';
            document.head.appendChild(favicon);
        }

        favicon.href = settings.faviconUrl;
    }

    const seo = settings.seo ?? {};
    setMeta('description', seo.description ?? '');
    setMeta('keywords', seo.keywords ?? '');

    if (seo.socialIntegration !== false) {
        setMeta('og:title', seo.title || settings.storeName, true);
        setMeta('og:description', seo.description ?? '', true);
        setMeta('og:image', settings.shareImageUrl ?? '', true);
    }

    const analyticsId = seo.googleAnalytics?.trim();

    if (analyticsId) {
        addTrackingScript(
            'google-analytics',
            analyticsId,
            (id) =>
                `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`,
        );
        addInlineTrackingScript(
            'google-analytics-config',
            `window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',${JSON.stringify(analyticsId)});`,
        );
    }

    const tagManagerId = seo.googleTagManager?.trim();

    if (tagManagerId) {
        addInlineTrackingScript(
            'google-tag-manager-config',
            `window.dataLayer=window.dataLayer||[];window.dataLayer.push({'gtm.start':new Date().getTime(),event:'gtm.js'});`,
        );
        addTrackingScript(
            'google-tag-manager',
            tagManagerId,
            (id) =>
                `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(id)}`,
        );
    }

    const pixelId = seo.metaPixel?.trim();

    if (pixelId) {
        addInlineTrackingScript(
            'meta-pixel-config',
            `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',${JSON.stringify(pixelId)});fbq('track','PageView');`,
        );
    }
}

function setMeta(name: string, content: string, property = false) {
    if (!content) {
        return;
    }

    const attribute = property ? 'property' : 'name';
    let meta = document.head.querySelector<HTMLMetaElement>(
        `meta[${attribute}="${name}"]`,
    );

    if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute(attribute, name);
        document.head.appendChild(meta);
    }

    meta.content = content;
}

function addTrackingScript(
    key: string,
    value: string | undefined,
    source: (id: string) => string,
) {
    const id = value?.trim();

    if (!id || document.querySelector(`script[data-store-tracking="${key}"]`)) {
        return;
    }

    const script = document.createElement('script');
    script.async = true;
    script.dataset.storeTracking = key;
    script.src = source(id);
    document.head.appendChild(script);
}

function addInlineTrackingScript(key: string, content: string) {
    if (document.querySelector(`script[data-store-tracking="${key}"]`)) {
        return;
    }

    const script = document.createElement('script');
    script.dataset.storeTracking = key;
    script.text = content;
    document.head.appendChild(script);
}
