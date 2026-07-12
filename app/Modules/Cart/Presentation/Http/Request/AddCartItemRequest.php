<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class AddCartItemRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'productId' => ['required', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
