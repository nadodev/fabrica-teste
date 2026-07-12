<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', Rule::unique('catalog_products', 'sku')],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'price' => ['required', 'string', 'regex:/^\d{1,8}([,.]\d{1,2})?$/'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::in(['draft', 'active'])],
            'imageUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'galleryImages' => ['nullable', 'array', 'max:8'],
            'galleryImages.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=4000,max_height=4000'],
            'variations' => ['nullable', 'array', 'max:50'],
            'variations.*.id' => ['nullable', 'string', 'max:40'],
            'variations.*.name' => ['nullable', 'string', 'max:40'],
            'variations.*.value' => ['nullable', 'string', 'max:60'],
            'variations.*.stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variations.*.lowStockThreshold' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }

    public function priceInCents(): int
    {
        $normalized = str_replace(',', '.', (string) $this->validated('price'));
        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
    }

    /** @return list<string> */
    /** @return list<array{id?: string, name: string, value: string, stock: int, lowStockThreshold: int}> */
    public function variations(): array
    {
        $variations = [];

        foreach ((array) $this->validated('variations', []) as $variation) {
            $name = trim((string) ($variation['name'] ?? ''));
            $value = trim((string) ($variation['value'] ?? ''));

            if ($name !== '' && $value !== '') {
                $variations[] = [
                    'id' => (string) ($variation['id'] ?? ''),
                    'name' => $name,
                    'value' => $value,
                    'stock' => (int) ($variation['stock'] ?? 0),
                    'lowStockThreshold' => (int) ($variation['lowStockThreshold'] ?? 5),
                ];
            }
        }

        return $variations;
    }
}
