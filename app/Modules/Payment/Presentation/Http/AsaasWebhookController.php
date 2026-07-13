<?php

declare(strict_types=1);

namespace App\Modules\Payment\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Application\Command\ReceiveAsaasWebhook;
use App\Modules\Payment\Presentation\Http\Request\AsaasWebhookRequest;
use Illuminate\Http\JsonResponse;

final class AsaasWebhookController extends Controller
{
    public function __invoke(AsaasWebhookRequest $request, ReceiveAsaasWebhook $receiver): JsonResponse
    {
        $data = $request->validated();
        $payment = $data['payment'];
        $safe = array_intersect_key($payment, array_flip(['id', 'status', 'billingType', 'externalReference', 'value', 'refundedValue']));
        $chargeback = is_array($payment['chargeback'] ?? null) ? $payment['chargeback'] : [];
        if (is_string($chargeback['status'] ?? null)) {
            $safe['chargebackStatus'] = $chargeback['status'];
        }
        if (is_string($chargeback['reason'] ?? null)) {
            $safe['chargebackReason'] = $chargeback['reason'];
        }
        $receiver->handle((string) $data['id'], (string) $data['event'], (string) $payment['id'], $safe);

        return response()->json(['received' => true]);
    }
}
