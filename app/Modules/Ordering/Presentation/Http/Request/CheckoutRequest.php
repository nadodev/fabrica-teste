<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Http\Request;

use App\Support\StoreSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CheckoutRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $settings = app(StoreSettings::class);
        $customers = $settings->customers();
        $requiresDocument = (bool) ($customers['validateDocument'] ?? false)
            || (config('payment.gateway') === 'asaas' && $this->input('checkoutType') === 'payment');
        $requiresCard = fn (): bool => $this->input('checkoutType') === 'payment'
            && $this->input('paymentMethod') === 'credit_card';

        return [
            'customerName' => ['required', 'string', 'max:160'],
            'customerEmail' => ['required', 'email', 'max:160'],
            'customerPhone' => ['required', 'string', 'max:20', 'regex:/^(?:\D*\d){10,11}\D*$/'],
            'customerDocument' => [
                $requiresDocument ? 'required' : 'nullable',
                'string',
                'max:40',
                ...($requiresDocument ? ['regex:/^(?:\D*\d){11}(?:(?:\D*\d){3})?\D*$/'] : []),
            ],
            'shippingZip' => ['required', 'string', 'regex:/^(?:\D*\d){8}\D*$/'],
            'shippingAddress' => ['required', 'string', 'max:255'],
            'shippingNumber' => ['required', 'string', 'max:40'],
            'shippingCity' => ['required', 'string', 'max:120'],
            'shippingState' => ['required', 'string', 'size:2'],
            'checkoutType' => ['required', 'in:quote,payment'],
            'deliveryMethod' => ['required', 'in:shipping,pickup'],
            'paymentMethod' => ['required', Rule::in([...$settings->enabledPaymentMethods(), 'combine'])],
            'cardHolderName' => [Rule::requiredIf($requiresCard), 'nullable', 'string', 'min:2', 'max:100'],
            'cardNumber' => [Rule::requiredIf($requiresCard), 'nullable', 'string', 'regex:/^\d{13,19}$/'],
            'cardExpiryMonth' => [Rule::requiredIf($requiresCard), 'nullable', 'string', 'digits:2', 'regex:/^(0[1-9]|1[0-2])$/'],
            'cardExpiryYear' => [Rule::requiredIf($requiresCard), 'nullable', 'integer', 'digits:4', 'between:'.now()->year.','.now()->addYears(20)->year],
            'cardCcv' => [Rule::requiredIf($requiresCard), 'nullable', 'string', 'regex:/^\d{3,4}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'privacyAccepted' => (bool) ($customers['privacyRequired'] ?? true) ? ['accepted'] : ['nullable', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('checkoutType') !== 'payment' || $this->input('paymentMethod') !== 'credit_card') {
                return;
            }

            $number = (string) $this->input('cardNumber', '');
            if (preg_match('/^\d{13,19}$/', $number) === 1 && ! $this->passesLuhn($number)) {
                $validator->errors()->add('cardNumber', 'Informe um numero de cartao valido.');
            }

            $month = (int) $this->input('cardExpiryMonth', 0);
            $year = (int) $this->input('cardExpiryYear', 0);
            if ($year > 0 && $month > 0 && ($year < now()->year || ($year === now()->year && $month < now()->month))) {
                $validator->errors()->add('cardExpiryMonth', 'O cartao esta vencido.');
            }
        }];
    }

    private function passesLuhn(string $number): bool
    {
        $sum = 0;
        $alternate = false;
        for ($index = strlen($number) - 1; $index >= 0; $index--) {
            $digit = (int) $number[$index];
            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $alternate = ! $alternate;
        }

        return $sum > 0 && $sum % 10 === 0;
    }
}
