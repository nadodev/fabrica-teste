<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure\Persistence;

use App\Modules\Administration\Application\DTO\AdminAuditEntry;
use App\Modules\Administration\Application\Port\AdminAuditRecorder;
use App\Modules\Administration\Application\Port\AdminAuditRetention;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final readonly class DatabaseAdminAuditRecorder implements AdminAuditRecorder, AdminAuditRetention
{
    public function __construct(private ConnectionInterface $database) {}

    public function record(AdminAuditEntry $entry): void
    {
        $this->database->table('admin_audit_logs')->insert([
            'id' => (string) Str::uuid(),
            'actor_user_id' => $entry->actorUserId,
            'action' => mb_substr($entry->action, 0, 160),
            'subject_type' => $entry->subjectType === null ? null : mb_substr($entry->subjectType, 0, 100),
            'subject_id' => $entry->subjectId === null ? null : mb_substr($entry->subjectId, 0, 160),
            'outcome' => mb_substr($entry->outcome, 0, 30),
            'http_status' => $entry->httpStatus,
            'ip_hash' => $entry->ipHash,
            'user_agent' => $entry->userAgent === null ? null : mb_substr($entry->userAgent, 0, 500),
            'metadata' => $entry->metadata === [] ? null : json_encode($entry->metadata, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    public function pruneBefore(DateTimeImmutable $threshold): int
    {
        return $this->database->table('admin_audit_logs')
            ->where('created_at', '<', $threshold)
            ->delete();
    }
}
