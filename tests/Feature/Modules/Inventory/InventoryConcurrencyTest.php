<?php

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use App\Modules\Inventory\Application\Port\StockGateway;
use App\Modules\Inventory\Domain\Exception\InsufficientStock;
use App\Modules\Inventory\Infrastructure\Persistence\DatabaseStockGateway;

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
        ->and(DB::table('inventory_movements')->count())->toBe(1)
        ->and(DB::table('inventory_reservations')->count())->toBe(1);

    $stock->release('0190f566-c399-79e3-a553-7e5fb8d83431');
    $stock->release('0190f566-c399-79e3-a553-7e5fb8d83431');

    expect($stock->available($productId))->toBe(5);
});

it('never reserves more units than are available', function () {
    $stock = app(StockGateway::class);
    $productId = '0190f566-c399-79e3-a553-7e5fb8d83430';
    app(DatabaseStockGateway::class)->receive('invoice-002', $productId, 2);

    $stock->reserve('0190f566-c399-79e3-a553-7e5fb8d83432', $productId, 3);
})->throws(InsufficientStock::class);
