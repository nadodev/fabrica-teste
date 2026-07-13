<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query;

use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Payment\Application\DTO\CheckoutSuccessView;
use App\Modules\Payment\Application\Port\PaymentInstructionStore;
use App\Modules\Payment\Application\Port\PaymentRepository;

final readonly class ShowCheckoutSuccess
{
    public function __construct(
        private OrderRepository $orders,
        private PaymentRepository $payments,
        private PaymentInstructionStore $instructions,
    ) {}

    public function handle(string $orderNumber, ?string $sessionOrderId, ?int $userId): ?CheckoutSuccessView
    {
        $order = $this->orders->findByNumber($orderNumber);
        if ($order === null || ($sessionOrderId !== $order->id && ($userId === null || $order->customerUserId !== $userId))) {
            return null;
        }

        $payment = $this->payments->findByOrder($order->id);

        return new CheckoutSuccessView(
            $order->number,
            $order->details()->checkoutType,
            $order->details()->paymentMethod,
            $payment?->status()->value ?? $order->details()->paymentStatus,
            $payment?->failureCode(),
            $payment === null ? null : $this->instructions->find($payment->id),
        );
    }
}
