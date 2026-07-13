<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomerProfileRequest;
use App\Modules\Customers\Application\Command\UpdateCustomerProfile;
use Illuminate\Http\RedirectResponse;

final class CustomerProfileController extends Controller
{
    public function update(UpdateCustomerProfileRequest $request, UpdateCustomerProfile $profile): RedirectResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();
        $data = $request->validated();
        $profile->handle($userId, (string) $data['name'], (string) ($data['phone'] ?? ''), (string) ($data['document'] ?? ''));

        return back()->with('success', 'Dados pessoais atualizados.');
    }
}
