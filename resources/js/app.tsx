import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';

const appName = import.meta.env.VITE_APP_NAME || 'FARDA+';

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
        createRoot(el).render(
            <div className="flex min-h-screen flex-col bg-white">
                <SiteHeader />
                <main className="flex-1">
                    <App {...props} />
                </main>
                <SiteFooter />
            </div>,
        );
    },
    progress: {
        color: '#f5c542',
    },
});
