<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Application\Command\AddItemToCart;
use App\Modules\Cart\Application\Command\RemoveItemFromCart;
use App\Modules\Cart\Application\Query\ShowCart;
use App\Modules\Cart\Presentation\Http\Request\AddCartItemRequest;
use App\Support\CouponCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class CartController extends Controller
{
    public function index(Request $request, ShowCart $query): Response
    {
        return Inertia::render('carrinho', [
            'cart' => $query->handle($request->session()->get('cart_token'), $request->session()->get('coupon_code')),
        ]);
    }

    public function store(AddCartItemRequest $request, AddItemToCart $command): RedirectResponse
    {
        $token = $request->session()->get('cart_token', fn (): string => Str::random(64));
        $request->session()->put('cart_token', $token);
        $data = $request->validated();

        try {
            $command->handle(
                (string) Str::uuid(),
                (string) $token,
                (string) $data['productId'],
                (int) $data['quantity'],
                isset($data['variationId']) ? (string) $data['variationId'] : null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['cart' => $exception->getMessage()]);
        }

        return back()->with('success', 'Produto adicionado ao carrinho.');
    }

    public function destroy(Request $request, string $product, RemoveItemFromCart $command): RedirectResponse
    {
        $token = $request->session()->get('cart_token');

        if (is_string($token)) {
            $command->handle($token, $product);
        }

        return to_route('carrinho')->with('success', 'Produto removido do carrinho.');
    }

    public function applyCoupon(Request $request, ShowCart $cart, CouponCalculator $coupons): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $view = $cart->handle($request->session()->get('cart_token'));

        if ($view->items === []) {
            return back()->withErrors(['coupon' => 'Adicione produtos antes de usar um cupom.']);
        }

        try {
            $coupon = $coupons->validDiscount((string) $data['code'], $view->subtotalAmount);
        } catch (RuntimeException $exception) {
            $request->session()->forget('coupon_code');

            return back()->withErrors(['coupon' => $exception->getMessage()]);
        }

        $request->session()->put('coupon_code', $coupon['code']);

        return back()->with('success', 'Cupom aplicado com sucesso.');
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        $request->session()->forget('coupon_code');

        return back()->with('success', 'Cupom removido.');
    }
}
