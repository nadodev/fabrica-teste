<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Command;

use App\Modules\Administration\Application\Port\AdministratorRepository;
use App\Modules\Administration\Application\Port\AdminPermissionChecker;
use App\Modules\Administration\Domain\AdminPermission;
use App\Modules\Administration\Domain\AdminPermissionSet;
use App\Modules\Shared\Application\Port\TransactionManager;
use DomainException;

final readonly class UpdateAdministratorPermissions
{
    public function __construct(
        private AdministratorRepository $administrators,
        private AdminPermissionChecker $permissions,
        private TransactionManager $transactions,
    ) {}

    /** @param list<string> $permissionValues */
    public function handle(int $actorUserId, int $targetUserId, array $permissionValues): void
    {
        if ($actorUserId === $targetUserId) {
            throw new DomainException('Você não pode alterar as próprias permissões.');
        }

        $target = $this->administrators->findById($targetUserId)
            ?? throw new DomainException('Administrador não encontrado.');
        if (! $target->administrator) {
            throw new DomainException('A conta não possui acesso administrativo.');
        }
        if ($target->superAdministrator) {
            throw new DomainException('O proprietário não pode ser alterado por esta tela.');
        }

        $requested = AdminPermissionSet::fromValues($permissionValues);
        $this->ensureActorCanGrant($actorUserId, $requested);
        $this->transactions->run(fn () => $this->administrators->grant($targetUserId, $requested, $actorUserId));
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
