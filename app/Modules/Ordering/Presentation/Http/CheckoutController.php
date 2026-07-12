<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Application\Query\ShowCart;
use App\Modules\Ordering\Application\Command\CheckoutCart;
use App\Modules\Ordering\Presentation\Http\Request\CheckoutRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class CheckoutController extends Controller
{
    public function create(Request $request, ShowCart $cart): Response|RedirectResponse
    {
        $view = $cart->handle($request->session()->get('cart_token'), $request->session()->get('coupon_code'));

        if ($view->items === []) {
            return to_route('carrinho')->withErrors(['cart' => 'Seu carrinho esta vazio.']);
        }

        return Inertia::render('checkout', ['cart' => $view]);
    }

    public function store(CheckoutRequest $request, CheckoutCart $checkout): RedirectResponse
    {
        $token = $request->session()->get('cart_token');

        if (! is_string($token)) {
            return to_route('carrinho')->withErrors(['cart' => 'Seu carrinho esta vazio.']);
        }

        try {
            $order = $checkout->handle((string) Str::uuid(), $token);
            $view = app(ShowCart::class)->handle($token, $request->session()->get('coupon_code'));
            $this->storeCustomerData($order->id, $request->validated(), $view);
            $this->decrementVariationStock($order->cartId);
            $request->session()->forget('cart_token');
            $request->session()->forget('coupon_code');
        } catch (DomainException|RuntimeException $exception) {
            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        return to_route('checkout.success', ['order' => $order->number]);
    }

    public function success(string $order): Response
    {
        return Inertia::render('pedido-confirmado', ['orderNumber' => $order]);
    }

    /** @param array<string, mixed> $data */
    private function storeCustomerData(string $orderId, array $data, object $cart): void
    {
        DB::table('ordering_orders')->where('id', $orderId)->update([
            'customer_name' => $data['customerName'],
            'customer_email' => $data['customerEmail'],
            'customer_phone' => $data['customerPhone'],
            'customer_document' => $data['customerDocument'] ?? null,
            'shipping_zip' => $data['shippingZip'],
            'shipping_address' => $data['shippingAddress'],
            'shipping_number' => $data['shippingNumber'],
            'shipping_city' => $data['shippingCity'],
            'shipping_state' => $data['shippingState'],
            'notes' => $data['notes'] ?? null,
            'subtotal_amount' => $cart->subtotalAmount,
            'discount_amount' => $cart->discountAmount,
            'coupon_code' => $cart->coupon['code'] ?? null,
            'total_amount' => $cart->totalAmount,
            'updated_at' => now(),
        ]);
    }

    private function decrementVariationStock(string $cartId): void
    {
        $items = DB::table('cart_items')->where('cart_id', $cartId)->whereNotNull('variation_key')->get();

        foreach ($items as $item) {
            $product = DB::table('catalog_products')->where('id', $item->product_id)->first(['variations']);
            $variations = json_decode((string) ($product->variations ?? '[]'), true);

            if (! is_array($variations)) {
                continue;
            }

            $changed = false;
            foreach ($variations as &$variation) {
                if (($variation['id'] ?? null) !== $item->variation_key) {
                    continue;
                }

                $variation['stock'] = max(0, (int) ($variation['stock'] ?? 0) - (int) $item->quantity);
                $threshold = max(0, (int) ($variation['lowStockThreshold'] ?? 5));
                $variation['lowStock'] = $variation['stock'] <= $threshold;
                $variation['purchasable'] = $variation['stock'] > $threshold;
                $changed = true;
                break;
            }
            unset($variation);

            if ($changed) {
                DB::table('catalog_products')->where('id', $item->product_id)->update([
                    'variations' => json_encode(array_values($variations), JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
