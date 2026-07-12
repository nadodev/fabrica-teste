<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query;

use App\Modules\Catalog\Application\DTO\ProductView;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Inventory\Application\Port\StockGateway;

final readonly class ListActiveProducts
{
    public function __construct(private ProductRepository $products, private StockGateway $stock) {}

    /** @return list<ProductView> */
    public function handle(): array
    {
        return array_map(
            fn ($product): ProductView => ProductView::fromDomain($product, $this->stock->available($product->id->value)),
            $this->products->active(),
        );
    }
}
