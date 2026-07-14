<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command;

use App\Modules\Catalog\Application\Support\ProductVariationStock;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\ShippingProfile;
use App\Modules\Inventory\Application\Port\StockManager;
use App\Modules\Shared\Application\Port\TransactionManager;
use App\Modules\Shared\Domain\ValueObject\Money;
use DomainException;

final readonly class UpdateProduct
{
    public function __construct(private ProductRepository $products, private StockManager $stock, private TransactionManager $transactions) {}

    /**
     * @param  list<string>  $galleryImages
     * @param  list<array{id?: string, name: string, value: string, sku?: string, stock?: int, lowStockThreshold?: int}>  $variations
     */
    public function handle(string $id, string $name, string $description, int $priceAmount, string $status, ?string $imageUrl, string $category = 'Uniformes', array $galleryImages = [], array $variations = [], int $simpleStock = 0, int $weightGrams = 300, int $widthCentimeters = 20, int $heightCentimeters = 5, int $lengthCentimeters = 30): Product
    {
        return $this->transactions->run(function () use ($id, $name, $description, $priceAmount, $status, $imageUrl, $category, $galleryImages, $variations, $simpleStock, $weightGrams, $widthCentimeters, $heightCentimeters, $lengthCentimeters): Product {
            $product = $this->products->find(ProductId::fromString($id))
                ?? throw new DomainException('Product not found.');
            $prepared = ProductVariationStock::prepare($product->sku->value, $variations);
            $product->updateDetails($name, $description, new Money($priceAmount), ProductStatus::from($status), $imageUrl, $category, $galleryImages, $prepared['catalog'], new ShippingProfile($weightGrams, $widthCentimeters, $heightCentimeters, $lengthCentimeters));
            $this->products->save($product);
            $this->stock->synchronizeProduct('catalog-update-'.$id.'-'.hash('sha256', json_encode([$prepared['inventory'], $simpleStock], JSON_THROW_ON_ERROR)), $id, $product->sku->value, $simpleStock, $prepared['inventory']);

            return $product;
        });
    }
}
