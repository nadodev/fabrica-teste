<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Application\Query;

use App\Modules\Cart\Application\DTO\CartView;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Shipping\Application\DTO\ShippingOption;
use App\Modules\Shipping\Application\DTO\ShippingQuoteRequest;
use App\Modules\Shipping\Application\Port\ShippingQuoteGateway;
use DomainException;

final readonly class QuoteCartShipping
{
    public function __construct(private ProductRepository $products, private ShippingQuoteGateway $gateway) {}

    /** @return list<ShippingOption> */
    public function handle(string $postalCode, CartView $cart): array
    {
        $items = array_map(function (array $item): array {
            $product = $this->products->find(ProductId::fromString((string) $item['productId']))
                ?? throw new DomainException('Um produto do carrinho nao esta mais disponivel para calcular o frete.');
            $profile = $product->shippingProfile();

            return [
                'productId' => (string) $item['productId'],
                'cartItemKey' => (string) $item['cartItemKey'],
                'quantity' => (int) $item['quantity'],
                'unitPriceAmount' => (int) $item['unitPriceAmount'],
                'weightInGrams' => $profile->weightGrams,
                'widthInCentimeters' => $profile->widthCentimeters,
                'heightInCentimeters' => $profile->heightCentimeters,
                'lengthInCentimeters' => $profile->lengthCentimeters,
            ];
        }, $cart->items);

        return $this->gateway->quote(new ShippingQuoteRequest($postalCode, $items));
    }

    /** @return array{serviceId: string, name: string, companyName: string, priceAmount: int, deliveryTime: int} */
    public function revalidate(string $postalCode, CartView $cart, string $serviceCode): array
    {
        foreach ($this->handle($postalCode, $cart) as $option) {
            if ($option->serviceCode === $serviceCode) {
                return $this->toArray($option);
            }
        }

        throw new DomainException('A opcao de frete selecionada nao esta mais disponivel. Calcule o frete novamente.');
    }

    /** @return array{serviceId: string, name: string, companyName: string, priceAmount: int, deliveryTime: int} */
    public function toArray(ShippingOption $option): array
    {
        return [
            'serviceId' => $option->serviceCode,
            'name' => $option->name,
            'companyName' => $option->companyName,
            'priceAmount' => $option->priceAmount,
            'deliveryTime' => $option->estimatedDays,
        ];
    }
}
