<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyCustomerEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->user();

        return $customer !== null
            && (string) $customer->getAuthIdentifier() === (string) $this->route('id')
            && hash_equals(sha1($customer->getEmailForVerification()), (string) $this->route('hash'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
