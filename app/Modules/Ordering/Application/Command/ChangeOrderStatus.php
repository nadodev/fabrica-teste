<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Command;

use App\Modules\Ordering\Application\Port\OrderStatusHistoryRecorder;
use App\Modules\Ordering\Domain\OrderStatus;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Shared\Application\Port\TransactionManager;
use DomainException;

final readonly class ChangeOrderStatus
{
    public function __construct(
        private OrderRepository $orders,
        private OrderStatusHistoryRecorder $history,
        private TransactionManager $transactions,
    ) {}

    public function handle(string $orderId, string $status, int $adminUserId, ?string $note = null): void
    {
        $this->transactions->run(function () use ($orderId, $status, $adminUserId, $note): void {
            $order = $this->orders->findForUpdate($orderId)
                ?? throw new DomainException('Pedido nao encontrado.');
            $from = $order->status();
            $to = OrderStatus::from($status);
            $order->changeAdministrativeStatus($to);

            if ($from === $to) {
                return;
            }

            $this->orders->save($order);
            $this->history->record($orderId, $from, $to, $adminUserId, $note);
        });
    }
}
