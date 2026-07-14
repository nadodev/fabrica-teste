<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command;

use App\Modules\Catalog\Application\Support\ProductVariationStock;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\ShippingProfile;
use App\Modules\Catalog\Domain\ValueObject\Sku;
use App\Modules\Inventory\Application\Port\StockManager;
use App\Modules\Shared\Application\Port\TransactionManager;
use App\Modules\Shared\Domain\ValueObject\Money;

final readonly class CreateProduct
{
    public function __construct(private ProductRepository $products, private StockManager $stock, private TransactionManager $transactions) {}

    /**
     * @param  list<string>  $galleryImages
     * @param  list<array{id?: string, name: string, value: string, sku?: string, stock?: int, lowStockThreshold?: int}>  $variations
     */
    public function handle(string $id, string $sku, string $name, string $description, int $priceAmount, string $status, ?string $imageUrl, string $category = 'Uniformes', array $galleryImages = [], array $variations = [], int $simpleStock = 0, int $weightGrams = 300, int $widthCentimeters = 20, int $heightCentimeters = 5, int $lengthCentimeters = 30): Product
    {
        return $this->transactions->run(function () use ($id, $sku, $name, $description, $priceAmount, $status, $imageUrl, $category, $galleryImages, $variations, $simpleStock, $weightGrams, $widthCentimeters, $heightCentimeters, $lengthCentimeters): Product {
            $prepared = ProductVariationStock::prepare($sku, $variations);
            $product = new Product(ProductId::fromString($id), new Sku($sku), $name, $description, new Money($priceAmount), ProductStatus::from($status), $imageUrl, $category, $galleryImages, $prepared['catalog'], new ShippingProfile($weightGrams, $widthCentimeters, $heightCentimeters, $lengthCentimeters));
            $this->products->save($product);
            $this->stock->synchronizeProduct('catalog-create-'.$id, $id, $sku, $simpleStock, $prepared['inventory']);

            return $product;
        });
    }
}
