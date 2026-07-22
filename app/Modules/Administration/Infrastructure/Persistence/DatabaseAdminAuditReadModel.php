<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Modules\Administration\Application\Port\AdminAuditReadModel;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseAdminAuditReadModel implements AdminAuditReadModel
{
    public function __construct(private ConnectionInterface $database) {}

    public function latest(int $limit = 50): array
    {
        return array_values($this->database->table('admin_audit_logs')
            ->leftJoin('users', 'users.id', '=', 'admin_audit_logs.actor_user_id')
            ->orderByDesc('admin_audit_logs.created_at')
            ->limit($limit)
            ->get([
                'admin_audit_logs.id',
                'admin_audit_logs.action',
                'admin_audit_logs.subject_type',
                'admin_audit_logs.subject_id',
                'admin_audit_logs.outcome',
                'admin_audit_logs.http_status',
                'admin_audit_logs.created_at',
                'users.name as actor_name',
            ])
            ->map(static fn (object $entry): array => [
                'id' => (string) $entry->id,
                'action' => (string) $entry->action,
                'subjectType' => $entry->subject_type === null ? null : (string) $entry->subject_type,
                'subjectId' => $entry->subject_id === null ? null : (string) $entry->subject_id,
                'outcome' => (string) $entry->outcome,
                'httpStatus' => $entry->http_status === null ? null : (int) $entry->http_status,
                'createdAt' => (string) $entry->created_at,
                'actorName' => $entry->actor_name === null ? 'Conta removida' : (string) $entry->actor_name,
            ])
            ->all());
    }
}
