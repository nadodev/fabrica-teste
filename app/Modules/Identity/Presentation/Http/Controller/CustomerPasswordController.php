<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controller;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Command\ResetCustomerPassword;
use App\Modules\Identity\Application\Command\SendPasswordResetLink;
use App\Modules\Identity\Application\DTO\ResetPasswordData;
use App\Modules\Identity\Application\Result\PasswordResetResult;
use App\Modules\Identity\Presentation\Http\Request\ForgotPasswordRequest;
use App\Modules\Identity\Presentation\Http\Request\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerPasswordController extends Controller
{
    public function request(): Response
    {
        return Inertia::render('cliente/esqueci-senha');
    }

    public function email(ForgotPasswordRequest $request, SendPasswordResetLink $send): RedirectResponse
    {
        $send->handle((string) $request->validated('email'));

        return back()->with('success', 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir a senha.');
    }

    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('cliente/redefinir-senha', [
            'email' => (string) $request->query('email', ''),
            'token' => $token,
        ]);
    }

    public function update(ResetPasswordRequest $request, ResetCustomerPassword $reset): RedirectResponse
    {
        $data = $request->validated();
        $result = $reset->handle(new ResetPasswordData(
            (string) $data['email'],
            (string) $data['password'],
            (string) $data['token'],
        ));

        if ($result !== PasswordResetResult::Reset) {
            return back()->withErrors(['email' => 'O link e invalido ou expirou. Solicite uma nova redefinicao.']);
        }

        return to_route('cliente.login')->with('success', 'Senha redefinida. Agora voce pode entrar na sua conta.');
    }
}
