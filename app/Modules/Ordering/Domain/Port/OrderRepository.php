<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Port;

interface OrderRepository
{
    public function nextIdentity(): string;

    public function save(object $order): void;
}
