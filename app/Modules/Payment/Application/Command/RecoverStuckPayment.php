<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command;

use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Payment\Application\Port\PaymentRepository;
use App\Modules\Payment\Domain\PaymentStatus;
use App\Modules\Shared\Application\Port\TransactionManager;

final readonly class RecoverStuckPayment
{
    public function __construct(
        private OrderRepository $orders,
        private PaymentRepository $payments,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $orderNumber): bool
    {
        return $this->transactions->run(function () use ($orderNumber): bool {
            $order = $this->orders->findByNumber($orderNumber);
            if ($order === null) {
                return false;
            }
            $payment = $this->payments->findByOrder($order->id, true);
            if ($payment === null || $payment->status() !== PaymentStatus::Processing || $payment->providerPaymentId() !== null) {
                return false;
            }

            $payment->retryAfterFailure('manual_recovery');
            $this->payments->save($payment, 'manual_recovery');

            return true;
        });
    }
}
