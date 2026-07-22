<?php

declare(strict_types=1);

namespace App\Modules\Administration\Domain;

enum AdminPermission: string
{
    case DashboardView = 'admin.dashboard.view';
    case CatalogView = 'admin.catalog.view';
    case CatalogManage = 'admin.catalog.manage';
    case OrdersView = 'admin.orders.view';
    case OrdersManage = 'admin.orders.manage';
    case InventoryView = 'admin.inventory.view';
    case InventoryManage = 'admin.inventory.manage';
    case CustomersView = 'admin.customers.view';
    case CouponsManage = 'admin.coupons.manage';
    case ContentManage = 'admin.content.manage';
    case SettingsManage = 'admin.settings.manage';
    case ShippingManage = 'admin.shipping.manage';
    case ReportsView = 'admin.reports.view';
    case OperationsView = 'admin.operations.view';
    case BackupsManage = 'admin.backups.manage';
    case AdministratorsManage = 'admin.administrators.manage';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'Acessar dashboard',
            self::CatalogView => 'Visualizar catálogo',
            self::CatalogManage => 'Gerenciar produtos e categorias',
            self::OrdersView => 'Visualizar pedidos',
            self::OrdersManage => 'Alterar pedidos',
            self::InventoryView => 'Visualizar estoque',
            self::InventoryManage => 'Ajustar estoque',
            self::CustomersView => 'Visualizar clientes',
            self::CouponsManage => 'Gerenciar cupons e promoções',
            self::ContentManage => 'Gerenciar conteúdo e marketing',
            self::SettingsManage => 'Alterar configurações gerais',
            self::ShippingManage => 'Alterar frete e entrega',
            self::ReportsView => 'Visualizar relatórios',
            self::OperationsView => 'Visualizar operação e auditoria',
            self::BackupsManage => 'Executar backups',
            self::AdministratorsManage => 'Gerenciar administradores',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DashboardView => 'Painel',
            self::CatalogView, self::CatalogManage => 'Catálogo',
            self::OrdersView, self::OrdersManage, self::CouponsManage => 'Vendas',
            self::InventoryView, self::InventoryManage => 'Estoque',
            self::CustomersView => 'Clientes',
            self::ContentManage => 'Conteúdo',
            self::ReportsView => 'Relatórios',
            self::SettingsManage, self::ShippingManage => 'Configurações',
            self::OperationsView, self::BackupsManage, self::AdministratorsManage => 'Segurança',
        };
    }
}
