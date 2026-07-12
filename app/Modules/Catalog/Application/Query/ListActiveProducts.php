<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query;

use App\Modules\Catalog\Application\DTO\ProductView;
use App\Modules\Catalog\Domain\Port\ProductRepository;

final readonly class ListActiveProducts
{
    public function __construct(private ProductRepository $products) {}

    /** @return list<ProductView> */
    public function handle(): array
    {
        return array_map(ProductView::fromDomain(...), $this->products->active());
    }
}
