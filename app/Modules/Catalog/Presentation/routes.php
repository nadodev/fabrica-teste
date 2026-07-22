<?php

declare(strict_types=1);

use App\Http\Controllers\AdminCatalogCategoryController;
use App\Http\Controllers\AdminCouponController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminInventoryController;
use App\Http\Controllers\AdminMarketingController;
use App\Http\Controllers\AdminOperationsController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminShippingController;
use App\Http\Controllers\AdminSiteContentController;
use App\Modules\Administration\Presentation\Http\Controller\AdminAdministratorController;
use App\Modules\Catalog\Presentation\Http\AdminProductController;
use App\Modules\Catalog\Presentation\Http\CatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:catalog')->group(function (): void {
    Route::get('/produtos', [CatalogController::class, 'index'])->name('produtos');
    Route::get('/produtos/{product}', [CatalogController::class, 'show'])->whereUuid('product')->name('produtos.show');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'audit.admin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->middleware('can:admin.dashboard.view')->name('dashboard');
    Route::get('/configuracoes', [AdminSettingsController::class, 'edit'])->middleware('can:admin.settings.manage')->name('settings.edit');
    Route::post('/configuracoes', [AdminSettingsController::class, 'update'])->middleware(['can:admin.settings.manage', 'throttle:commerce', 'idempotent'])->name('settings.update');
    Route::get('/frete', [AdminShippingController::class, 'edit'])->middleware('can:admin.shipping.manage')->name('shipping.edit');
    Route::post('/frete', [AdminShippingController::class, 'update'])->middleware(['can:admin.shipping.manage', 'throttle:commerce', 'idempotent'])->name('shipping.update');
    Route::get('/relatorios', AdminReportController::class)->middleware('can:admin.reports.view')->name('reports');
    Route::get('/marketing', AdminMarketingController::class)->middleware('can:admin.content.manage')->name('marketing');
    Route::get('/operacao', [AdminOperationsController::class, 'index'])->middleware('can:admin.operations.view')->name('operations');
    Route::post('/operacao/backup', [AdminOperationsController::class, 'backup'])->middleware(['can:admin.backups.manage', 'throttle:commerce', 'idempotent'])->name('operations.backup');
    Route::get('/clientes', [AdminCustomerController::class, 'index'])->middleware('can:admin.customers.view')->name('customers.index');
    Route::get('/clientes/{email}', [AdminCustomerController::class, 'show'])->where('email', '.*')->middleware('can:admin.customers.view')->name('customers.show');
    Route::get('/produtos', [AdminProductController::class, 'index'])->middleware('can:admin.catalog.view')->name('products.index');
    Route::get('/produtos/exportar', [AdminProductController::class, 'export'])->middleware('can:admin.catalog.view')->name('products.export');
    Route::post('/produtos/importar', [AdminProductController::class, 'import'])->middleware(['can:admin.catalog.manage', 'throttle:commerce', 'idempotent'])->name('products.import');
    Route::get('/produtos/novo', [AdminProductController::class, 'create'])->middleware('can:admin.catalog.manage')->name('products.create');
    Route::get('/produtos/{product}/editar', [AdminProductController::class, 'edit'])->whereUuid('product')->middleware('can:admin.catalog.manage')->name('products.edit');
    Route::post('/produtos', [AdminProductController::class, 'store'])
        ->middleware(['can:admin.catalog.manage', 'throttle:commerce', 'idempotent'])
        ->name('products.store');
    Route::put('/produtos/{product}', [AdminProductController::class, 'update'])->whereUuid('product')->middleware(['can:admin.catalog.manage', 'throttle:commerce', 'idempotent'])->name('products.update');
    Route::delete('/produtos/{product}', [AdminProductController::class, 'destroy'])->whereUuid('product')->middleware(['can:admin.catalog.manage', 'throttle:commerce', 'idempotent'])->name('products.destroy');

    Route::get('/categorias-produtos', [AdminCatalogCategoryController::class, 'index'])->middleware('can:admin.catalog.view')->name('catalog-categories.index');
    Route::post('/categorias-produtos', [AdminCatalogCategoryController::class, 'store'])->middleware(['can:admin.catalog.manage', 'throttle:commerce', 'idempotent'])->name('catalog-categories.store');
    Route::post('/categorias-produtos/{category}', [AdminCatalogCategoryController::class, 'update'])->whereUuid('category')->middleware(['can:admin.catalog.manage', 'throttle:commerce', 'idempotent'])->name('catalog-categories.update');
    Route::delete('/categorias-produtos/{category}', [AdminCatalogCategoryController::class, 'destroy'])->whereUuid('category')->middleware(['can:admin.catalog.manage', 'throttle:commerce', 'idempotent'])->name('catalog-categories.destroy');

    Route::get('/pedidos', [AdminOrderController::class, 'index'])->middleware('can:admin.orders.view')->name('orders.index');
    Route::get('/pedidos/{order}', [AdminOrderController::class, 'show'])->whereUuid('order')->middleware('can:admin.orders.view')->name('orders.show');
    Route::post('/pedidos/{order}/status', [AdminOrderController::class, 'updateStatus'])->whereUuid('order')->middleware(['can:admin.orders.manage', 'throttle:commerce', 'idempotent'])->name('orders.status');

    Route::get('/estoque', [AdminInventoryController::class, 'index'])->middleware('can:admin.inventory.view')->name('inventory.index');
    Route::post('/estoque/ajuste', [AdminInventoryController::class, 'adjust'])->middleware(['can:admin.inventory.manage', 'throttle:commerce', 'idempotent'])->name('inventory.adjust');

    Route::get('/cupons', [AdminCouponController::class, 'index'])->middleware('can:admin.coupons.manage')->name('coupons.index');
    Route::get('/cupons/novo', [AdminCouponController::class, 'create'])->middleware('can:admin.coupons.manage')->name('coupons.create');
    Route::post('/cupons', [AdminCouponController::class, 'store'])->middleware(['can:admin.coupons.manage', 'throttle:commerce', 'idempotent'])->name('coupons.store');
    Route::get('/cupons/{coupon}/editar', [AdminCouponController::class, 'edit'])->whereUuid('coupon')->middleware('can:admin.coupons.manage')->name('coupons.edit');
    Route::post('/cupons/{coupon}', [AdminCouponController::class, 'update'])->whereUuid('coupon')->middleware(['can:admin.coupons.manage', 'throttle:commerce', 'idempotent'])->name('coupons.update');
    Route::delete('/cupons/{coupon}', [AdminCouponController::class, 'destroy'])->whereUuid('coupon')->middleware(['can:admin.coupons.manage', 'throttle:commerce', 'idempotent'])->name('coupons.destroy');

    Route::get('/conteudo/{type}', [AdminSiteContentController::class, 'index'])->middleware('can:admin.content.manage')->name('content.index');
    Route::get('/conteudo/{type}/novo', [AdminSiteContentController::class, 'create'])->middleware('can:admin.content.manage')->name('content.create');
    Route::post('/conteudo/{type}', [AdminSiteContentController::class, 'store'])->middleware(['can:admin.content.manage', 'throttle:commerce', 'idempotent'])->name('content.store');
    Route::get('/conteudo/{type}/{id}/editar', [AdminSiteContentController::class, 'edit'])->whereUuid('id')->middleware('can:admin.content.manage')->name('content.edit');
    Route::post('/conteudo/{type}/{id}', [AdminSiteContentController::class, 'update'])->whereUuid('id')->middleware(['can:admin.content.manage', 'throttle:commerce', 'idempotent'])->name('content.update');
    Route::delete('/conteudo/{type}/{id}', [AdminSiteContentController::class, 'destroy'])->whereUuid('id')->middleware(['can:admin.content.manage', 'throttle:commerce', 'idempotent'])->name('content.destroy');

    Route::get('/usuarios', [AdminAdministratorController::class, 'index'])->middleware('can:admin.administrators.manage')->name('administrators.index');
    Route::post('/usuarios', [AdminAdministratorController::class, 'store'])->middleware(['can:admin.administrators.manage', 'throttle:commerce', 'idempotent'])->name('administrators.store');
    Route::put('/usuarios/{administrator}', [AdminAdministratorController::class, 'update'])->whereNumber('administrator')->middleware(['can:admin.administrators.manage', 'throttle:commerce', 'idempotent'])->name('administrators.update');
    Route::delete('/usuarios/{administrator}', [AdminAdministratorController::class, 'destroy'])->whereNumber('administrator')->middleware(['can:admin.administrators.manage', 'throttle:commerce', 'idempotent'])->name('administrators.destroy');
});
