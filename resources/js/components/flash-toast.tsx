import { router } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useEffect, useState } from 'react';

type Flash = { success?: string | null };

export function FlashToast({ initialFlash }: { initialFlash?: Flash | null }) {
    const [message, setMessage] = useState(initialFlash?.success ?? '');

    useEffect(() => {
        if (!message) {
            return;
        }

        const timer = window.setTimeout(() => setMessage(''), 3200);

        return () => window.clearTimeout(timer);
    }, [message]);

    useEffect(() => {
        return router.on('success', (event) => {
            const flash = event.detail.page.props.flash as Flash | undefined;

            if (flash?.success) {
                setMessage(flash.success);
            }
        });
    }, []);

    if (!message) {
        return null;
    }

    return (
        <div className="fixed right-4 bottom-4 z-[80] max-w-sm rounded-xl border border-green-200 bg-white p-4 text-sm font-bold text-navy shadow-[var(--shadow-card)]">
            <div className="flex items-start gap-3">
                <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-green-700" />
                <span>{message}</span>
            </div>
        </div>
    );
}
