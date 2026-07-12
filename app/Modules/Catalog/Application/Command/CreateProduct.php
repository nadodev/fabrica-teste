<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command;

use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\Sku;
use App\Modules\Shared\Domain\ValueObject\Money;

final readonly class CreateProduct
{
    public function __construct(private ProductRepository $products) {}

    public function handle(string $id, string $sku, string $name, string $description, int $priceAmount, string $status, ?string $imageUrl): Product
    {
        $product = new Product(ProductId::fromString($id), new Sku($sku), $name, $description, new Money($priceAmount), ProductStatus::from($status), $imageUrl);
        $this->products->save($product);

        return $product;
    }
}
