<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\Cart\Application\DTO\CartView;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class MelhorEnvioClient
{
    /** @return list<array<string, int|string>> */
    public function quote(string $destinationZip, CartView $cart): array
    {
        $settings = DB::table('shipping_settings')->where('id', 1)->first();

        if ($settings === null || ! (bool) $settings->is_enabled) {
            throw new RuntimeException('Configure e ative o Melhor Envio no painel administrativo.');
        }

        $token = trim((string) ($settings->token ?? ''));
        $originZip = $this->onlyDigits((string) ($settings->origin_zip ?? ''));

        if ($token === '' || $originZip === '') {
            throw new RuntimeException('Informe o token e o CEP de origem do Melhor Envio.');
        }

        $endpoint = ((string) $settings->environment === 'production')
            ? 'https://www.melhorenvio.com.br/api/v2/me/shipment/calculate'
            : 'https://sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate';

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['User-Agent' => config('app.name', 'Uniform Crafted')])
            ->timeout(20)
            ->post($endpoint, [
                'from' => ['postal_code' => $originZip],
                'to' => ['postal_code' => $this->onlyDigits($destinationZip)],
                'products' => $this->productsPayload($cart),
                'services' => '',
                'options' => [
                    'receipt' => false,
                    'own_hand' => false,
                    'insurance_value' => $this->decimal($cart->subtotalAmount),
                ],
            ]);

        if ($response->failed()) {
            Log::warning('Melhor Envio quote failed', [
                'status' => $response->status(),
                'environment' => (string) $settings->environment,
                'origin_zip' => $originZip,
                'destination_zip' => $this->onlyDigits($destinationZip),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException($this->errorMessage($response->json(), $response->status()));
        }

        $body = $response->json();
        $quotes = [];
        foreach (is_array($body) ? $body : [] as $row) {
            if (! is_array($row) || isset($row['error']) || ! isset($row['price'])) {
                continue;
            }
            $quote = [
                'serviceId' => (string) ($row['id'] ?? $row['name'] ?? ''),
                'name' => (string) ($row['name'] ?? 'Frete'),
                'companyName' => (string) data_get($row, 'company.name', 'Transportadora'),
                'priceAmount' => $this->moneyToCents((string) ($row['custom_price'] ?? $row['price'])),
                'deliveryTime' => (int) ($row['custom_delivery_time'] ?? $row['delivery_time'] ?? 0),
            ];
            if ($quote['serviceId'] !== '' && $quote['priceAmount'] > 0) {
                $quotes[] = $quote;
            }
        }

        if ($quotes === []) {
            Log::warning('Melhor Envio quote returned no available services', [
                'environment' => (string) $settings->environment,
                'origin_zip' => $originZip,
                'destination_zip' => $this->onlyDigits($destinationZip),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('Nenhuma opcao de frete foi encontrada para esse CEP.');
        }

        return $quotes;
    }

    /** @return list<array<string, int|float|string>> */
    private function productsPayload(CartView $cart): array
    {
        return array_map(fn (array $item): array => [
            'id' => (string) $item['cartItemKey'],
            'width' => 20,
            'height' => 5,
            'length' => 30,
            'weight' => 0.3,
            'insurance_value' => $this->decimal((int) $item['unitPriceAmount']),
            'quantity' => (int) $item['quantity'],
        ], $cart->items);
    }

    private function moneyToCents(string $value): int
    {
        return (int) round(((float) str_replace(',', '.', $value)) * 100);
    }

    private function decimal(int $amount): float
    {
        return round($amount / 100, 2);
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function errorMessage(mixed $body, int $status): string
    {
        if ($status === 401 || $status === 403) {
            return 'Melhor Envio recusou o token. Confira se o token pertence ao ambiente selecionado: sandbox ou producao.';
        }

        $message = data_get($body, 'message') ?? data_get($body, 'error') ?? data_get($body, 'errors.0');

        if (is_array($message)) {
            $message = implode(' ', array_map('strval', $message));
        }

        if (is_string($message) && trim($message) !== '') {
            return 'Melhor Envio: '.trim($message);
        }

        return 'Nao foi possivel consultar o Melhor Envio agora. Veja o log da aplicacao para o retorno completo da API.';
    }
}
