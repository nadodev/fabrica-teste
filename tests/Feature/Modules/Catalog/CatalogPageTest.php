<?php

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductRecord;
use Inertia\Testing\AssertableInertia as Assert;

it('lists active products through the catalog adapter', function () {
    ProductRecord::query()->create([
        'id' => '0190f566-c399-79e3-a553-7e5fb8d83419',
        'sku' => 'POLO-001',
        'name' => 'Camisa Polo',
        'description' => 'Uniforme empresarial',
        'price_amount' => 7990,
        'price_currency' => 'BRL',
        'status' => 'active',
    ]);

    $this->get(route('produtos'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('produtos')
            ->has('products', 1)
            ->where('products.0.sku', 'POLO-001')
            ->where('products.0.priceAmount', 7990));
});
