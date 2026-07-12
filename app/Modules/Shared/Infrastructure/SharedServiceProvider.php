<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure;

use App\Modules\Shared\Application\Port\IdempotencyStore;
use App\Modules\Shared\Infrastructure\Idempotency\DatabaseIdempotencyStore;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [IdempotencyStore::class => DatabaseIdempotencyStore::class];
}
