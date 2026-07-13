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
    public function all(): array
    {
        $products = ProductRecord::query()->orderByDesc('created_at')->get()
            ->map(fn (ProductRecord $record): Product => $this->toDomain($record))
            ->all();

        return array_values($products);
    }

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
            'category' => $product->category(),
            'price_amount' => $product->price()->amount,
            'price_currency' => $product->price()->currency,
            'status' => $product->status()->value,
            'image_url' => $product->imageUrl(),
            'gallery_images' => $product->galleryImages(),
            'variations' => $product->variations(),
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
            (string) ($record->getAttribute('category') ?? 'Uniformes'),
            $this->galleryImages($record->getAttribute('gallery_images')),
            $this->variations($record->getAttribute('variations')),
        );
    }

    /** @return list<string> */
    private function galleryImages(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_string(...)));
    }

    /** @return list<array{id?: string, name: string, value: string, sku?: string}> */
    private function variations(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $variations = [];
        foreach ($value as $variation) {
            if (! is_array($variation) || ! is_string($variation['name'] ?? null) || ! is_string($variation['value'] ?? null)) {
                continue;
            }
            $clean = ['name' => $variation['name'], 'value' => $variation['value']];
            if (is_string($variation['id'] ?? null)) {
                $clean['id'] = $variation['id'];
            }
            if (is_string($variation['sku'] ?? null)) {
                $clean['sku'] = $variation['sku'];
            }
            $variations[] = $clean;
        }

        return $variations;
    }
}
