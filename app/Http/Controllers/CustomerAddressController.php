<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SaveCustomerAddressRequest;
use App\Modules\Customers\Application\Command\DeleteCustomerAddress;
use App\Modules\Customers\Application\Command\SaveCustomerAddress;
use App\Modules\Customers\Application\DTO\CustomerAddressData;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CustomerAddressController extends Controller
{
    public function store(SaveCustomerAddressRequest $request, SaveCustomerAddress $addresses): RedirectResponse
    {
        $this->save($request, $addresses, null);

        return back()->with('success', 'Endereco cadastrado.');
    }

    public function update(string $address, SaveCustomerAddressRequest $request, SaveCustomerAddress $addresses): RedirectResponse
    {
        try {
            $this->save($request, $addresses, $address);
        } catch (DomainException) {
            abort(404);
        }

        return back()->with('success', 'Endereco atualizado.');
    }

    public function destroy(string $address, Request $request, DeleteCustomerAddress $addresses): RedirectResponse
    {
        try {
            $addresses->handle((int) $request->user()->getAuthIdentifier(), $address);
        } catch (DomainException) {
            abort(404);
        }

        return back()->with('success', 'Endereco removido.');
    }

    private function save(SaveCustomerAddressRequest $request, SaveCustomerAddress $addresses, ?string $addressId): void
    {
        $data = $request->validated();
        $addresses->handle(
            (int) $request->user()->getAuthIdentifier(),
            $addressId,
            new CustomerAddressData(
                (string) $data['type'],
                (string) $data['label'],
                (string) $data['postalCode'],
                (string) $data['street'],
                (string) $data['number'],
                (string) $data['city'],
                strtoupper((string) $data['state']),
                (bool) $data['isDefault'],
            ),
        );
    }
}
