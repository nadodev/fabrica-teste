<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure;

use App\Modules\Shared\Application\Port\IdempotencyStore;
use App\Modules\Shared\Application\Port\OutboxQueue;
use App\Modules\Shared\Application\Port\OutboxStore;
use App\Modules\Shared\Application\Port\TransactionManager;
use App\Modules\Shared\Infrastructure\Idempotency\DatabaseIdempotencyStore;
use App\Modules\Shared\Infrastructure\Outbox\DatabaseOutboxQueue;
use App\Modules\Shared\Infrastructure\Outbox\DatabaseOutboxStore;
use App\Modules\Shared\Infrastructure\Transaction\DatabaseTransactionManager;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        IdempotencyStore::class => DatabaseIdempotencyStore::class,
        OutboxStore::class => DatabaseOutboxStore::class,
        OutboxQueue::class => DatabaseOutboxQueue::class,
        TransactionManager::class => DatabaseTransactionManager::class,
    ];
}
