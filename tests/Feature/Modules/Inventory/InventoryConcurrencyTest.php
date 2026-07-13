<?php

use App\Modules\Catalog\Application\Command\CreateProduct;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Inventory\Application\Command\ConfirmStockReservation;
use App\Modules\Inventory\Application\Command\ExpireStockReservations;
use App\Modules\Inventory\Application\Command\ReleaseStockReservation;
use App\Modules\Inventory\Application\Port\StockGateway;
use App\Modules\Inventory\Application\Port\StockReservationLifecycle;
use App\Modules\Inventory\Domain\Exception\InsufficientStock;
use App\Modules\Inventory\Domain\Exception\ReservationConflict;
use App\Modules\Inventory\Infrastructure\Persistence\DatabaseStockGateway;
use Illuminate\Support\Str;

beforeEach(function () {
    ProductRecord::query()->create([
        'id' => '0190f566-c399-79e3-a553-7e5fb8d83430',
        'sku' => 'STOCK-001',
        'name' => 'Produto com estoque',
        'description' => '',
        'price_amount' => 1000,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);
});

it('keeps stock receipt and reservation idempotent', function () {
    $stock = app(DatabaseStockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';

    $stock->receive('invoice-001', $productId, 5);
    $stock->receive('invoice-001', $productId, 5);
    $stock->reserve('0190f566-c399-79e3-a553-7e5fb8d83431', $productId, 4);
    $stock->reserve('0190f566-c399-79e3-a553-7e5fb8d83431', $productId, 4);

    expect($stock->available($productId))->toBe(1)
        ->and(DB::table('inventory_movements')->count())->toBe(2)
        ->and(DB::table('inventory_reservations')->count())->toBe(1);

    app(ReleaseStockReservation::class)->handle('0190f566-c399-79e3-a553-7e5fb8d83431');
    app(ReleaseStockReservation::class)->handle('0190f566-c399-79e3-a553-7e5fb8d83431');

    expect($stock->available($productId))->toBe(5);
});

it('confirms an active reservation once and consumes physical stock', function () {
    $stock = app(DatabaseStockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';
    $reservationId = '0190f566-c399-79e3-a553-7e5fb8d83433';
    $stock->receive('invoice-confirm-001', $productId, 5);
    $stock->reserve($reservationId, $productId, 4);

    app(ConfirmStockReservation::class)->handle($reservationId);
    app(ConfirmStockReservation::class)->handle($reservationId);

    expect($stock->available($productId))->toBe(1);
    $this->assertDatabaseHas('inventory_stock_levels', [
        'product_id' => $productId,
        'on_hand' => 1,
        'reserved' => 0,
    ]);
    $this->assertDatabaseHas('inventory_reservations', ['id' => $reservationId, 'status' => 'confirmed']);
    $this->assertDatabaseHas('inventory_movements', [
        'reservation_id' => $reservationId,
        'type' => 'reservation_confirmed',
        'quantity' => -4,
        'reserved_delta' => -4,
        'balance_after' => 1,
        'reserved_after' => 0,
    ]);
    expect(DB::table('inventory_movements')->where('type', 'reservation_confirmed')->count())->toBe(1);
});

it('expires due reservations in bounded idempotent batches', function () {
    $stock = app(DatabaseStockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';
    $first = '0190f566-c399-79e3-a553-7e5fb8d83434';
    $second = '0190f566-c399-79e3-a553-7e5fb8d83435';
    $stock->receive('invoice-expire-001', $productId, 5);
    $stock->reserve($first, $productId, 2);
    $stock->reserve($second, $productId, 2);
    DB::table('inventory_reservations')->whereIn('id', [$first, $second])->update(['expires_at' => now()->subMinute()]);

    $expirer = app(ExpireStockReservations::class);
    expect($expirer->handle(1))->toBe(1)
        ->and($expirer->handle(1))->toBe(1)
        ->and($expirer->handle(1))->toBe(0)
        ->and($stock->available($productId))->toBe(5)
        ->and(DB::table('inventory_reservations')->where('status', 'expired')->count())->toBe(2)
        ->and(DB::table('inventory_movements')->where('type', 'reservation_expired')->count())->toBe(2);
});

it('expires a due reservation instead of confirming it', function () {
    $stock = app(DatabaseStockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';
    $reservationId = '0190f566-c399-79e3-a553-7e5fb8d83436';
    $stock->receive('invoice-expired-confirm-001', $productId, 3);
    $stock->reserve($reservationId, $productId, 2);
    DB::table('inventory_reservations')->where('id', $reservationId)->update(['expires_at' => now()->subSecond()]);

    expect(fn () => app(StockReservationLifecycle::class)->confirm($reservationId))
        ->toThrow(ReservationConflict::class);

    $this->assertDatabaseHas('inventory_reservations', ['id' => $reservationId, 'status' => 'expired']);
    $this->assertDatabaseHas('inventory_stock_levels', [
        'product_id' => $productId,
        'on_hand' => 3,
        'reserved' => 0,
    ]);
});

it('runs expiration through the scheduled console entry point', function () {
    $stock = app(DatabaseStockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';
    $reservationId = '0190f566-c399-79e3-a553-7e5fb8d83437';
    $stock->receive('invoice-console-expire-001', $productId, 2);
    $stock->reserve($reservationId, $productId, 1);
    DB::table('inventory_reservations')->where('id', $reservationId)->update(['expires_at' => now()->subMinute()]);

    $this->artisan('inventory:expire-reservations', ['--limit' => 10])
        ->expectsOutput('1 reserva(s) de estoque expirada(s).')
        ->assertSuccessful();

    $this->assertDatabaseHas('inventory_reservations', ['id' => $reservationId, 'status' => 'expired']);
});

it('never reserves more units than are available', function () {
    $stock = app(StockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';
    app(DatabaseStockGateway::class)->receive('invoice-002', $productId, 2);

    $stock->reserve('0190f566-c399-79e3-a553-7e5fb8d83432', $productId, 3);
})->throws(InsufficientStock::class);

it('keeps independent balances and reservations for each variation sku', function () {
    $stock = app(DatabaseStockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';
    $stock->synchronizeProduct('variation-sync-001', $productId, 'STOCK-001', 0, [
        ['id' => 'size-m', 'sku' => 'STOCK-001-M', 'stock' => 5, 'lowStockThreshold' => 2],
        ['id' => 'size-g', 'sku' => 'STOCK-001-G', 'stock' => 3, 'lowStockThreshold' => 1],
    ]);

    $stock->reserve((string) Str::uuid(), $productId, 4, 'size-m');

    expect($stock->available($productId, 'size-m'))->toBe(1)
        ->and($stock->available($productId, 'size-g'))->toBe(3)
        ->and($stock->available($productId))->toBe(4)
        ->and($stock->levels($productId))->toHaveCount(2);
    $this->assertDatabaseHas('inventory_stock_levels', [
        'product_id' => $productId,
        'variation_key' => 'size-m',
        'sku' => 'STOCK-001-M',
        'on_hand' => 5,
        'reserved' => 4,
    ]);
});

it('stores variation metadata without duplicating stock in catalog json', function () {
    $productId = (string) Str::uuid();
    app(CreateProduct::class)->handle(
        $productId,
        'UNIFIED-001',
        'Produto por variacao',
        '',
        5000,
        'active',
        null,
        'Uniformes',
        [],
        [
            ['id' => 'blue-m', 'name' => 'Cor/Tamanho', 'value' => 'Azul M', 'sku' => 'UNIFIED-001-BLUE-M', 'stock' => 7, 'lowStockThreshold' => 2],
        ],
    );

    $variations = ProductRecord::query()->findOrFail($productId)->getAttribute('variations');
    expect($variations)->toBeArray()
        ->and($variations[0])->toMatchArray([
            'id' => 'blue-m',
            'name' => 'Cor/Tamanho',
            'value' => 'Azul M',
            'sku' => 'UNIFIED-001-BLUE-M',
        ])
        ->and($variations[0])->not->toHaveKeys(['stock', 'lowStockThreshold', 'purchasable', 'lowStock']);
    $this->assertDatabaseHas('inventory_stock_levels', [
        'product_id' => $productId,
        'variation_key' => 'blue-m',
        'sku' => 'UNIFIED-001-BLUE-M',
        'on_hand' => 7,
        'low_stock_threshold' => 2,
    ]);
});
