<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Infrastructure\Persistence;

use App\Modules\Shipping\Application\Port\ShippingSettingsRepository;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseShippingSettingsRepository implements ShippingSettingsRepository
{
    public function __construct(private ConnectionInterface $database) {}

    public function configuration(): array
    {
        $value = $this->database->table('shipping_settings')->where('id', 1)->value('options');
        $options = json_decode((string) ($value ?? '[]'), true);
        $options = is_array($options) ? $options : [];

        return [
            'freeShippingEnabled' => (bool) ($options['freeShippingEnabled'] ?? false),
            'freeShippingMinimum' => trim((string) ($options['freeShippingMinimum'] ?? '')),
            'estimatedDays' => max(0, (int) ($options['estimatedDays'] ?? 0)),
        ];
    }
}
