<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain;

use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\Sku;
use App\Modules\Shared\Domain\ValueObject\Money;
use InvalidArgumentException;

final class Product
{
    /**
     * @param list<string> $galleryImages
     * @param list<array{id?: string, name: string, value: string, stock: int, lowStockThreshold: int}> $variations
     */
    public function __construct(
        public readonly ProductId $id,
        public readonly Sku $sku,
        private string $name,
        private string $description,
        private Money $price,
        private ProductStatus $status,
        private ?string $imageUrl = null,
        private string $category = 'Uniformes',
        private array $galleryImages = [],
        private array $variations = [],
    )
    {
        $this->rename($name);
        $this->category = $this->sanitizeCategory($category);
        $this->galleryImages = $this->sanitizeGallery($galleryImages);
        $this->variations = $this->sanitizeVariations($variations);
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 160) {
            throw new InvalidArgumentException('Product name must contain between 1 and 160 characters.');
        }

        $this->name = $name;
    }

    /**
     * @param list<string> $galleryImages
     * @param list<array{id?: string, name: string, value: string, stock: int, lowStockThreshold: int}> $variations
     */
    public function updateDetails(string $name, string $description, Money $price, ProductStatus $status, ?string $imageUrl, string $category = 'Uniformes', array $galleryImages = [], array $variations = []): void
    {
        if (mb_strlen($description) > 5000) {
            throw new InvalidArgumentException('Product description cannot exceed 5000 characters.');
        }

        $this->rename($name);
        $this->description = trim($description);
        $this->price = $price;
        $this->status = $status;
        $this->imageUrl = $imageUrl;
        $this->category = $this->sanitizeCategory($category);
        $this->galleryImages = $this->sanitizeGallery($galleryImages);
        $this->variations = $this->sanitizeVariations($variations);
    }

    public function activate(): void
    {
        $this->status = ProductStatus::Active;
    }

    public function archive(): void
    {
        $this->status = ProductStatus::Archived;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function status(): ProductStatus
    {
        return $this->status;
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function category(): string
    {
        return $this->category;
    }

    /** @return list<string> */
    public function galleryImages(): array
    {
        return $this->galleryImages;
    }

    /** @return list<array{id: string, name: string, value: string, stock: int, lowStockThreshold: int, purchasable: bool, lowStock: bool}> */
    public function variations(): array
    {
        return $this->variations;
    }

    public function variationLabel(?string $variationId): ?string
    {
        if ($this->variations === []) {
            return null;
        }

        $variation = $this->findVariation($variationId);

        if (! $variation['purchasable']) {
            throw new InvalidArgumentException('Variation is not available for purchase.');
        }

        return "{$variation['name']}: {$variation['value']}";
    }

    public function variationStock(?string $variationId): ?int
    {
        if ($this->variations === []) {
            return null;
        }

        return $this->findVariation($variationId)['stock'];
    }

    public function availableForDisplay(int $productStock = 0): int
    {
        if ($this->variations === []) {
            return $productStock;
        }

        return array_sum(array_map(
            fn (array $variation): int => $variation['purchasable'] ? $variation['stock'] : 0,
            $this->variations,
        ));
    }

    private function sanitizeCategory(string $category): string
    {
        $category = trim($category);

        return $category === '' ? 'Uniformes' : mb_substr($category, 0, 80);
    }

    /** @param list<string> $galleryImages @return list<string> */
    private function sanitizeGallery(array $galleryImages): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $url): string => mb_substr(trim((string) $url), 0, 2048),
            $galleryImages,
        ), fn (string $url): bool => $url !== ''));
    }

    /**
     * @param list<array{id?: string, name: string, value: string, stock: int, lowStockThreshold: int}> $variations
     * @return list<array{id: string, name: string, value: string, stock: int, lowStockThreshold: int, purchasable: bool, lowStock: bool}>
     */
    private function sanitizeVariations(array $variations): array
    {
        $clean = [];

        foreach ($variations as $variation) {
            $name = mb_substr(trim((string) ($variation['name'] ?? '')), 0, 40);
            $value = mb_substr(trim((string) ($variation['value'] ?? '')), 0, 60);
            $stock = max(0, (int) ($variation['stock'] ?? 0));
            $lowStockThreshold = max(0, (int) ($variation['lowStockThreshold'] ?? 5));

            if ($name !== '' && $value !== '') {
                $id = trim((string) ($variation['id'] ?? ''));
                $id = $id === '' ? substr(hash('sha256', $name.':'.$value), 0, 16) : mb_substr($id, 0, 40);
                $lowStock = $stock <= $lowStockThreshold;
                $clean[] = [
                    'id' => $id,
                    'name' => $name,
                    'value' => $value,
                    'stock' => $stock,
                    'lowStockThreshold' => $lowStockThreshold,
                    'purchasable' => $stock > $lowStockThreshold,
                    'lowStock' => $lowStock,
                ];
            }
        }

        return $clean;
    }

    /** @return array{id: string, name: string, value: string, stock: int, lowStockThreshold: int, purchasable: bool, lowStock: bool} */
    private function findVariation(?string $variationId): array
    {
        $variationId = trim((string) $variationId);

        foreach ($this->variations as $variation) {
            if ($variation['id'] === $variationId) {
                return $variation;
            }
        }

        throw new InvalidArgumentException('Variation option is required.');
    }
}
