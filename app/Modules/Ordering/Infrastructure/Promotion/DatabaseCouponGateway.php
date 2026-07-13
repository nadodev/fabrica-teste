<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Promotion;

use App\Modules\Ordering\Application\Port\CouponGateway;
use App\Modules\Ordering\Application\ValueObject\CouponDiscount;
use App\Support\StoreSettings;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

final readonly class DatabaseCouponGateway implements CouponGateway
{
    public function __construct(private ConnectionInterface $database, private StoreSettings $settings) {}

    public function consume(string $code, int $subtotalAmount): CouponDiscount
    {
        if (! $this->settings->couponsEnabled()) {
            throw new RuntimeException('Cupons estao desativados nesta loja.');
        }

        $normalized = strtoupper(trim($code));
        if ($normalized === '') {
            throw new RuntimeException('Informe um cupom valido.');
        }

        return $this->database->transaction(function () use ($normalized, $subtotalAmount): CouponDiscount {
            $coupon = $this->database->table('commerce_coupons')->where('code', $normalized)->lockForUpdate()->first();

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

            if ((int) ($coupon->minimum_amount ?? 0) > $subtotalAmount) {
                throw new RuntimeException('Cupom exige um valor minimo maior para este carrinho.');
            }

            if ($coupon->usage_limit !== null && (int) $coupon->used_count >= (int) $coupon->usage_limit) {
                throw new RuntimeException('Cupom atingiu o limite de uso.');
            }

            $discountValue = max(0, (int) $coupon->discount_value);
            $discountAmount = match ((string) $coupon->discount_type) {
                'percent' => (int) floor($subtotalAmount * min($discountValue, 100) / 100),
                'fixed' => $discountValue,
                default => throw new RuntimeException('Tipo de cupom invalido.'),
            };

            $this->database->table('commerce_coupons')->where('id', $coupon->id)->update([
                'used_count' => (int) $coupon->used_count + 1,
                'updated_at' => $now,
            ]);

            return new CouponDiscount((string) $coupon->code, min($subtotalAmount, $discountAmount));
        }, 3);
    }
}
