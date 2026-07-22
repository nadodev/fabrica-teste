<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Administration\Application\DTO\AdminAuditEntry;
use App\Modules\Administration\Application\Port\AdminAuditRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class AuditAdministrativeMutation
{
    public function __construct(private AdminAuditRecorder $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        [$subjectType, $subjectId] = $this->subject($request);
        $entry = new AdminAuditEntry(
            $request->user() === null ? null : (int) $request->user()->getAuthIdentifier(),
            (string) ($request->route()?->getName() ?? 'admin.unknown'),
            $subjectType,
            $subjectId,
            'attempted',
            null,
            $this->ipHash($request),
            $request->userAgent(),
            ['method' => $request->method()],
        );
        $this->audit->record($entry);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->recordOutcome($entry, 'rejected', 500);

            throw $exception;
        }

        $this->recordOutcome(
            $entry,
            $response->getStatusCode() >= 400 || $request->session()->has('errors') ? 'rejected' : 'completed',
            $response->getStatusCode(),
        );

        return $response;
    }

    private function recordOutcome(AdminAuditEntry $entry, string $outcome, int $httpStatus): void
    {
        $this->audit->record(new AdminAuditEntry(
            $entry->actorUserId,
            $entry->action,
            $entry->subjectType,
            $entry->subjectId,
            $outcome,
            $httpStatus,
            $entry->ipHash,
            $entry->userAgent,
            $entry->metadata,
        ));
    }

    /** @return array{string|null, string|null} */
    private function subject(Request $request): array
    {
        foreach ($request->route()?->parameters() ?? [] as $name => $value) {
            if (is_scalar($value)) {
                return [(string) $name, (string) $value];
            }
        }

        return [null, null];
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip === null ? null : hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
