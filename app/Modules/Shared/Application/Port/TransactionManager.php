<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Port;

interface TransactionManager
{
    /**
     * @template T
     *
     * @param  callable(): T  $operation
     * @return T
     */
    public function run(callable $operation): mixed;
}
