<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence;

use App\Modules\Inventory\Application\DTO\InventoryDashboard;
use App\Modules\Inventory\Application\Port\InventoryReadModel;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseInventoryReadModel implements InventoryReadModel
{
    public function __construct(private ConnectionInterface $database) {}

    public function dashboard(): InventoryDashboard
    {
        $stocks = $this->database->table('inventory_stock_levels')
            ->join('catalog_products', 'catalog_products.id', '=', 'inventory_stock_levels.product_id')
            ->select(
                'inventory_stock_levels.id',
                'catalog_products.name',
                'inventory_stock_levels.sku',
                'inventory_stock_levels.variation_key',
                'inventory_stock_levels.on_hand',
                'inventory_stock_levels.reserved',
                'inventory_stock_levels.low_stock_threshold',
                'inventory_stock_levels.updated_at',
            )
            ->orderBy('catalog_products.name')
            ->orderBy('inventory_stock_levels.variation_key')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
                'sku' => (string) $row->sku,
                'variationKey' => $row->variation_key === null ? null : (string) $row->variation_key,
                'onHand' => (int) $row->on_hand,
                'reserved' => (int) $row->reserved,
                'available' => max(0, (int) $row->on_hand - (int) $row->reserved),
                'lowStockThreshold' => (int) $row->low_stock_threshold,
                'updatedAt' => $row->updated_at === null ? null : (string) $row->updated_at,
            ])->values()->all();

        $movements = $this->database->table('inventory_movements')
            ->join('catalog_products', 'catalog_products.id', '=', 'inventory_movements.product_id')
            ->select('inventory_movements.*', 'catalog_products.name')
            ->orderByDesc('inventory_movements.created_at')
            ->limit(50)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'sku' => (string) $row->sku,
                'variationKey' => $row->variation_key === null ? null : (string) $row->variation_key,
                'type' => (string) $row->type,
                'quantity' => (int) $row->quantity,
                'reservedDelta' => (int) $row->reserved_delta,
                'balanceAfter' => (int) $row->balance_after,
                'reservedAfter' => $row->reserved_after === null ? null : (int) $row->reserved_after,
                'reference' => (string) $row->reference,
                'createdAt' => (string) $row->created_at,
            ])->values()->all();

        return new InventoryDashboard(array_values($stocks), array_values($movements));
    }
}
