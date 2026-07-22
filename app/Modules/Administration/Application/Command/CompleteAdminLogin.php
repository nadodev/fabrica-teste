<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Command;

use App\Modules\Administration\Application\DTO\AdminAuditEntry;
use App\Modules\Administration\Application\DTO\AdminChallengeAttempt;
use App\Modules\Administration\Application\DTO\AdminLoginContext;
use App\Modules\Administration\Application\DTO\CompletedAdminLogin;
use App\Modules\Administration\Application\Exception\AdminLoginChallengeFailed;
use App\Modules\Administration\Application\Port\AdminAuditRecorder;
use App\Modules\Administration\Application\Port\AdminChallengeCodeHasher;
use App\Modules\Administration\Application\Port\AdminCredentialVerifier;
use App\Modules\Administration\Application\Port\AdminLoginChallengeRepository;
use App\Modules\Administration\Domain\AdminChallengeStatus;
use App\Modules\Shared\Application\Port\TransactionManager;
use DateTimeImmutable;

final readonly class CompleteAdminLogin
{
    public function __construct(
        private AdminLoginChallengeRepository $challenges,
        private AdminChallengeCodeHasher $hasher,
        private AdminCredentialVerifier $credentials,
        private AdminAuditRecorder $audit,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $challengeId, string $plainCode, AdminLoginContext $context): CompletedAdminLogin
    {
        $attempt = $this->transactions->run(function () use ($challengeId, $plainCode): AdminChallengeAttempt {
            $challenge = $this->challenges->findForUpdate($challengeId);
            if ($challenge === null) {
                return new AdminChallengeAttempt(AdminChallengeStatus::Consumed, null, false);
            }

            $status = $challenge->verify(
                $this->hasher->hash($challenge->id, $plainCode),
                new DateTimeImmutable,
            );
            if ($status === AdminChallengeStatus::Success) {
                $identity = $this->credentials->findEligible($challenge->userId);
                if ($identity === null || ! $identity->administrator || ! $identity->emailVerified) {
                    $status = AdminChallengeStatus::Consumed;
                }
            }
            $this->challenges->save($challenge);

            return new AdminChallengeAttempt($status, $challenge->userId, $challenge->remember);
        });

        if ($attempt->status !== AdminChallengeStatus::Success || $attempt->userId === null) {
            $this->recordAudit($attempt->userId, 'rejected', 422, $context);
            throw $this->failureFor($attempt->status);
        }

        $this->recordAudit($attempt->userId, 'completed', 200, $context);

        return new CompletedAdminLogin($attempt->userId, $attempt->remember);
    }

    private function failureFor(AdminChallengeStatus $status): AdminLoginChallengeFailed
    {
        return match ($status) {
            AdminChallengeStatus::Invalid => new AdminLoginChallengeFailed('O código informado é inválido.', false),
            AdminChallengeStatus::Expired => new AdminLoginChallengeFailed('O código expirou. Entre novamente para receber outro.', true),
            AdminChallengeStatus::Locked => new AdminLoginChallengeFailed('Limite de tentativas atingido. Entre novamente.', true),
            AdminChallengeStatus::Consumed, AdminChallengeStatus::Success => new AdminLoginChallengeFailed('Este código não está mais disponível. Entre novamente.', true),
        };
    }

    private function recordAudit(?int $userId, string $outcome, int $status, AdminLoginContext $context): void
    {
        $this->audit->record(new AdminAuditEntry(
            $userId,
            'admin.login.challenge.complete',
            'admin_login_challenge',
            null,
            $outcome,
            $status,
            $context->ipHash,
            $context->userAgent,
        ));
    }
}
