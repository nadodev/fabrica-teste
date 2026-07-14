<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdminOrderStatusRequest;
use App\Modules\Ordering\Application\Command\ChangeOrderStatus;
use App\Modules\Ordering\Application\Port\AdminOrderReadModel;
use App\Modules\Ordering\Domain\OrderStatus;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class AdminOrderController extends Controller
{
    public function index(AdminOrderReadModel $orders): Response
    {
        $records = array_map(fn (array $order): array => [
            ...$order,
            'allowedStatuses' => $this->statusOptions(OrderStatus::from((string) $order['status'])),
        ], $orders->all());

        return Inertia::render('admin/orders/index', [
            'orders' => $records,
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(string $order, AdminOrderReadModel $orders): Response
    {
        $record = $orders->find($order);
        abort_if($record === null, 404);

        return Inertia::render('admin/orders/show', [
            'order' => $record,
            'statuses' => $this->statuses(),
            'allowedStatuses' => $this->statusOptions(OrderStatus::from((string) $record['status'])),
            'statusHistory' => $orders->statusHistory($order),
        ]);
    }

    public function updateStatus(string $order, UpdateAdminOrderStatusRequest $request, ChangeOrderStatus $command): RedirectResponse
    {
        $data = $request->validated();
        try {
            $command->handle(
                $order,
                (string) $data['status'],
                (int) $request->user()->getAuthIdentifier(),
                isset($data['note']) ? (string) $data['note'] : null,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Status do pedido atualizado.');
    }

    /** @return array<string, string> */
    private function statuses(): array
    {
        return [
            'quote_requested' => 'Orcamento',
            'awaiting_payment' => 'Novo',
            'paid' => 'Pago',
            'processing' => 'Em producao',
            'shipped' => 'Enviado',
            'delivered' => 'Entregue',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
        ];
    }

    /** @return array<string, string> */
    private function statusOptions(OrderStatus $current): array
    {
        $allowed = [$current, ...$current->allowedAdministrativeTransitions()];

        return array_intersect_key($this->statuses(), array_flip(array_map(fn (OrderStatus $status): string => $status->value, $allowed)));
    }
}
