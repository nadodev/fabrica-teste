<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain;

use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\Sku;
use App\Modules\Shared\Domain\ValueObject\Money;
use InvalidArgumentException;

final class Product
{
    public function __construct(public readonly ProductId $id, public readonly Sku $sku, private string $name, private string $description, private Money $price, private ProductStatus $status, private ?string $imageUrl = null)
    {
        $this->rename($name);
    }

    public function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 160) {
            throw new InvalidArgumentException('Product name must contain between 1 and 160 characters.');
        }

        $this->name = $name;
    }

    public function updateDetails(string $name, string $description, Money $price, ProductStatus $status, ?string $imageUrl): void
    {
        if (mb_strlen($description) > 5000) {
            throw new InvalidArgumentException('Product description cannot exceed 5000 characters.');
        }

        $this->rename($name);
        $this->description = trim($description);
        $this->price = $price;
        $this->status = $status;
        $this->imageUrl = $imageUrl;
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
}
