<?php

declare(strict_types=1);

namespace App\Modules\Administration\Infrastructure;

use App\Models\User;
use App\Modules\Administration\Application\Command\StartAdminLogin;
use App\Modules\Administration\Application\Port\AdminAuditReadModel;
use App\Modules\Administration\Application\Port\AdminAuditRecorder;
use App\Modules\Administration\Application\Port\AdminAuditRetention;
use App\Modules\Administration\Application\Port\AdminChallengeCodeGenerator;
use App\Modules\Administration\Application\Port\AdminChallengeCodeHasher;
use App\Modules\Administration\Application\Port\AdminCredentialVerifier;
use App\Modules\Administration\Application\Port\AdministratorRepository;
use App\Modules\Administration\Application\Port\AdminLoginChallengeRepository;
use App\Modules\Administration\Application\Port\AdminPermissionChecker;
use App\Modules\Administration\Application\Port\AdminTwoFactorNotifier;
use App\Modules\Administration\Domain\AdminPermission;
use App\Modules\Administration\Infrastructure\Authentication\EloquentAdminCredentialVerifier;
use App\Modules\Administration\Infrastructure\Authentication\HmacAdminChallengeCodeHasher;
use App\Modules\Administration\Infrastructure\Authentication\SecureAdminChallengeCodeGenerator;
use App\Modules\Administration\Infrastructure\Notification\LaravelAdminTwoFactorNotifier;
use App\Modules\Administration\Infrastructure\Persistence\DatabaseAdminAuditReadModel;
use App\Modules\Administration\Infrastructure\Persistence\DatabaseAdminAuditRecorder;
use App\Modules\Administration\Infrastructure\Persistence\DatabaseAdministratorRepository;
use App\Modules\Administration\Infrastructure\Persistence\DatabaseAdminLoginChallengeRepository;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;

final class AdministrationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        AdministratorRepository::class => DatabaseAdministratorRepository::class,
        AdminPermissionChecker::class => DatabaseAdministratorRepository::class,
        AdminAuditRecorder::class => DatabaseAdminAuditRecorder::class,
        AdminAuditRetention::class => DatabaseAdminAuditRecorder::class,
        AdminAuditReadModel::class => DatabaseAdminAuditReadModel::class,
        AdminCredentialVerifier::class => EloquentAdminCredentialVerifier::class,
        AdminChallengeCodeGenerator::class => SecureAdminChallengeCodeGenerator::class,
        AdminChallengeCodeHasher::class => HmacAdminChallengeCodeHasher::class,
        AdminLoginChallengeRepository::class => DatabaseAdminLoginChallengeRepository::class,
        AdminTwoFactorNotifier::class => LaravelAdminTwoFactorNotifier::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->when(StartAdminLogin::class)->needs('$ttlMinutes')->give(
            static fn (): int => (int) config('security.admin_two_factor_ttl_minutes'),
        );
        $this->app->when(StartAdminLogin::class)->needs('$maxAttempts')->give(
            static fn (): int => (int) config('security.admin_two_factor_max_attempts'),
        );
    }

    public function boot(Gate $gate, AdminPermissionChecker $permissions): void
    {
        foreach (AdminPermission::cases() as $permission) {
            $gate->define(
                $permission->value,
                static fn (User $user): bool => $permissions->allows((int) $user->getAuthIdentifier(), $permission),
            );
        }
    }
}
