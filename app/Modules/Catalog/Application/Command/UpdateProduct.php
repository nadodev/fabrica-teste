<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command;

use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Shared\Domain\ValueObject\Money;
use DomainException;

final readonly class UpdateProduct
{
    public function __construct(private ProductRepository $products) {}

    /**
     * @param list<string> $galleryImages
     * @param list<array{name: string, values: list<string>}> $variations
     */
    public function handle(string $id, string $name, string $description, int $priceAmount, string $status, ?string $imageUrl, string $category = 'Uniformes', array $galleryImages = [], array $variations = []): Product
    {
        $product = $this->products->find(ProductId::fromString($id))
            ?? throw new DomainException('Product not found.');
        $product->updateDetails($name, $description, new Money($priceAmount), ProductStatus::from($status), $imageUrl, $category, $galleryImages, $variations);
        $this->products->save($product);

        return $product;
    }
}
