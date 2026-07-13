<?php

declare(strict_types=1);

use App\Modules\Cart\Presentation\Http\CartController;
use App\Modules\Cart\Presentation\Http\ShippingController;
use Illuminate\Support\Facades\Route;

Route::get('/carrinho', [CartController::class, 'index'])->name('carrinho');
Route::post('/carrinho/itens', [CartController::class, 'store'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.itens.store');
Route::delete('/carrinho/itens/{product}', [CartController::class, 'destroy'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.itens.destroy');
Route::patch('/carrinho/itens/{product}', [CartController::class, 'updateQuantity'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.itens.quantity');
Route::patch('/carrinho/itens/{product}/observacao', [CartController::class, 'updateNotes'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.itens.notes');
Route::post('/carrinho/cupom', [CartController::class, 'applyCoupon'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.cupom.apply');
Route::delete('/carrinho/cupom', [CartController::class, 'removeCoupon'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.cupom.remove');
Route::post('/carrinho/frete', [ShippingController::class, 'quote'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.frete.quote');
Route::post('/carrinho/frete/selecionar', [ShippingController::class, 'select'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.frete.select');
Route::delete('/carrinho/frete', [ShippingController::class, 'remove'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.frete.remove');
