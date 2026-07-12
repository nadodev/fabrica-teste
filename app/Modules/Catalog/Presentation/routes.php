<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\AdminProductController;
use App\Modules\Catalog\Presentation\Http\CatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:catalog')->group(function (): void {
    Route::get('/produtos', [CatalogController::class, 'index'])->name('produtos');
    Route::get('/produtos/{product}', [CatalogController::class, 'show'])->whereUuid('product')->name('produtos.show');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::redirect('/', '/admin/produtos')->name('dashboard');
    Route::get('/produtos', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/produtos/novo', [AdminProductController::class, 'create'])->name('products.create');
    Route::get('/produtos/{product}/editar', [AdminProductController::class, 'edit'])->whereUuid('product')->name('products.edit');
    Route::post('/produtos', [AdminProductController::class, 'store'])
        ->middleware(['throttle:commerce', 'idempotent'])
        ->name('products.store');
    Route::put('/produtos/{product}', [AdminProductController::class, 'update'])->whereUuid('product')->middleware(['throttle:commerce', 'idempotent'])->name('products.update');
    Route::delete('/produtos/{product}', [AdminProductController::class, 'destroy'])->whereUuid('product')->middleware(['throttle:commerce', 'idempotent'])->name('products.destroy');
});
