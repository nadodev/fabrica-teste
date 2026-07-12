<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Port;

use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ValueObject\ProductId;

interface ProductRepository
{
    /** @return list<Product> */
    public function all(): array;

    /** @return list<Product> */
    public function active(): array;

    public function find(ProductId $id): ?Product;

    public function save(Product $product): void;
}
