<?php

use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderItem;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Shared\Domain\ValueObject\Money;
use Illuminate\Support\Facades\DB;

it('generates sequential public numbers and persists item snapshots', function () {
    DB::table('cart_carts')->insert([
        'id' => '0190f566-c399-79e3-a553-7e5fb8d83450',
        'token_hash' => hash('sha256', 'order-cart'),
        'currency' => 'BRL',
        'status' => 'active',
        'version' => 1,
        'expires_at' => now()->addDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $repository = app(OrderRepository::class);
    $number = $repository->nextIdentity();
    $order = Order::place('0190f566-c399-79e3-a553-7e5fb8d83451', $number, '0190f566-c399-79e3-a553-7e5fb8d83450', [
        new OrderItem('0190f566-c399-79e3-a553-7e5fb8d83440', 'SNAP-001', 'Nome no momento da compra', new Money(10990), 2),
    ]);
    $repository->save($order);

    expect($number)->toBe('PED-00000001')
        ->and($repository->nextIdentity())->toBe('PED-00000002')
        ->and($repository->find($order->id)?->total()->amount)->toBe(21980);
    $this->assertDatabaseHas('ordering_order_items', ['name' => 'Nome no momento da compra', 'unit_price_amount' => 10990]);
});
