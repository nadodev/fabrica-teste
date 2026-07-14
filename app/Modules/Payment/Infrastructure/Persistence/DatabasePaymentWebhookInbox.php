<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Application\DTO\ProviderWebhookEvent;
use App\Modules\Payment\Application\Port\PaymentWebhookInbox;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabasePaymentWebhookInbox implements PaymentWebhookInbox
{
    public function __construct(private ConnectionInterface $database) {}

    public function receive(ProviderWebhookEvent $event): void
    {
        $this->database->table('payment_webhook_events')->insertOrIgnore([
            'id' => $event->id,
            'event' => $event->event,
            'provider_payment_id' => $event->providerPaymentId,
            'payload' => json_encode($event->payment, JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function claim(?string $id = null): ?ProviderWebhookEvent
    {
        return $this->database->transaction(function () use ($id): ?ProviderWebhookEvent {
            $query = $this->database->table('payment_webhook_events')
                ->where('status', 'pending')
                ->where('available_at', '<=', now());
            if ($id !== null) {
                $query->where('id', $id);
            } else {
                $query->orderBy('created_at');
            }
            $record = $query->lockForUpdate()->first();
            if ($record === null) {
                return null;
            }
            $this->database->table('payment_webhook_events')->where('id', $record->id)->update(['status' => 'processing', 'attempts' => (int) $record->attempts + 1, 'updated_at' => now()]);
            $payload = json_decode((string) $record->payload, true, 512, JSON_THROW_ON_ERROR);

            return new ProviderWebhookEvent((string) $record->id, (string) $record->event, (string) $record->provider_payment_id, is_array($payload) ? $payload : []);
        }, 3);
    }

    public function processed(string $id): void
    {
        $this->database->table('payment_webhook_events')->where('id', $id)->update(['status' => 'processed', 'processed_at' => now(), 'last_error' => null, 'updated_at' => now()]);
    }

    public function retry(string $id, string $error, int $maxAttempts): void
    {
        $attempts = (int) $this->database->table('payment_webhook_events')->where('id', $id)->value('attempts');
        $failed = $attempts >= max(1, $maxAttempts);
        $this->database->table('payment_webhook_events')->where('id', $id)->update([
            'status' => $failed ? 'failed' : 'pending',
            'available_at' => $failed ? now() : now()->addMinutes(5),
            'last_error' => mb_substr($error, 0, 2000),
            'updated_at' => now(),
        ]);
    }

    public function recoverStale(int $staleAfterMinutes, int $maxAttempts): int
    {
        return $this->database->transaction(function () use ($staleAfterMinutes, $maxAttempts): int {
            $records = $this->database->table('payment_webhook_events')
                ->where('status', 'processing')
                ->where('updated_at', '<=', now()->subMinutes(max(1, $staleAfterMinutes)))
                ->lockForUpdate()
                ->get(['id', 'attempts']);
            foreach ($records as $record) {
                $failed = (int) $record->attempts >= max(1, $maxAttempts);
                $this->database->table('payment_webhook_events')->where('id', $record->id)->update([
                    'status' => $failed ? 'failed' : 'pending',
                    'available_at' => now(),
                    'last_error' => $failed ? 'Webhook excedeu o limite de tentativas apos processamento interrompido.' : 'Processamento anterior foi interrompido e o evento foi recuperado.',
                    'updated_at' => now(),
                ]);
            }

            return $records->count();
        }, 3);
    }

    public function statusCounts(): array
    {
        $counts = $this->database->table('payment_webhook_events')
            ->whereIn('status', ['pending', 'processing', 'failed'])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'processing' => (int) ($counts['processing'] ?? 0),
            'failed' => (int) ($counts['failed'] ?? 0),
        ];
    }
}
