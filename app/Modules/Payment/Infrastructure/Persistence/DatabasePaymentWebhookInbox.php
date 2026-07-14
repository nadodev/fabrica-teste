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

    public function retry(string $id, string $error): void
    {
        $this->database->table('payment_webhook_events')->where('id', $id)->update(['status' => 'pending', 'available_at' => now()->addMinutes(5), 'last_error' => mb_substr($error, 0, 2000), 'updated_at' => now()]);
    }
}
