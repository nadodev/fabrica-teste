<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Http\Request;

use App\Support\StoreSettings;
use Illuminate\Foundation\Http\FormRequest;

final class AddCartItemRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $products = app(StoreSettings::class)->products();
        $minimum = max(1, (int) ($products['minQuantity'] ?? 1));
        $maximum = max($minimum, (int) ($products['maxQuantity'] ?? 100));

        return [
            'productId' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', "min:{$minimum}", "max:{$maximum}"],
            'variationId' => ['nullable', 'string', 'max:40'],
        ];
    }
}
