<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'string', 'regex:/^\d{1,8}([,.]\d{1,2})?$/'],
            'status' => ['required', Rule::in(['draft', 'active'])],
            'imageUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=4000,max_height=4000'],
            'removeImage' => ['sometimes', 'boolean'],
        ];
    }

    public function priceInCents(): int
    {
        $normalized = str_replace(',', '.', (string) $this->validated('price'));
        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }
}
