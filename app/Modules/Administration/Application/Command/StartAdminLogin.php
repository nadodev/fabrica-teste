<?php

declare(strict_types=1);

namespace App\Modules\Administration\Application\Command;

use App\Modules\Administration\Application\DTO\AdminAuditEntry;
use App\Modules\Administration\Application\DTO\AdminLoginContext;
use App\Modules\Administration\Application\DTO\StartedAdminLogin;
use App\Modules\Administration\Application\Port\AdminAuditRecorder;
use App\Modules\Administration\Application\Port\AdminChallengeCodeGenerator;
use App\Modules\Administration\Application\Port\AdminChallengeCodeHasher;
use App\Modules\Administration\Application\Port\AdminCredentialVerifier;
use App\Modules\Administration\Application\Port\AdminLoginChallengeRepository;
use App\Modules\Administration\Application\Port\AdminTwoFactorNotifier;
use App\Modules\Administration\Domain\AdminLoginChallenge;
use App\Modules\Shared\Application\Port\TransactionManager;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final readonly class StartAdminLogin
{
    public function __construct(
        private AdminCredentialVerifier $credentials,
        private AdminLoginChallengeRepository $challenges,
        private AdminChallengeCodeGenerator $codes,
        private AdminChallengeCodeHasher $hasher,
        private AdminTwoFactorNotifier $notifier,
        private AdminAuditRecorder $audit,
        private TransactionManager $transactions,
        private int $ttlMinutes,
        private int $maxAttempts,
    ) {
        if ($ttlMinutes < 1 || $ttlMinutes > 60 || $maxAttempts < 1 || $maxAttempts > 10) {
            throw new InvalidArgumentException('Configuração inválida do segundo fator administrativo.');
        }
    }

    public function handle(
        string $email,
        string $password,
        bool $remember,
        AdminLoginContext $context,
    ): StartedAdminLogin {
        $identity = $this->credentials->verify(mb_strtolower(trim($email)), $password);

        if ($identity === null || ! $identity->administrator || ! $identity->emailVerified) {
            $this->recordAudit(null, 'rejected', 422, $context);
            throw new DomainException('As credenciais informadas são inválidas ou a conta não está habilitada.');
        }

        $now = new DateTimeImmutable;
        $expiresAt = $now->modify(sprintf('+%d minutes', $this->ttlMinutes));
        $challengeId = $this->codes->challengeId();
        $plainCode = $this->codes->plainCode();
        $challenge = new AdminLoginChallenge(
            $challengeId,
            $identity->userId,
            $this->hasher->hash($challengeId, $plainCode),
            $remember,
            $expiresAt,
            0,
            $this->maxAttempts,
        );

        $this->transactions->run(function () use ($identity, $challenge, $now): void {
            $this->challenges->invalidateOutstandingForUser($identity->userId, $now);
            $this->challenges->add($challenge);
        });

        try {
            $this->notifier->send($identity->userId, $plainCode, $expiresAt);
        } catch (Throwable) {
            $this->transactions->run(fn () => $this->challenges->invalidate($challengeId, new DateTimeImmutable));
            $this->recordAudit($identity->userId, 'rejected', 503, $context);
            throw new DomainException('Não foi possível enviar o código de acesso. Tente novamente em instantes.');
        }

        $this->recordAudit($identity->userId, 'completed', 200, $context);

        return new StartedAdminLogin($challengeId, $expiresAt);
    }

    private function recordAudit(?int $userId, string $outcome, int $status, AdminLoginContext $context): void
    {
        $this->audit->record(new AdminAuditEntry(
            $userId,
            'admin.login.challenge.start',
            'admin_login_challenge',
            null,
            $outcome,
            $status,
            $context->ipHash,
            $context->userAgent,
        ));
    }
}
