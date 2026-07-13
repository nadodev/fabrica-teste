<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Payment\Application\Port\PaymentGateway;
use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Domain\Payment;
use App\Modules\Payment\Domain\PaymentStatus;
use App\Modules\Shared\Application\Port\TransactionManager;
use DomainException;
use Ramsey\Uuid\Uuid;

final readonly class RefundPayment
{
    public function __construct(
        private PaymentRepository $payments,
        private PaymentGateway $gateway,
        private OrderRepository $orders,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $orderId): Payment
    {
        $payment = $this->payments->findByOrder($orderId) ?? throw new DomainException('Payment not found.');
        if ($payment->status() === PaymentStatus::Refunded) {
            return $payment;
        }
        $providerId = $payment->providerPaymentId() ?? throw new DomainException('Paid provider transaction not found.');
        $attemptId = $this->payments->startAttempt($payment, 'refund');
        $key = Uuid::uuid5(Uuid::NAMESPACE_URL, 'payment-refund:'.$payment->id)->toString();
        $result = $this->gateway->refund($providerId, $key, $payment->amount);

        $this->transactions->run(function () use ($payment, $orderId, $attemptId, $result): void {
            $order = $this->orders->find($orderId) ?? throw new DomainException('Order not found.');
            $payment->refund();
            $order->markRefunded();
            $this->payments->save($payment, 'refund');
            $this->orders->save($order);
            $this->payments->finishAttempt($attemptId, 'succeeded', $result->transactionId, 'refunded');
        });

        return $payment;
    }
}
