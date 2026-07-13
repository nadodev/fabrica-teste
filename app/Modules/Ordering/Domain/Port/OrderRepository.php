<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Domain\Port;

use App\Modules\Ordering\Domain\Order;

interface OrderRepository
{
    public function nextIdentity(): string;

    public function find(string $id): ?Order;

    public function findByNumber(string $number): ?Order;

    public function findByCartId(string $cartId): ?Order;

    public function save(Order $order): void;
}
