<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command;

use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use DomainException;

final readonly class ArchiveProduct
{
    public function __construct(private ProductRepository $products) {}

    public function handle(string $id): void
    {
        $product = $this->products->find(ProductId::fromString($id))
            ?? throw new DomainException('Product not found.');
        $product->archive();
        $this->products->save($product);
    }
}
