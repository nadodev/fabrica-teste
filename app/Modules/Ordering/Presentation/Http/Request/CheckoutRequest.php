<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'customerName' => ['required', 'string', 'max:160'],
            'customerEmail' => ['required', 'email', 'max:160'],
            'customerPhone' => ['required', 'string', 'max:40'],
            'customerDocument' => ['nullable', 'string', 'max:40'],
            'shippingZip' => ['required', 'string', 'max:20'],
            'shippingAddress' => ['required', 'string', 'max:255'],
            'shippingNumber' => ['required', 'string', 'max:40'],
            'shippingCity' => ['required', 'string', 'max:120'],
            'shippingState' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
