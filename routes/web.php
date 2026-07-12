<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'home')->name('home');
Route::inertia('/empresas', 'empresas')->name('empresas');
Route::inertia('/escolas', 'escolas')->name('escolas');
Route::inertia('/produtos', 'produtos')->name('produtos');
Route::get('/produtos/{product}', fn (string $product) => Inertia::render('produto-detalhe', [
    'productId' => $product,
]))->name('produtos.show');
Route::inertia('/personalizados', 'personalizados')->name('personalizados');
Route::inertia('/orcamento', 'orcamento')->name('orcamento');
Route::inertia('/carrinho', 'carrinho')->name('carrinho');
