<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Http\Middleware;

use App\Modules\Shared\Application\Idempotency\IdempotencyOutcome;
use App\Modules\Shared\Application\Port\IdempotencyStore;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class EnsureIdempotency
{
    public function __construct(private IdempotencyStore $store) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key'));

        if ($key === '' || strlen($key) > 128) {
            return new JsonResponse(['message' => 'A valid Idempotency-Key header is required.'], 422);
        }

        $scope = $this->scope($request);
        $fingerprint = hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());
        $claim = $this->store->claim($scope, $key, $fingerprint, 86400);

        if ($claim->outcome === IdempotencyOutcome::Conflict) {
            return new JsonResponse(['message' => 'Idempotency key was already used with a different request.'], 409);
        }

        if ($claim->outcome === IdempotencyOutcome::InProgress) {
            return new JsonResponse(['message' => 'A request with this idempotency key is still processing.'], 409, ['Retry-After' => '2']);
        }

        if ($claim->outcome === IdempotencyOutcome::Replay) {
            return new Response($claim->body, $claim->responseCode ?? 200, $claim->headers + ['Idempotent-Replayed' => 'true']);
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->store->release($scope, $key);
            throw $exception;
        }

        if ($response->getStatusCode() >= 500) {
            $this->store->release($scope, $key);

            return $response;
        }

        $headers = ['Content-Type' => (string) $response->headers->get('Content-Type', 'application/json')];
        $this->store->complete($scope, $key, $response->getStatusCode(), $headers, (string) $response->getContent());

        return $response;
    }

    private function scope(Request $request): string
    {
        $actor = $request->user() === null
            ? 'anonymous'
            : 'user:'.$request->user()->getAuthIdentifier();
        $operation = $request->route()?->getName() ?? $request->path();

        return hash('sha256', $actor.'|'.$operation);
    }
}
