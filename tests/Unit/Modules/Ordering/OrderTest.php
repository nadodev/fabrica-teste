<?php

use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderDetails;
use App\Modules\Ordering\Domain\OrderItem;
use App\Modules\Ordering\Domain\OrderStatus;
use App\Modules\Shared\Domain\ValueObject\Money;

function orderDetails(): OrderDetails
{
    return new OrderDetails(
        'payment', 'Cliente', 'cliente@example.com', '11999999999', null,
        '01001000', 'Rua Teste', '10', 'Sao Paulo', 'SP', 'pickup', null, null,
        new Money(0), null, 'pix', 'pending', null, null, new Money(0),
    );
}

it('creates an immutable-priced order awaiting payment', function () {
    $order = Order::place('order-1', 'PED-00000001', 'cart-1', [
        new OrderItem('product-1', 'SKU-1', 'Camisa', new Money(7990), 2),
    ], orderDetails());

    expect($order->status())->toBe(OrderStatus::AwaitingPayment)
        ->and($order->total()->amount)->toBe(15980);

    $order->markPaid();
    expect($order->status())->toBe(OrderStatus::Paid);
});

it('rejects orders without items', function () {
    Order::place('order-1', 'PED-00000001', 'cart-1', [], orderDetails());
})->throws(DomainException::class);
