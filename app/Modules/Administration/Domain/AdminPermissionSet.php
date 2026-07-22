<?php

declare(strict_types=1);

namespace App\Modules\Administration\Domain;

use DomainException;

final class AdminPermissionSet
{
    /** @param list<string> $permissions
     * @return list<AdminPermission>
     */
    public static function fromValues(array $permissions): array
    {
        $resolved = [AdminPermission::DashboardView->value => AdminPermission::DashboardView];

        foreach ($permissions as $value) {
            $permission = AdminPermission::tryFrom($value)
                ?? throw new DomainException('Permissão administrativa inválida.');
            if ($permission === AdminPermission::AdministratorsManage) {
                throw new DomainException('A gestão de administradores é exclusiva do proprietário.');
            }
            $resolved[$permission->value] = $permission;
        }

        foreach ([
            AdminPermission::CatalogManage->value => AdminPermission::CatalogView,
            AdminPermission::OrdersManage->value => AdminPermission::OrdersView,
            AdminPermission::InventoryManage->value => AdminPermission::InventoryView,
            AdminPermission::BackupsManage->value => AdminPermission::OperationsView,
        ] as $managing => $viewing) {
            if (isset($resolved[$managing])) {
                $resolved[$viewing->value] = $viewing;
            }
        }

        ksort($resolved);

        return array_values($resolved);
    }
}
