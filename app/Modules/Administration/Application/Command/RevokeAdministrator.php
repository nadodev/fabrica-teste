<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Command;

use App\Modules\Administration\Application\Port\AdministratorRepository;
use App\Modules\Shared\Application\Port\TransactionManager;
use DomainException;

final readonly class RevokeAdministrator
{
    public function __construct(
        private AdministratorRepository $administrators,
        private TransactionManager $transactions,
    ) {}

    public function handle(int $actorUserId, int $targetUserId): void
    {
        if ($actorUserId === $targetUserId) {
            throw new DomainException('Você não pode remover o próprio acesso.');
        }

        $target = $this->administrators->findById($targetUserId)
            ?? throw new DomainException('Administrador não encontrado.');
        if (! $target->administrator) {
            return;
        }
        if ($target->superAdministrator) {
            throw new DomainException('O proprietário não pode ter o acesso removido.');
        }

        $this->transactions->run(fn () => $this->administrators->revoke($targetUserId));
    }
}
