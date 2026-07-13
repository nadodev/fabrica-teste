<?php

use App\Modules\Payment\Domain\Payment;
use App\Modules\Payment\Domain\PaymentStatus;

function paidPayment(): Payment
{
    $payment = new Payment('payment-1', 'order-1', 10000, 'BRL', 'pix', 'key-1', true);
    $payment->start();
    $payment->approve('pay_1');

    return $payment;
}

it('keeps cumulative partial refunds monotonic and completes the full refund', function () {
    $payment = paidPayment();

    $payment->partiallyRefund(2500);
    expect($payment->status())->toBe(PaymentStatus::PartiallyRefunded)
        ->and($payment->refundedAmount())->toBe(2500)
        ->and(fn () => $payment->partiallyRefund(2000))->toThrow(DomainException::class);

    $payment->partiallyRefund(5000);
    $payment->refund();

    expect($payment->status())->toBe(PaymentStatus::Refunded)
        ->and($payment->refundedAmount())->toBe(10000);
});

it('records a chargeback idempotently and accepts a provider reversal', function () {
    $payment = paidPayment();

    $payment->markChargeback('requested');
    $version = $payment->version();
    $payment->markChargeback('requested');

    expect($payment->status())->toBe(PaymentStatus::Chargeback)
        ->and($payment->failureCode())->toBe('requested')
        ->and($payment->version())->toBe($version);

    $payment->approveFromProvider('pay_1');
    expect($payment->status())->toBe(PaymentStatus::Paid)
        ->and($payment->failureCode())->toBeNull();
});
