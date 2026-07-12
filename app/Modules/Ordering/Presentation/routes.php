<?php

declare(strict_types=1);

use App\Modules\Ordering\Presentation\Http\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware(['throttle:commerce', 'idempotent'])->name('checkout.store');
Route::get('/pedido-confirmado/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
