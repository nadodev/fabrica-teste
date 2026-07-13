<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Support;

final class ProductVariationStock
{
    /**
     * @param  list<array{id?: string, name: string, value: string, sku?: string, stock?: int, lowStockThreshold?: int}>  $variations
     * @return array{catalog: list<array{id: string, name: string, value: string, sku: string}>, inventory: list<array{id: string, sku: string, stock: int, lowStockThreshold: int}>}
     */
    public static function prepare(string $baseSku, array $variations): array
    {
        $catalog = [];
        $inventory = [];

        foreach ($variations as $variation) {
            $name = mb_substr(trim($variation['name']), 0, 40);
            $value = mb_substr(trim($variation['value']), 0, 60);
            if ($name === '' || $value === '') {
                continue;
            }

            $id = trim((string) ($variation['id'] ?? ''));
            $id = $id === '' ? substr(hash('sha256', $name.':'.$value), 0, 16) : mb_substr($id, 0, 40);
            $sku = trim((string) ($variation['sku'] ?? ''));
            $sku = $sku === ''
                ? mb_substr($baseSku, 0, 47).'-'.substr(hash('sha256', $id), 0, 16)
                : mb_substr($sku, 0, 64);

            $catalog[] = ['id' => $id, 'name' => $name, 'value' => $value, 'sku' => $sku];
            $inventory[] = [
                'id' => $id,
                'sku' => $sku,
                'stock' => max(0, (int) ($variation['stock'] ?? 0)),
                'lowStockThreshold' => max(0, (int) ($variation['lowStockThreshold'] ?? 5)),
            ];
        }

        return ['catalog' => $catalog, 'inventory' => $inventory];
    }
}
