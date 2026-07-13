import { Link } from '@inertiajs/react';
import { useState } from 'react';

export function CookieNotice({
    enabled,
    privacyUrl,
}: {
    enabled: boolean;
    privacyUrl: string;
}) {
    const [visible, setVisible] = useState(
        () =>
            enabled &&
            window.localStorage.getItem('cookie-notice-accepted') !== '1',
    );

    if (!visible) {
        return null;
    }

    const accept = () => {
        window.localStorage.setItem('cookie-notice-accepted', '1');
        setVisible(false);
    };

    return (
        <div className="fixed inset-x-3 bottom-3 z-[120] mx-auto flex max-w-3xl flex-col gap-3 rounded-xl border border-border bg-white p-4 shadow-[var(--shadow-card)] sm:flex-row sm:items-center">
            <p className="flex-1 text-sm leading-6 text-text-muted">
                Usamos armazenamento essencial para manter sua sessão e seu
                carrinho. Consulte nossa{' '}
                <Link
                    href={privacyUrl}
                    className="font-bold text-navy underline"
                >
                    política de privacidade
                </Link>
                .
            </p>
            <button
                type="button"
                onClick={accept}
                className="rounded-lg bg-navy px-5 py-2.5 text-sm font-black text-white"
            >
                Entendi
            </button>
        </div>
    );
}
