<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(?:\D*\d){10,11}\D*$/'],
            'document' => ['nullable', 'string', 'max:40', 'regex:/^(?:\D*\d){11}(?:(?:\D*\d){3})?\D*$/'],
        ];
    }
}
