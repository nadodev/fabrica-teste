<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

use App\Modules\Catalog\Domain\Product;
use App\Modules\Inventory\Application\DTO\StockLevel;

final readonly class ProductView
{
    /**
     * @param  list<string>  $galleryImages
     * @param  list<array<string, bool|int|string>>  $variations
     */
    public function __construct(
        public string $id,
        public string $sku,
        public string $name,
        public string $description,
        public string $category,
        public int $priceAmount,
        public string $priceCurrency,
        public ?string $imageUrl,
        public array $galleryImages,
        public array $variations,
        public string $status,
        public int $stockAvailable = 0,
        public bool $canSellWithoutStock = false,
        public bool $showStockAlerts = true,
        public int $weightGrams = 300,
        public int $widthCentimeters = 20,
        public int $heightCentimeters = 5,
        public int $lengthCentimeters = 30,
    ) {}

    /** @param list<StockLevel> $stockLevels */
    public static function fromDomain(Product $product, array $stockLevels = [], bool $allowOutOfStock = false, bool $showStockAlerts = true): self
    {
        $levels = [];
        $stockAvailable = 0;
        foreach ($stockLevels as $level) {
            $levels[$level->variationKey ?? ''] = $level;
            $stockAvailable += $level->available();
        }
        $variations = array_map(function (array $variation) use ($levels, $allowOutOfStock, $showStockAlerts): array {
            $level = $levels[$variation['id']] ?? null;
            $available = $level === null ? 0 : $level->available();
            $sku = $level === null ? $variation['sku'] : $level->sku;
            $threshold = $level === null ? 5 : $level->lowStockThreshold;

            return [
                ...$variation,
                'sku' => $sku,
                'stock' => $available,
                'lowStockThreshold' => $threshold,
                'purchasable' => $allowOutOfStock || $available > 0,
                'lowStock' => $showStockAlerts && $available <= $threshold,
            ];
        }, $product->variations());

        return new self(
            $product->id->value,
            $product->sku->value,
            $product->name(),
            $product->description(),
            $product->category(),
            $product->price()->amount,
            $product->price()->currency,
            $product->imageUrl(),
            $product->galleryImages(),
            $variations,
            $product->status()->value,
            max($stockAvailable, 0),
            $allowOutOfStock,
            $showStockAlerts,
            $product->shippingProfile()->weightGrams,
            $product->shippingProfile()->widthCentimeters,
            $product->shippingProfile()->heightCentimeters,
            $product->shippingProfile()->lengthCentimeters,
        );
    }
}
