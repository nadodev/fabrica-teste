<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\DTO;

final readonly class AdminAuditEntry
{
    /** @param array<string, string|int|bool|null> $metadata */
    public function __construct(
        public ?int $actorUserId,
        public string $action,
        public ?string $subjectType,
        public ?string $subjectId,
        public string $outcome,
        public ?int $httpStatus,
        public ?string $ipHash,
        public ?string $userAgent,
        public array $metadata = [],
    ) {}
}
