<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Port;

use App\Modules\Shared\Application\Idempotency\IdempotencyClaim;

interface IdempotencyStore
{
    public function claim(string $scope, string $key, string $fingerprint, int $ttlSeconds): IdempotencyClaim;

    /** @param array<string, string> $headers */
    public function complete(string $scope, string $key, int $responseCode, array $headers, string $body): void;

    public function release(string $scope, string $key): void;
}
