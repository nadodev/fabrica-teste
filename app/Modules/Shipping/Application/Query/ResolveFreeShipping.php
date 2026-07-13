<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Application\Query;

use App\Modules\Shipping\Application\Port\ShippingSettingsRepository;

final readonly class ResolveFreeShipping
{
    public function __construct(private ShippingSettingsRepository $settings) {}

    /** @return array{serviceId: string, name: string, companyName: string, priceAmount: int, deliveryTime: int}|null */
    public function handle(int $merchandiseAmount): ?array
    {
        $configuration = $this->settings->configuration();
        if (! $configuration['freeShippingEnabled']) {
            return null;
        }

        $minimum = $this->moneyToCents($configuration['freeShippingMinimum']);
        if ($minimum === null || max(0, $merchandiseAmount) < $minimum) {
            return null;
        }

        return [
            'serviceId' => 'free-shipping',
            'name' => 'Frete gratis',
            'companyName' => 'Loja',
            'priceAmount' => 0,
            'deliveryTime' => max(0, $configuration['estimatedDays']),
        ];
    }

    private function moneyToCents(string $value): ?int
    {
        $value = str_replace(',', '.', trim($value));
        if ($value === '') {
            return 0;
        }
        if (preg_match('/^\d+(?:\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }

        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }
}
