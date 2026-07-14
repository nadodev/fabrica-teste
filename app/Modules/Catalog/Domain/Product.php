<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain;

use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\ShippingProfile;
use App\Modules\Catalog\Domain\ValueObject\Sku;
use App\Modules\Shared\Domain\ValueObject\Money;
use InvalidArgumentException;

final class Product
{
    /** @var list<string> */
    private array $galleryImages;

    /** @var list<array{id: string, name: string, value: string, sku: string}> */
    private array $variations;

    /**
     * @param  list<string>  $galleryImages
     * @param  list<array{id?: string, name: string, value: string, sku?: string}>  $variations
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
        array $galleryImages = [],
        array $variations = [],
        private ?ShippingProfile $shippingProfile = null,
    ) {
        $this->rename($name);
        $this->category = $this->sanitizeCategory($category);
        $this->galleryImages = $this->sanitizeGallery($galleryImages);
        $this->variations = $this->sanitizeVariations($variations);
        $this->shippingProfile ??= new ShippingProfile(300, 20, 5, 30);
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
     * @param  list<string>  $galleryImages
     * @param  list<array{id?: string, name: string, value: string, sku?: string}>  $variations
     */
    public function updateDetails(string $name, string $description, Money $price, ProductStatus $status, ?string $imageUrl, string $category = 'Uniformes', array $galleryImages = [], array $variations = [], ?ShippingProfile $shippingProfile = null): void
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
        $this->shippingProfile = $shippingProfile ?? $this->shippingProfile;
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

    /** @return list<array{id: string, name: string, value: string, sku: string}> */
    public function variations(): array
    {
        return $this->variations;
    }

    public function shippingProfile(): ShippingProfile
    {
        return $this->shippingProfile ?? new ShippingProfile(300, 20, 5, 30);
    }

    public function variationLabel(?string $variationId): ?string
    {
        if ($this->variations === []) {
            return null;
        }

        $variation = $this->findVariation($variationId);

        return "{$variation['name']}: {$variation['value']}";
    }

    public function variationSku(?string $variationId): string
    {
        if ($this->variations === []) {
            return $this->sku->value;
        }

        return $this->findVariation($variationId)['sku'];
    }

    private function sanitizeCategory(string $category): string
    {
        $category = trim($category);

        return $category === '' ? 'Uniformes' : mb_substr($category, 0, 80);
    }

    /**
     * @param  list<string>  $galleryImages
     * @return list<string>
     */
    private function sanitizeGallery(array $galleryImages): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $url): string => mb_substr(trim((string) $url), 0, 2048),
            $galleryImages,
        ), fn (string $url): bool => $url !== ''));
    }

    /**
     * @param  list<array{id?: string, name: string, value: string, sku?: string}>  $variations
     * @return list<array{id: string, name: string, value: string, sku: string}>
     */
    private function sanitizeVariations(array $variations): array
    {
        $clean = [];

        foreach ($variations as $variation) {
            $name = mb_substr(trim($variation['name']), 0, 40);
            $value = mb_substr(trim($variation['value']), 0, 60);

            if ($name !== '' && $value !== '') {
                $id = trim((string) ($variation['id'] ?? ''));
                $id = $id === '' ? substr(hash('sha256', $name.':'.$value), 0, 16) : mb_substr($id, 0, 40);
                $clean[] = [
                    'id' => $id,
                    'name' => $name,
                    'value' => $value,
                    'sku' => mb_substr(trim((string) ($variation['sku'] ?? '')), 0, 64),
                ];
            }
        }

        return $clean;
    }

    /** @return array{id: string, name: string, value: string, sku: string} */
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
