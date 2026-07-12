<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Idempotency;

final readonly class IdempotencyClaim
{
    /** @param array<string, string> $headers */
    public function __construct(
        public IdempotencyOutcome $outcome,
        public ?int $responseCode = null,
        public array $headers = [],
        public ?string $body = null,
    ) {}
}
