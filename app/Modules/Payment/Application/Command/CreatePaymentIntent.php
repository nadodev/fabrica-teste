<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Domain\Payment;
use Ramsey\Uuid\Uuid;

final readonly class CreatePaymentIntent
{
    public function __construct(private PaymentRepository $payments) {}

    public function handle(string $orderId, int $amount, string $currency, string $method, bool $stockReserved): Payment
    {
        $existing = $this->payments->findByOrder($orderId);
        if ($existing !== null) {
            return $existing;
        }

        $payment = new Payment(
            Uuid::uuid5(Uuid::NAMESPACE_URL, 'payment:'.$orderId)->toString(),
            $orderId,
            $amount,
            $currency,
            $method,
            Uuid::uuid5(Uuid::NAMESPACE_URL, 'payment-charge:'.$orderId)->toString(),
            $stockReserved,
        );
        $this->payments->save($payment, 'checkout');

        return $payment;
    }
}
