<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Application\Query\ShowCart;
use App\Modules\Shipping\Application\Query\QuoteCartShipping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class ShippingController extends Controller
{
    public function quote(Request $request, ShowCart $cart, QuoteCartShipping $shipping): RedirectResponse
    {
        $data = $request->validate([
            'zip' => ['required', 'string', 'regex:/^(?:\D*\d){8}\D*$/'],
        ]);

        $view = $cart->handle($request->session()->get('cart_token'), $request->session()->get('coupon_code'));

        if ($view->items === []) {
            return back()->withErrors(['shipping' => 'Adicione produtos antes de calcular o frete.']);
        }

        try {
            $quotes = array_map($shipping->toArray(...), $shipping->handle((string) $data['zip'], $view));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['shipping' => $exception->getMessage()]);
        }

        $request->session()->put('shipping_zip', preg_replace('/\D+/', '', (string) $data['zip']));
        $request->session()->put('shipping_quotes', $quotes);
        $request->session()->forget('shipping_quote');

        return back()->with('success', 'Frete calculado.');
    }

    public function select(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serviceId' => ['required', 'string', 'max:80'],
        ]);

        $quotes = $request->session()->get('shipping_quotes', []);
        $selected = collect(is_array($quotes) ? $quotes : [])->first(
            fn (array $quote): bool => (string) ($quote['serviceId'] ?? '') === (string) $data['serviceId'],
        );

        if (! is_array($selected)) {
            return back()->withErrors(['shipping' => 'Calcule o frete novamente antes de selecionar.']);
        }

        $request->session()->put('shipping_quote', $selected);

        return back()->with('success', 'Frete selecionado.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $request->session()->forget(['shipping_quote', 'shipping_quotes', 'shipping_zip']);

        return back()->with('success', 'Frete removido.');
    }
}
