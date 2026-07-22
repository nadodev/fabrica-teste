<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Command;

use App\Modules\Administration\Application\Port\AdministratorRepository;
use App\Modules\Administration\Application\Port\AdminPermissionChecker;
use App\Modules\Administration\Domain\AdminPermission;
use App\Modules\Administration\Domain\AdminPermissionSet;
use App\Modules\Shared\Application\Port\TransactionManager;
use DomainException;

final readonly class PromoteAdministrator
{
    public function __construct(
        private AdministratorRepository $administrators,
        private AdminPermissionChecker $permissions,
        private TransactionManager $transactions,
    ) {}

    /** @param list<string> $permissionValues */
    public function handle(int $actorUserId, string $email, array $permissionValues): void
    {
        $requested = AdminPermissionSet::fromValues($permissionValues);
        $this->ensureActorCanGrant($actorUserId, $requested);

        $candidate = $this->administrators->findByEmail(mb_strtolower(trim($email)))
            ?? throw new DomainException('Cadastre o cliente antes de conceder acesso administrativo.');

        if (! $candidate->emailVerified) {
            throw new DomainException('O cliente precisa confirmar o e-mail antes de receber acesso administrativo.');
        }
        if ($candidate->administrator) {
            throw new DomainException('Esta conta já possui acesso administrativo.');
        }

        $this->transactions->run(fn () => $this->administrators->grant($candidate->id, $requested, $actorUserId));
    }

    /** @param list<AdminPermission> $requested */
    private function ensureActorCanGrant(int $actorUserId, array $requested): void
    {
        foreach ($requested as $permission) {
            if (! $this->permissions->allows($actorUserId, $permission)) {
                throw new DomainException('Você não pode conceder uma permissão que não possui.');
            }
        }
    }
}
