<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controller;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Command\SendVerificationEmail;
use App\Modules\Identity\Application\Command\VerifyCustomerEmail;
use App\Modules\Identity\Presentation\Http\Request\VerifyCustomerEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerEmailVerificationController extends Controller
{
    public function notice(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return to_route('cliente.conta');
        }

        return Inertia::render('cliente/verificar-email', [
            'email' => (string) $request->user()?->email,
        ]);
    }

    public function verify(VerifyCustomerEmailRequest $request, VerifyCustomerEmail $verify): RedirectResponse
    {
        $verify->handle((int) $request->user()->getAuthIdentifier());

        return to_route('cliente.conta')->with('success', 'E-mail confirmado com sucesso.');
    }

    public function send(Request $request, SendVerificationEmail $send): RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return to_route('cliente.conta');
        }

        if (! $send->handle((int) $request->user()->getAuthIdentifier())) {
            return back()->withErrors(['email' => 'Nao foi possivel enviar agora. Tente novamente em alguns minutos.']);
        }

        return back()->with('success', 'Enviamos um novo link de confirmacao.');
    }
}
