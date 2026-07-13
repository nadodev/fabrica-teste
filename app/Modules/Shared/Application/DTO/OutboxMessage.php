<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\DTO;

final readonly class OutboxMessage
{
    /** @param array<string, mixed> $payload */
    public function __construct(public string $id, public string $type, public array $payload) {}
}
