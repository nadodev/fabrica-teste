<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query;

use App\Modules\Catalog\Application\DTO\ProductView;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Inventory\Application\Port\StockGateway;

final readonly class FindProduct
{
    public function __construct(private ProductRepository $products, private StockGateway $stock) {}

    public function handle(string $id): ?ProductView
    {
        $product = $this->products->find(ProductId::fromString($id));

        return $product === null ? null : ProductView::fromDomain($product, $this->stock->available($product->id->value));
    }
}
