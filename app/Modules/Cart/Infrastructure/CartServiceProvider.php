<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure;

use App\Modules\Cart\Application\Port\PostalAddressLookup;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Cart\Infrastructure\Address\ViaCepAddressLookup;
use App\Modules\Cart\Infrastructure\Persistence\DatabaseCartRepository;
use Illuminate\Support\ServiceProvider;

final class CartServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        CartRepository::class => DatabaseCartRepository::class,
        PostalAddressLookup::class => ViaCepAddressLookup::class,
    ];
}
