<?php

declare(strict_types=1);

use App\Modules\Cart\Presentation\Http\CartController;
use Illuminate\Support\Facades\Route;

Route::get('/carrinho', [CartController::class, 'index'])->name('carrinho');
Route::post('/carrinho/itens', [CartController::class, 'store'])
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.itens.store');
Route::delete('/carrinho/itens/{product}', [CartController::class, 'destroy'])
    ->whereUuid('product')
    ->middleware(['throttle:commerce', 'idempotent'])
    ->name('carrinho.itens.destroy');
