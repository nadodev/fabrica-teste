<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Request;

use App\Modules\Administration\Domain\AdminPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAdministratorPermissionsRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array', 'max:'.count(AdminPermission::cases())],
            'permissions.*' => ['string', 'distinct', Rule::enum(AdminPermission::class)],
        ];
    }
}
