<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\AdminProductController;
use App\Modules\Catalog\Presentation\Http\CatalogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminCouponController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminSiteContentController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:catalog')->group(function (): void {
    Route::get('/produtos', [CatalogController::class, 'index'])->name('produtos');
    Route::get('/produtos/{product}', [CatalogController::class, 'show'])->whereUuid('product')->name('produtos.show');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/configuracoes', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/configuracoes', [AdminSettingsController::class, 'update'])->middleware(['throttle:commerce', 'idempotent'])->name('settings.update');
    Route::get('/produtos', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/produtos/novo', [AdminProductController::class, 'create'])->name('products.create');
    Route::get('/produtos/{product}/editar', [AdminProductController::class, 'edit'])->whereUuid('product')->name('products.edit');
    Route::post('/produtos', [AdminProductController::class, 'store'])
        ->middleware(['throttle:commerce', 'idempotent'])
        ->name('products.store');
    Route::put('/produtos/{product}', [AdminProductController::class, 'update'])->whereUuid('product')->middleware(['throttle:commerce', 'idempotent'])->name('products.update');
    Route::delete('/produtos/{product}', [AdminProductController::class, 'destroy'])->whereUuid('product')->middleware(['throttle:commerce', 'idempotent'])->name('products.destroy');

    Route::get('/cupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::get('/cupons/novo', [AdminCouponController::class, 'create'])->name('coupons.create');
    Route::post('/cupons', [AdminCouponController::class, 'store'])->middleware(['throttle:commerce', 'idempotent'])->name('coupons.store');
    Route::get('/cupons/{coupon}/editar', [AdminCouponController::class, 'edit'])->whereUuid('coupon')->name('coupons.edit');
    Route::post('/cupons/{coupon}', [AdminCouponController::class, 'update'])->whereUuid('coupon')->middleware(['throttle:commerce', 'idempotent'])->name('coupons.update');
    Route::delete('/cupons/{coupon}', [AdminCouponController::class, 'destroy'])->whereUuid('coupon')->middleware(['throttle:commerce', 'idempotent'])->name('coupons.destroy');

    Route::get('/conteudo/{type}', [AdminSiteContentController::class, 'index'])->name('content.index');
    Route::get('/conteudo/{type}/novo', [AdminSiteContentController::class, 'create'])->name('content.create');
    Route::post('/conteudo/{type}', [AdminSiteContentController::class, 'store'])->middleware(['throttle:commerce', 'idempotent'])->name('content.store');
    Route::get('/conteudo/{type}/{id}/editar', [AdminSiteContentController::class, 'edit'])->whereUuid('id')->name('content.edit');
    Route::post('/conteudo/{type}/{id}', [AdminSiteContentController::class, 'update'])->whereUuid('id')->middleware(['throttle:commerce', 'idempotent'])->name('content.update');
    Route::delete('/conteudo/{type}/{id}', [AdminSiteContentController::class, 'destroy'])->whereUuid('id')->middleware(['throttle:commerce', 'idempotent'])->name('content.destroy');
});
