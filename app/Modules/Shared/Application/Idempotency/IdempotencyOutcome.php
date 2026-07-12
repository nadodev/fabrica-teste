<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Idempotency;

enum IdempotencyOutcome: string
{
    case Acquired = 'acquired';
    case Replay = 'replay';
    case InProgress = 'in_progress';
    case Conflict = 'conflict';
}
