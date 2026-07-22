<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicSeoController;
use App\Modules\Identity\Presentation\Http\Controller\CustomerEmailVerificationController;
use App\Modules\Identity\Presentation\Http\Controller\CustomerPasswordController;
use App\Modules\Payment\Presentation\Http\AsaasWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/asaas', AsaasWebhookController::class)->middleware('throttle:120,1')->name('webhooks.asaas');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->middleware('throttle:authentication')->name('admin.login.store');
    Route::get('/admin/verificar-acesso', [AdminAuthController::class, 'challenge'])->name('admin.two-factor');
    Route::post('/admin/verificar-acesso', [AdminAuthController::class, 'verify'])->middleware('throttle:admin-two-factor')->name('admin.two-factor.verify');
});
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->middleware(['auth', 'admin'])->name('admin.logout');

Route::middleware('guest')->group(function (): void {
    Route::get('/entrar', [CustomerAuthController::class, 'login'])->name('cliente.login');
    Route::post('/entrar', [CustomerAuthController::class, 'storeLogin'])->middleware('throttle:authentication')->name('cliente.login.store');
    Route::get('/cadastro', [CustomerAuthController::class, 'register'])->name('cliente.register');
    Route::post('/cadastro', [CustomerAuthController::class, 'storeRegister'])->middleware('throttle:authentication')->name('cliente.register.store');
    Route::get('/esqueci-senha', [CustomerPasswordController::class, 'request'])->name('password.request');
    Route::post('/esqueci-senha', [CustomerPasswordController::class, 'email'])->middleware('throttle:authentication')->name('password.email');
    Route::get('/redefinir-senha/{token}', [CustomerPasswordController::class, 'reset'])->name('password.reset');
    Route::post('/redefinir-senha', [CustomerPasswordController::class, 'update'])->middleware('throttle:authentication')->name('password.update');
});
Route::post('/sair', [CustomerAuthController::class, 'logout'])->middleware('auth')->name('cliente.logout');
Route::middleware('auth')->group(function (): void {
    Route::get('/email/verificar', [CustomerEmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verificar/{id}/{hash}', [CustomerEmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verificacao', [CustomerEmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});
Route::get('/minha-conta', CustomerAccountController::class)->middleware(['auth', 'verified'])->name('cliente.conta');
Route::middleware(['auth', 'verified', 'throttle:commerce'])->prefix('/minha-conta')->name('cliente.')->group(function (): void {
    Route::put('/perfil', [CustomerProfileController::class, 'update'])->name('profile.update');
    Route::post('/enderecos', [CustomerAddressController::class, 'store'])->name('addresses.store');
    Route::put('/enderecos/{address}', [CustomerAddressController::class, 'update'])->whereUuid('address')->name('addresses.update');
    Route::delete('/enderecos/{address}', [CustomerAddressController::class, 'destroy'])->whereUuid('address')->name('addresses.destroy');
});

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PublicSeoController::class, 'robots'])->name('robots');
Route::inertia('/empresas', 'empresas')->name('empresas');
Route::inertia('/escolas', 'escolas')->name('escolas');
Route::inertia('/personalizados', 'personalizados')->name('personalizados');
Route::inertia('/orcamento', 'orcamento')->name('orcamento');
Route::inertia('/privacidade', 'legal', ['type' => 'privacy'])->name('privacy');
Route::inertia('/termos', 'legal', ['type' => 'terms'])->name('terms');

require app_path('Modules/Catalog/Presentation/routes.php');
require app_path('Modules/Cart/Presentation/routes.php');
require app_path('Modules/Ordering/Presentation/routes.php');
