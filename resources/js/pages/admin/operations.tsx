import { Head, router, usePage } from '@inertiajs/react';
import { Database, History, ShieldCheck } from 'lucide-react';
import { createIdempotencyKey } from '@/lib/idempotency-key';
import { AdminLayout } from '@/modules/admin/ui/admin-layout';

type Check = { label: string; value: string };
type AuditEntry = {
    id: string;
    action: string;
    subjectType: string | null;
    subjectId: string | null;
    outcome: string;
    httpStatus: number | null;
    createdAt: string;
    actorName: string;
};

export default function Operations({
    checks = [],
    auditEntries = [],
    backups = [],
}: {
    checks?: Check[];
    auditEntries?: AuditEntry[];
    backups?: string[];
}) {
    const page = usePage<{
        auth: {
            user: {
                permissions: string[];
                is_super_admin: boolean;
            };
        };
    }>();
    const canBackup =
        page.props.auth.user.is_super_admin ||
        page.props.auth.user.permissions.includes('admin.backups.manage');
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
                        {canBackup && (
                            <button
                                onClick={createBackup}
                                className="rounded-lg bg-yellow px-5 py-3 font-black text-navy"
                            >
                                Criar backup do banco
                            </button>
                        )}
                        <div className="mt-4 space-y-2 text-sm text-text-muted">
                            {backups.map((backup) => (
                                <div key={backup}>{backup}</div>
                            ))}
                        </div>
                    </div>
                </section>
                <section className="rounded-xl border border-border bg-white p-5">
                    <div className="mb-4 flex items-center gap-2 font-display text-lg font-black text-navy">
                        <History className="h-5 w-5 text-yellow" /> Auditoria
                        administrativa
                    </div>
                    <div className="max-h-[620px] space-y-2 overflow-auto">
                        {auditEntries.map((entry) => (
                            <article
                                key={entry.id}
                                className="rounded-lg border border-border bg-bg-soft p-3 text-xs"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <strong className="text-navy">
                                        {entry.action}
                                    </strong>
                                    <span
                                        className={
                                            entry.outcome === 'completed'
                                                ? 'font-bold text-green-700'
                                                : entry.outcome === 'rejected'
                                                  ? 'font-bold text-red-700'
                                                  : 'font-bold text-amber-700'
                                        }
                                    >
                                        {entry.outcome}
                                    </span>
                                </div>
                                <div className="mt-1 text-text-muted">
                                    {entry.actorName} ·{' '}
                                    {new Date(entry.createdAt).toLocaleString(
                                        'pt-BR',
                                    )}
                                    {entry.httpStatus
                                        ? ` · HTTP ${entry.httpStatus}`
                                        : ''}
                                </div>
                                {entry.subjectId && (
                                    <div className="mt-1 break-all text-text-muted">
                                        {entry.subjectType}: {entry.subjectId}
                                    </div>
                                )}
                            </article>
                        ))}
                        {auditEntries.length === 0 && (
                            <p className="rounded-lg bg-bg-soft p-4 text-sm text-text-muted">
                                Nenhuma mutação administrativa registrada.
                            </p>
                        )}
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}
