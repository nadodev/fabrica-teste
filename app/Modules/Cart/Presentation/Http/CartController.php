<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Application\Command\AddItemToCart;
use App\Modules\Cart\Application\Command\RemoveItemFromCart;
use App\Modules\Cart\Application\Query\ShowCart;
use App\Modules\Cart\Presentation\Http\Request\AddCartItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class CartController extends Controller
{
    public function index(Request $request, ShowCart $query): Response
    {
        return Inertia::render('carrinho', [
            'cart' => $query->handle($request->session()->get('cart_token')),
        ]);
    }

    public function store(AddCartItemRequest $request, AddItemToCart $command): RedirectResponse
    {
        $token = $request->session()->get('cart_token', fn (): string => Str::random(64));
        $request->session()->put('cart_token', $token);
        $data = $request->validated();

        $command->handle(
            (string) Str::uuid(),
            (string) $token,
            (string) $data['productId'],
            (int) $data['quantity'],
        );

        return to_route('carrinho')->with('success', 'Produto adicionado ao carrinho.');
    }

    public function destroy(Request $request, string $product, RemoveItemFromCart $command): RedirectResponse
    {
        $token = $request->session()->get('cart_token');

        if (is_string($token)) {
            $command->handle($token, $product);
        }

        return to_route('carrinho')->with('success', 'Produto removido do carrinho.');
    }
}
