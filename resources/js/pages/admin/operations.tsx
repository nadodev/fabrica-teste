import { Head, router } from '@inertiajs/react';
import { Database, FileText, ShieldCheck } from 'lucide-react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type Check = { label: string; value: string };

export default function Operations({
    checks = [],
    logs = [],
    backups = [],
}: {
    checks?: Check[];
    logs?: string[];
    backups?: string[];
}) {
    const createBackup = () => {
        router.post(
            '/admin/operacao/backup',
            {},
            {
                headers: { 'Idempotency-Key': createIdempotencyKey() },
                preserveScroll: true,
            },
        );
    };

    return (
        <AdminLayout title="Operacao">
            <Head title="Operacao" />
            <div className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <section className="space-y-6">
                    <div className="rounded-xl border border-border bg-white p-5">
                        <div className="mb-4 flex items-center gap-2 font-display text-lg font-black text-navy">
                            <ShieldCheck className="h-5 w-5 text-yellow" />{' '}
                            Checklist
                        </div>
                        <div className="divide-y divide-border">
                            {checks.map((check) => (
                                <div
                                    key={check.label}
                                    className="flex justify-between gap-4 py-3 text-sm"
                                >
                                    <span className="text-text-muted">
                                        {check.label}
                                    </span>
                                    <strong className="text-navy">
                                        {check.value}
                                    </strong>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="rounded-xl border border-border bg-white p-5">
                        <div className="mb-4 flex items-center gap-2 font-display text-lg font-black text-navy">
                            <Database className="h-5 w-5 text-yellow" /> Backup
                        </div>
                        <button
                            onClick={createBackup}
                            className="rounded-lg bg-yellow px-5 py-3 font-black text-navy"
                        >
                            Criar backup do banco
                        </button>
                        <div className="mt-4 space-y-2 text-sm text-text-muted">
                            {backups.map((backup) => (
                                <div key={backup}>{backup}</div>
                            ))}
                        </div>
                    </div>
                </section>
                <section className="rounded-xl border border-border bg-white p-5">
                    <div className="mb-4 flex items-center gap-2 font-display text-lg font-black text-navy">
                        <FileText className="h-5 w-5 text-yellow" /> Logs
                        recentes
                    </div>
                    <pre className="max-h-[620px] overflow-auto rounded-lg bg-bg-soft p-4 text-xs whitespace-pre-wrap text-text-dark">
                        {logs.join('\n') || 'Sem logs.'}
                    </pre>
                </section>
            </div>
        </AdminLayout>
    );
}
