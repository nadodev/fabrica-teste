<?php

use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderItem;
use App\Modules\Ordering\Domain\OrderStatus;
use App\Modules\Shared\Domain\ValueObject\Money;

it('creates an immutable-priced order awaiting payment', function () {
    $order = Order::place('order-1', 'PED-00000001', 'cart-1', [
        new OrderItem('product-1', 'SKU-1', 'Camisa', new Money(7990), 2),
    ]);

    expect($order->status())->toBe(OrderStatus::AwaitingPayment)
        ->and($order->total()->amount)->toBe(15980);

    $order->markPaid();
    expect($order->status())->toBe(OrderStatus::Paid);
});

it('rejects orders without items', function () {
    Order::place('order-1', 'PED-00000001', 'cart-1', []);
})->throws(DomainException::class);
