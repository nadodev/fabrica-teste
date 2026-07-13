<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Http\Request;

use App\Support\StoreSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CheckoutRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $settings = app(StoreSettings::class);
        $customers = $settings->customers();
        $requiresDocument = (bool) ($customers['validateDocument'] ?? false)
            || (config('payment.gateway') === 'asaas' && $this->input('checkoutType') === 'payment');

        return [
            'customerName' => ['required', 'string', 'max:160'],
            'customerEmail' => ['required', 'email', 'max:160'],
            'customerPhone' => ['required', 'string', 'max:40'],
            'customerDocument' => [
                $requiresDocument ? 'required' : 'nullable',
                'string',
                'max:40',
                ...($requiresDocument ? ['regex:/^(?:\D*\d){11}(?:(?:\D*\d){3})?\D*$/'] : []),
            ],
            'shippingZip' => ['required', 'string', 'max:20'],
            'shippingAddress' => ['required', 'string', 'max:255'],
            'shippingNumber' => ['required', 'string', 'max:40'],
            'shippingCity' => ['required', 'string', 'max:120'],
            'shippingState' => ['required', 'string', 'max:40'],
            'checkoutType' => ['required', 'in:quote,payment'],
            'deliveryMethod' => ['required', 'in:shipping,pickup'],
            'paymentMethod' => ['required', Rule::in([...$settings->enabledPaymentMethods(), 'combine'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'privacyAccepted' => (bool) ($customers['privacyRequired'] ?? true) ? ['accepted'] : ['nullable', 'boolean'],
        ];
    }
}
