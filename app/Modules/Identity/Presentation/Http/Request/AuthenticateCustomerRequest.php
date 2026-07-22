<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class AuthenticateCustomerRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }
}
