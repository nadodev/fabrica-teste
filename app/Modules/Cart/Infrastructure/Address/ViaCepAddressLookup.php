<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Address;

use App\Modules\Cart\Application\DTO\PostalAddress;
use App\Modules\Cart\Application\Port\PostalAddressLookup;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ViaCepAddressLookup implements PostalAddressLookup
{
    public function find(string $postalCode): ?PostalAddress
    {
        $key = 'postal-address:viacep:'.$postalCode;
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $this->fromPayload($postalCode, $cached);
        }

        try {
            $response = Http::acceptJson()->timeout(5)->get('https://viacep.com.br/ws/'.$postalCode.'/json/');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('O servico de CEP esta temporariamente indisponivel.', previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException('O servico de CEP esta temporariamente indisponivel.');
        }

        $payload = $response->json();
        if (! is_array($payload) || ($payload['erro'] ?? false) === true) {
            return null;
        }

        Cache::put($key, $payload, now()->addDay());

        return $this->fromPayload($postalCode, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function fromPayload(string $postalCode, array $payload): PostalAddress
    {
        return new PostalAddress(
            $postalCode,
            trim((string) ($payload['logradouro'] ?? '')),
            trim((string) ($payload['bairro'] ?? '')),
            trim((string) ($payload['localidade'] ?? '')),
            mb_strtoupper(trim((string) ($payload['uf'] ?? ''))),
        );
    }
}
