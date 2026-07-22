<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controller;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Application\Command\PromoteAdministrator;
use App\Modules\Administration\Application\Command\RevokeAdministrator;
use App\Modules\Administration\Application\Command\UpdateAdministratorPermissions;
use App\Modules\Administration\Application\DTO\AdministratorAccount;
use App\Modules\Administration\Application\Query\ListAdministrators;
use App\Modules\Administration\Domain\AdminPermission;
use App\Modules\Administration\Presentation\Http\Request\PromoteAdministratorRequest;
use App\Modules\Administration\Presentation\Http\Request\UpdateAdministratorPermissionsRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AdminAdministratorController extends Controller
{
    public function index(ListAdministrators $query): Response
    {
        return Inertia::render('admin/administrators/index', [
            'administrators' => array_map($this->serialize(...), $query->handle()),
            'permissions' => array_map(static fn (AdminPermission $permission): array => [
                'value' => $permission->value,
                'label' => $permission->label(),
                'group' => $permission->group(),
            ], array_filter(
                AdminPermission::cases(),
                static fn (AdminPermission $permission): bool => $permission !== AdminPermission::AdministratorsManage,
            )),
        ]);
    }

    public function store(PromoteAdministratorRequest $request, PromoteAdministrator $command): RedirectResponse
    {
        $data = $request->validated();
        try {
            $command->handle(
                (int) $request->user()->getAuthIdentifier(),
                (string) $data['email'],
                array_values((array) $data['permissions']),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()]);
        }

        return back()->with('success', 'Acesso administrativo concedido.');
    }

    public function update(
        int $administrator,
        UpdateAdministratorPermissionsRequest $request,
        UpdateAdministratorPermissions $command,
    ): RedirectResponse {
        try {
            $command->handle(
                (int) $request->user()->getAuthIdentifier(),
                $administrator,
                array_values((array) $request->validated('permissions')),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['permissions' => $exception->getMessage()]);
        }

        return back()->with('success', 'Permissões atualizadas.');
    }

    public function destroy(int $administrator, Request $request, RevokeAdministrator $command): RedirectResponse
    {
        try {
            $command->handle((int) $request->user()->getAuthIdentifier(), $administrator);
        } catch (DomainException $exception) {
            return back()->withErrors(['administrator' => $exception->getMessage()]);
        }

        return back()->with('success', 'Acesso administrativo removido e sessões encerradas.');
    }

    /** @return array<string, mixed> */
    private function serialize(AdministratorAccount $administrator): array
    {
        return [
            'id' => $administrator->id,
            'name' => $administrator->name,
            'email' => $administrator->email,
            'emailVerified' => $administrator->emailVerified,
            'superAdministrator' => $administrator->superAdministrator,
            'permissions' => $administrator->permissions,
        ];
    }
}
