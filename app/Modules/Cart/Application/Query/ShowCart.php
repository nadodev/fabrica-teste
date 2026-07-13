<?php

declare(strict_types=1);

namespace App\Modules\Cart\Application\Query;

use App\Modules\Cart\Application\DTO\CartView;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Support\CouponCalculator;
use RuntimeException;

final readonly class ShowCart
{
    public function __construct(private CartRepository $carts, private CouponCalculator $coupons) {}

    /** @param array<string, mixed>|null $shipping */
    public function handle(?string $plainToken, ?string $couponCode = null, ?array $shipping = null): CartView
    {
        if ($plainToken === null) {
            return CartView::empty();
        }

        $cart = $this->carts->findByTokenHash(hash('sha256', $plainToken));

        if ($cart === null) {
            return CartView::empty();
        }

        $coupon = null;
        if (is_string($couponCode) && $couponCode !== '') {
            try {
                $coupon = $this->coupons->validDiscount($couponCode, $cart->total()->amount);
            } catch (RuntimeException) {
                $coupon = null;
            }
        }

        return CartView::fromDomain($cart, $coupon, $shipping);
    }
}
