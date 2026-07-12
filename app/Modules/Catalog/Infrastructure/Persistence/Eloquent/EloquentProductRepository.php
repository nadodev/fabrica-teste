<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\Sku;
use App\Modules\Shared\Domain\ValueObject\Money;

final class EloquentProductRepository implements ProductRepository
{
    public function active(): array
    {
        $products = ProductRecord::query()->where('status', ProductStatus::Active->value)->orderBy('name')->get()->map(fn (ProductRecord $record): Product => $this->toDomain($record))->all();

        return array_values($products);
    }

    public function find(ProductId $id): ?Product
    {
        $record = ProductRecord::query()->find($id->value);

        return $record === null ? null : $this->toDomain($record);
    }

    public function save(Product $product): void
    {
        ProductRecord::query()->updateOrCreate(['id' => $product->id->value], [
            'sku' => $product->sku->value,
            'name' => $product->name(),
            'description' => $product->description(),
            'price_amount' => $product->price()->amount,
            'price_currency' => $product->price()->currency,
            'status' => $product->status()->value,
            'image_url' => $product->imageUrl(),
        ]);
    }

    private function toDomain(ProductRecord $record): Product
    {
        return new Product(
            ProductId::fromString((string) $record->getKey()),
            new Sku((string) $record->getAttribute('sku')),
            (string) $record->getAttribute('name'),
            (string) $record->getAttribute('description'),
            new Money((int) $record->getAttribute('price_amount'), (string) $record->getAttribute('price_currency')),
            ProductStatus::from((string) $record->getAttribute('status')),
            $record->getAttribute('image_url'),
        );
    }
}
