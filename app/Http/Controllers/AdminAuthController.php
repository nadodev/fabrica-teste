<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AuthenticateAdminRequest;
use App\Http\Requests\VerifyAdminTwoFactorRequest;
use App\Modules\Administration\Application\Command\CompleteAdminLogin;
use App\Modules\Administration\Application\Command\StartAdminLogin;
use App\Modules\Administration\Application\DTO\AdminLoginContext;
use App\Modules\Administration\Application\Exception\AdminLoginChallengeFailed;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AdminAuthController extends Controller
{
    public function create(Request $request): Response
    {
        $this->forgetChallenge($request);

        return Inertia::render('auth/login');
    }

    public function store(AuthenticateAdminRequest $request, StartAdminLogin $command): RedirectResponse
    {
        try {
            $challenge = $command->handle(
                (string) $request->validated('email'),
                (string) $request->validated('password'),
                $request->boolean('remember'),
                $this->context($request),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()]);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_login_challenge_id', $challenge->challengeId);
        $request->session()->put('admin_login_challenge_expires_at', $challenge->expiresAt->format(DATE_ATOM));

        return to_route('admin.two-factor');
    }

    public function challenge(Request $request): Response|RedirectResponse
    {
        if (! is_string($request->session()->get('admin_login_challenge_id'))) {
            return to_route('admin.login');
        }

        return Inertia::render('auth/two-factor', [
            'expiresAt' => $request->session()->get('admin_login_challenge_expires_at'),
        ]);
    }

    public function verify(VerifyAdminTwoFactorRequest $request, CompleteAdminLogin $command): RedirectResponse
    {
        $challengeId = $request->session()->get('admin_login_challenge_id');
        if (! is_string($challengeId)) {
            return to_route('admin.login')->withErrors(['email' => 'Entre novamente para receber um código de acesso.']);
        }

        try {
            $completed = $command->handle(
                $challengeId,
                (string) $request->validated('code'),
                $this->context($request),
            );
        } catch (AdminLoginChallengeFailed $exception) {
            if ($exception->restart) {
                $this->forgetChallenge($request);

                return to_route('admin.login')->withErrors(['email' => $exception->getMessage()]);
            }

            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        if (Auth::loginUsingId($completed->userId, $completed->remember) === false) {
            $this->forgetChallenge($request);

            return to_route('admin.login')->withErrors(['email' => 'Não foi possível concluir o acesso. Entre novamente.']);
        }

        $this->forgetChallenge($request);
        $request->session()->regenerate();

        return to_route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }

    private function context(Request $request): AdminLoginContext
    {
        $ip = $request->ip();

        return new AdminLoginContext(
            $ip === null ? null : hash_hmac('sha256', $ip, (string) config('app.key')),
            $request->userAgent(),
        );
    }

    private function forgetChallenge(Request $request): void
    {
        $request->session()->forget(['admin_login_challenge_id', 'admin_login_challenge_expires_at']);
    }
}
