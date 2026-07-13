<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final class ShippingToken
{
    public function encode(string $token): string
    {
        return Crypt::encryptString($this->normalize($token));
    }

    public function decode(mixed $stored): string
    {
        if (! is_string($stored) || trim($stored) === '') {
            return '';
        }

        try {
            return $this->normalize(Crypt::decryptString($stored));
        } catch (DecryptException) {
            return $this->normalize($stored);
        }
    }

    public function preview(mixed $stored): ?string
    {
        $token = $this->decode($stored);

        return $token === '' ? null : 'salvo, termina em '.substr($token, -6);
    }

    private function normalize(string $token): string
    {
        return trim((string) preg_replace('/^Bearer\s+/i', '', trim($token)));
    }
}
