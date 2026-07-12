<?php

use App\Modules\Shared\Domain\ValueObject\Money;

it('calculates monetary values without floating point', function () {
    $unitPrice = new Money(6990, 'BRL');

    expect($unitPrice->multiply(3))
        ->amount->toBe(20970)
        ->currency->toBe('BRL');
});

it('rejects negative monetary values', function () {
    new Money(-1, 'BRL');
})->throws(InvalidArgumentException::class);
