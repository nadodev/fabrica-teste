<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CouponCalculator
{
    /** @return array{code: string, description: string, discountType: string, discountValue: int, discountAmount: int} */
    public function validDiscount(string $code, int $subtotalAmount): array
    {
        $normalized = $this->normalize($code);

        if ($normalized === '') {
            throw new RuntimeException('Informe um cupom valido.');
        }

        $coupon = DB::table('commerce_coupons')->where('code', $normalized)->first();

        if ($coupon === null || ! (bool) $coupon->is_active) {
            throw new RuntimeException('Cupom invalido ou inativo.');
        }

        $now = now();
        if ($coupon->starts_at !== null && $now->lt($coupon->starts_at)) {
            throw new RuntimeException('Cupom ainda nao esta disponivel.');
        }

        if ($coupon->ends_at !== null && $now->gt($coupon->ends_at)) {
            throw new RuntimeException('Cupom expirado.');
        }

        $discountValue = max(0, (int) $coupon->discount_value);
        $discountAmount = match ((string) $coupon->discount_type) {
            'percent' => (int) floor($subtotalAmount * min($discountValue, 100) / 100),
            'fixed' => $discountValue,
            default => throw new RuntimeException('Tipo de cupom invalido.'),
        };

        return [
            'code' => (string) $coupon->code,
            'description' => (string) $coupon->description,
            'discountType' => (string) $coupon->discount_type,
            'discountValue' => $discountValue,
            'discountAmount' => min($subtotalAmount, $discountAmount),
        ];
    }

    public function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }
}
