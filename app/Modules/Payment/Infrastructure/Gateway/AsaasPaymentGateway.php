<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Gateway;

use App\Modules\Payment\Application\DTO\PaymentRequest;
use App\Modules\Payment\Application\DTO\PaymentResult;
use App\Modules\Payment\Application\DTO\ProviderPaymentSnapshot;
use App\Modules\Payment\Application\Exception\PaymentCardDeclined;
use App\Modules\Payment\Application\Exception\PaymentGatewayTimeout;
use App\Modules\Payment\Application\Port\PaymentGateway;
use App\Modules\Payment\Application\Port\PaymentReconciliationGateway;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class AsaasPaymentGateway implements PaymentGateway, PaymentReconciliationGateway
{
    public function __construct(private ConnectionInterface $database) {}

    public function charge(#[\SensitiveParameter] PaymentRequest $request): PaymentResult
    {
        $this->ensureLiveEnabled();

        try {
            $existing = $this->client()->get('/payments', ['externalReference' => $request->orderId])->throw()->json('data.0');
            if (is_array($existing) && is_string($existing['id'] ?? null)) {
                return $this->resultWithInstructions($existing, $request->methodToken);
            }

            $customerId = $this->customerId($request);
            $body = [
                'customer' => $customerId,
                'billingType' => $this->billingType($request->methodToken),
                'value' => $request->amount / 100,
                'dueDate' => now()->addDays(max(1, (int) config('payment.asaas.due_days', 3)))->toDateString(),
                'description' => 'Pedido '.$request->orderId,
                'externalReference' => $request->orderId,
            ];
            if ($request->methodToken === 'credit_card') {
                if ($request->creditCard === null) {
                    throw new RuntimeException('Os dados do cartao sao necessarios para processar o pagamento.');
                }
                $document = preg_replace('/\D/', '', (string) ($request->customer['document'] ?? '')) ?? '';
                $phone = preg_replace('/\D/', '', (string) ($request->customer['phone'] ?? '')) ?? '';
                $body['creditCard'] = [
                    'holderName' => $request->creditCard->holderName,
                    'number' => $request->creditCard->number,
                    'expiryMonth' => $request->creditCard->expiryMonth,
                    'expiryYear' => $request->creditCard->expiryYear,
                    'ccv' => $request->creditCard->ccv,
                ];
                $body['creditCardHolderInfo'] = [
                    'name' => (string) ($request->customer['name'] ?? ''),
                    'email' => (string) ($request->customer['email'] ?? ''),
                    'cpfCnpj' => $document,
                    'postalCode' => preg_replace('/\D/', '', (string) ($request->customer['postalCode'] ?? '')),
                    'addressNumber' => (string) ($request->customer['addressNumber'] ?? ''),
                    'phone' => $phone,
                    'mobilePhone' => $phone,
                ];
                $body['remoteIp'] = $request->creditCard->remoteIp;
            }
            $response = $this->client()->post('/payments', $body)->throw()->json();
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayTimeout(
                'Asaas connection timed out.',
                previous: $request->methodToken === 'credit_card' ? null : $exception,
            );
        } catch (RequestException $exception) {
            if ($request->methodToken === 'credit_card' && $exception->response->status() === 400) {
                throw new PaymentCardDeclined('O cartao nao foi autorizado pelo provedor de pagamento.');
            }

            throw new RuntimeException(
                'O Asaas recusou a criacao da cobranca.',
                previous: $request->methodToken === 'credit_card' ? null : $exception,
            );
        }

        if (! is_array($response)) {
            throw new RuntimeException('Asaas returned an invalid payment response.');
        }

        return $this->resultWithInstructions($response, $request->methodToken);
    }

    public function refund(string $transactionId, string $idempotencyKey, ?int $amount = null): PaymentResult
    {
        $this->ensureLiveEnabled();

        try {
            $body = $amount === null ? [] : ['value' => $amount / 100, 'description' => 'Estorno da loja '.$idempotencyKey];
            $response = $this->client()->post('/payments/'.$transactionId.'/refund', $body)->throw()->json();
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayTimeout('Asaas refund timed out.', previous: $exception);
        }

        if (! is_array($response)) {
            throw new RuntimeException('Asaas returned an invalid refund response.');
        }

        return new PaymentResult($transactionId, 'refunded');
    }

    public function fetch(string $providerPaymentId): ProviderPaymentSnapshot
    {
        $this->ensureLiveEnabled();

        try {
            $response = $this->client()->get('/payments/'.$providerPaymentId)->throw()->json();
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayTimeout('Asaas reconciliation timed out.', previous: $exception);
        }

        if (! is_array($response) || ! is_string($response['id'] ?? null)) {
            throw new RuntimeException('Asaas returned an invalid reconciliation response.');
        }

        $refundedAmount = 0;
        foreach (($response['refunds'] ?? []) as $refund) {
            if (is_array($refund) && ($refund['status'] ?? null) === 'DONE') {
                $refundedAmount += $this->cents($refund['value'] ?? 0);
            }
        }
        $chargeback = is_array($response['chargeback'] ?? null) ? $response['chargeback'] : [];

        return new ProviderPaymentSnapshot(
            $response['id'],
            (string) ($response['status'] ?? 'PENDING'),
            (string) ($response['billingType'] ?? ''),
            $refundedAmount,
            is_string($chargeback['status'] ?? null) ? $chargeback['status'] : null,
            is_string($chargeback['reason'] ?? null) ? $chargeback['reason'] : null,
        );
    }

    private function customerId(PaymentRequest $request): string
    {
        $document = preg_replace('/\D/', '', (string) ($request->customer['document'] ?? '')) ?? '';
        if ($document === '') {
            throw new RuntimeException('CPF ou CNPJ e obrigatorio para gerar cobranca no Asaas.');
        }
        $key = hash('sha256', $document);
        $stored = $this->database->table('payment_provider_customers')->where('customer_key', $key)->value('provider_customer_id');
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $response = $this->client()->post('/customers', [
            'name' => (string) ($request->customer['name'] ?? ''),
            'cpfCnpj' => $document,
            'email' => (string) ($request->customer['email'] ?? ''),
            'mobilePhone' => preg_replace('/\D/', '', (string) ($request->customer['phone'] ?? '')),
            'externalReference' => 'customer-'.$key,
            'notificationDisabled' => false,
        ])->throw()->json();
        $id = is_array($response) ? ($response['id'] ?? null) : null;
        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Asaas returned an invalid customer response.');
        }
        $this->database->table('payment_provider_customers')->insertOrIgnore([
            'customer_key' => $key,
            'provider' => 'asaas',
            'provider_customer_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function client(): PendingRequest
    {
        $key = trim((string) config('payment.asaas.api_key'));
        if (! str_starts_with($key, '$aact_prod_')) {
            throw new RuntimeException('A valid Asaas production API key is required.');
        }

        return Http::baseUrl(rtrim((string) config('payment.asaas.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withHeaders(['access_token' => $key, 'User-Agent' => 'UniformCrafted/1.0 (Laravel; production)'])
            ->timeout(65);
    }

    private function ensureLiveEnabled(): void
    {
        if (! (bool) config('payment.asaas.live_enabled')) {
            throw new RuntimeException('Asaas live charges are disabled.');
        }
    }

    private function billingType(string $method): string
    {
        return match ($method) {
            'pix' => 'PIX',
            'boleto' => 'BOLETO',
            'credit_card' => 'CREDIT_CARD',
            default => throw new RuntimeException('Unsupported Asaas payment method.'),
        };
    }

    private function cents(mixed $value): int
    {
        return max(0, (int) round(((float) $value) * 100));
    }

    /** @param array<string, mixed> $payload */
    private function result(array $payload): PaymentResult
    {
        $id = $payload['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Asaas payment ID is missing.');
        }
        $status = (string) ($payload['status'] ?? 'PENDING');
        $mapped = match ($status) {
            'RECEIVED', 'CONFIRMED' => 'approved',
            'REFUNDED', 'REFUND_REQUESTED' => 'refunded',
            'DELETED', 'CREDIT_CARD_REFUSED' => 'declined',
            default => 'pending',
        };
        $url = $payload['invoiceUrl'] ?? $payload['bankSlipUrl'] ?? null;

        return new PaymentResult($id, $mapped, is_string($url) ? $url : null);
    }

    /** @param array<string, mixed> $payload */
    private function resultWithInstructions(array $payload, string $method): PaymentResult
    {
        $result = $this->result($payload);
        if ($method !== 'pix') {
            return $result;
        }

        $pix = $this->client()->get('/payments/'.$result->transactionId.'/pixQrCode')->throw()->json();
        if (! is_array($pix) || ! is_string($pix['payload'] ?? null) || ! is_string($pix['encodedImage'] ?? null)) {
            throw new RuntimeException('Asaas returned invalid Pix payment instructions.');
        }

        return new PaymentResult(
            $result->transactionId,
            $result->status,
            $result->redirectUrl,
            $pix['payload'],
            $pix['encodedImage'],
            is_string($pix['expirationDate'] ?? null) ? $pix['expirationDate'] : null,
        );
    }
}
