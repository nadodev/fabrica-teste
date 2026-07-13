<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query;

use App\Modules\Catalog\Application\DTO\ProductView;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Inventory\Application\Port\StockGateway;
use App\Support\StoreSettings;

final readonly class ListActiveProducts
{
    public function __construct(private ProductRepository $products, private StockGateway $stock, private StoreSettings $settings) {}

    /** @return list<ProductView> */
    public function handle(): array
    {
        $allowOutOfStock = $this->settings->allowsOutOfStockSales();
        $showStockAlerts = $this->settings->lowStockWarningsEnabled();

        return array_map(
            fn ($product): ProductView => ProductView::fromDomain($product, $this->stock->levels($product->id->value), $allowOutOfStock, $showStockAlerts),
            $this->products->active(),
        );
    }
}
