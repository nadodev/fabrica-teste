<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('catalog', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('commerce', fn (Request $request): Limit => Limit::perMinute(30)->by(
            (string) ($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));
        RateLimiter::for('authentication', fn (Request $request): array => [
            Limit::perMinute(20)->by((string) $request->ip()),
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
        ]);
        RateLimiter::for('admin-two-factor', fn (Request $request): Limit => Limit::perMinute(10)->by(
            (string) $request->session()->get('admin_login_challenge_id', 'missing').'|'.$request->ip(),
        ));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(function (): Password {
            $rule = Password::min(12)->mixedCase()->letters()->numbers();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
