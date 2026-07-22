<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Identity\Application\Command\RegisterCustomer;
use App\Modules\Identity\Application\DTO\RegisterCustomerData;
use App\Modules\Identity\Presentation\Http\Request\AuthenticateCustomerRequest;
use App\Modules\Identity\Presentation\Http\Request\RegisterCustomerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerAuthController extends Controller
{
    public function login(): Response
    {
        return Inertia::render('cliente/login');
    }

    public function register(): Response
    {
        return Inertia::render('cliente/cadastro');
    }

    public function storeLogin(AuthenticateCustomerRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha invalidos.']);
        }

        $request->session()->regenerate();

        return to_route('cliente.conta');
    }

    public function storeRegister(RegisterCustomerRequest $request, RegisterCustomer $register): RedirectResponse
    {
        $data = $request->validated();
        $customer = $register->handle(new RegisterCustomerData(
            (string) $data['name'],
            (string) $data['email'],
            (string) $data['password'],
        ));

        Auth::loginUsingId($customer->id);
        $request->session()->regenerate();

        return to_route('verification.notice')->with('success', 'Conta criada. Confirme o e-mail para continuar.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
