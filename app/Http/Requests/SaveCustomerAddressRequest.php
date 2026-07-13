<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveCustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:personal,shipping'],
            'label' => ['required', 'string', 'max:80'],
            'postalCode' => ['required', 'string', 'regex:/^(?:\D*\d){8}\D*$/'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:40'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'isDefault' => ['required', 'boolean'],
        ];
    }
}
