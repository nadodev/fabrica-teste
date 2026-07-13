<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Notification;

use App\Mail\OrderPlacedMail;
use App\Modules\Ordering\Application\Port\OrderNotificationGateway;
use App\Support\StoreSettings;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

final readonly class MailOrderNotificationGateway implements OrderNotificationGateway
{
    public function __construct(private ConnectionInterface $database, private StoreSettings $settings) {}

    public function sendPlaced(string $orderId): void
    {
        $order = $this->database->table('ordering_orders')->where('id', $orderId)->first();
        if ($order === null) {
            throw new RuntimeException('Order referenced by outbox message was not found.');
        }

        $emailSettings = $this->settings->emails();
        $isQuote = (string) $order->checkout_type === 'quote';
        if (($isQuote && ! (bool) ($emailSettings['notifyQuote'] ?? true)) || (! $isQuote && ! (bool) ($emailSettings['notifyNewOrder'] ?? true))) {
            return;
        }

        $items = $this->database->table('ordering_order_items')->where('order_id', $orderId)->orderBy('id')->get();
        $payload = [
            'number' => (string) $order->number,
            'checkoutType' => (string) $order->checkout_type,
            'customerName' => (string) $order->customer_name,
            'couponCode' => $order->coupon_code,
            'total' => $this->formatMoney((int) $order->total_amount, (string) $order->currency),
            'items' => $items->map(fn (object $item): array => [
                'name' => (string) $item->name,
                'variationLabel' => $item->variation_label,
                'notes' => $item->notes,
                'quantity' => (int) $item->quantity,
                'subtotal' => $this->formatMoney((int) $item->subtotal_amount, (string) $item->price_currency),
            ])->all(),
        ];

        if (filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
            Mail::to((string) $order->customer_email)->send(new OrderPlacedMail($payload));
        }

        $recipients = preg_split('/[,;\s]+/', (string) ($emailSettings['adminRecipients'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $adminEmail = config('admin.email');
        if ($recipients === [] && is_string($adminEmail)) {
            $recipients = [$adminEmail];
        }

        foreach (array_unique($recipients) as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                Mail::to($recipient)->send(new OrderPlacedMail($payload, true));
            }
        }
    }

    private function formatMoney(int $amount, string $currency): string
    {
        return number_format($amount / 100, 2, ',', '.').' '.$currency;
    }
}
