<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

use App\Modules\Catalog\Domain\Product;

final readonly class ProductView
{
    public function __construct(public string $id, public string $sku, public string $name, public string $description, public int $priceAmount, public string $priceCurrency, public ?string $imageUrl, public string $status) {}

    public static function fromDomain(Product $product): self
    {
        return new self($product->id->value, $product->sku->value, $product->name(), $product->description(), $product->price()->amount, $product->price()->currency, $product->imageUrl(), $product->status()->value);
    }
}
