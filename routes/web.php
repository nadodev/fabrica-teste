<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'home')->name('home');
Route::inertia('/empresas', 'empresas')->name('empresas');
Route::inertia('/escolas', 'escolas')->name('escolas');
Route::inertia('/personalizados', 'personalizados')->name('personalizados');
Route::inertia('/orcamento', 'orcamento')->name('orcamento');

require app_path('Modules/Catalog/Presentation/routes.php');
require app_path('Modules/Cart/Presentation/routes.php');
