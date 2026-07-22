<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Request;

use App\Modules\Administration\Domain\AdminPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PromoteAdministratorRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:160'],
            'permissions' => ['present', 'array', 'max:'.count(AdminPermission::cases())],
            'permissions.*' => ['string', 'distinct', Rule::enum(AdminPermission::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }
}
