<?php

declare(strict_types=1);

namespace App\Modules\Payment\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class AsaasWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expected = (string) config('payment.asaas.webhook_token');
        $received = (string) $this->header('asaas-access-token', '');

        return mb_strlen($expected) >= 32 && hash_equals($expected, $received);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:160'],
            'event' => ['required', 'string', 'max:80'],
            'payment' => ['required', 'array'],
            'payment.id' => ['required', 'string', 'max:100'],
            'payment.status' => ['nullable', 'string', 'max:40'],
            'payment.billingType' => ['nullable', 'string', 'max:40'],
            'payment.externalReference' => ['nullable', 'string', 'max:100'],
            'payment.value' => ['nullable', 'numeric'],
            'payment.refundedValue' => ['nullable', 'numeric'],
            'payment.chargeback' => ['nullable', 'array'],
            'payment.chargeback.status' => ['nullable', 'string', 'max:80'],
            'payment.chargeback.reason' => ['nullable', 'string', 'max:160'],
        ];
    }
}
