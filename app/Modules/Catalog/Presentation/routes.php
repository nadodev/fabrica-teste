<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/produtos', [CatalogController::class, 'index'])->name('produtos');
Route::get('/produtos/{product}', [CatalogController::class, 'show'])->whereUuid('product')->name('produtos.show');
