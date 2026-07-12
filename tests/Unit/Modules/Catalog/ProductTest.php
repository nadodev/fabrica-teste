<?php

use App\Modules\Catalog\Domain\Product;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Catalog\Domain\ValueObject\Sku;
use App\Modules\Shared\Domain\ValueObject\Money;

it('protects product lifecycle inside the domain', function () {
    $product = new Product(
        ProductId::fromString('0190f566-c399-79e3-a553-7e5fb8d83419'),
        new Sku(' polo-001 '),
        'Camisa Polo',
        'Uniforme empresarial',
        new Money(7990),
        ProductStatus::Draft,
    );

    $product->activate();

    expect($product->sku->value)->toBe('POLO-001')
        ->and($product->status())->toBe(ProductStatus::Active);
});
