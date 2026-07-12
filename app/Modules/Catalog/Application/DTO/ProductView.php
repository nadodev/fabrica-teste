<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

use App\Modules\Catalog\Domain\Product;

final readonly class ProductView
{
    /**
     * @param list<string> $galleryImages
     * @param list<array<string, bool|int|string>> $variations
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
    ) {}

    public static function fromDomain(Product $product, int $stockAvailable = 0): self
    {
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
            $product->variations(),
            $product->status()->value,
            $product->availableForDisplay($stockAvailable),
        );
    }
}
