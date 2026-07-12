<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->middleware('throttle:authentication')->name('admin.login.store');
});
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->middleware('auth')->name('admin.logout');

Route::inertia('/', 'home')->name('home');
Route::inertia('/empresas', 'empresas')->name('empresas');
Route::inertia('/escolas', 'escolas')->name('escolas');
Route::inertia('/personalizados', 'personalizados')->name('personalizados');
Route::inertia('/orcamento', 'orcamento')->name('orcamento');

require app_path('Modules/Catalog/Presentation/routes.php');
require app_path('Modules/Cart/Presentation/routes.php');
