<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Transaction;

use App\Modules\Shared\Application\Port\TransactionManager;
use Closure;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseTransactionManager implements TransactionManager
{
    public function __construct(private ConnectionInterface $database) {}

    public function run(callable $operation): mixed
    {
        return $this->database->transaction(Closure::fromCallable($operation), 3);
    }
}
