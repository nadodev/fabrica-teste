<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cart\Application\Query\ShowCart;
use App\Modules\Customers\Application\Query\ShowCustomerAccount;
use App\Modules\Ordering\Application\Command\CheckoutCart;
use App\Modules\Ordering\Application\DTO\CheckoutData;
use App\Modules\Ordering\Presentation\Http\Request\CheckoutRequest;
use App\Modules\Payment\Application\Command\EnsurePaymentGatewayReady;
use App\Modules\Payment\Application\Command\ProcessPayment;
use App\Modules\Payment\Application\DTO\CreditCardData;
use App\Modules\Payment\Application\Exception\PaymentCardDeclined;
use App\Modules\Payment\Application\Query\ShowCheckoutSuccess;
use App\Support\StoreSettings;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

final class CheckoutController extends Controller
{
    public function create(Request $request, ShowCart $cart, ShowCustomerAccount $account): Response|RedirectResponse
    {
        $settings = app(StoreSettings::class);
        $customers = $settings->customers();
        if ($request->user() === null && ((bool) ($customers['registrationRequired'] ?? false) || ! (bool) ($customers['guestCheckout'] ?? true))) {
            return to_route('cliente.login')->withErrors(['auth' => 'Entre na sua conta para finalizar o pedido.']);
        }

        $view = $cart->handle($request->session()->get('cart_token'), $request->session()->get('coupon_code'), $request->session()->get('shipping_quote'));

        if ($view->items === []) {
            return to_route('carrinho')->withErrors(['cart' => 'Seu carrinho esta vazio.']);
        }

        if ($view->totalAmount < $settings->minimumOrderAmount()) {
            return to_route('carrinho')->withErrors(['cart' => 'O valor minimo do pedido ainda nao foi atingido.']);
        }

        if ($view->shipping === null) {
            return to_route('carrinho')->withErrors(['shipping' => 'Calcule e selecione uma opcao de frete antes de finalizar.']);
        }

        $user = $request->user();
        $customer = $user instanceof User ? $account->handle((int) $user->getAuthIdentifier()) : null;

        return Inertia::render('checkout', [
            'cart' => $view,
            'shippingZip' => $request->session()->get('shipping_zip'),
            'paymentMethods' => $settings->enabledPaymentMethods(),
            'customerSettings' => $customers,
            'policySettings' => $settings->policies(),
            'customer' => $customer['profile'] ?? null,
            'savedAddresses' => $customer['addresses'] ?? [],
        ]);
    }

    public function store(
        CheckoutRequest $request,
        CheckoutCart $checkout,
        ProcessPayment $payments,
        EnsurePaymentGatewayReady $paymentGateway,
    ): RedirectResponse {
        $settings = app(StoreSettings::class);
        $customers = $settings->customers();
        if ($request->user() === null && ((bool) ($customers['registrationRequired'] ?? false) || ! (bool) ($customers['guestCheckout'] ?? true))) {
            return to_route('cliente.login')->withErrors(['auth' => 'Entre na sua conta para finalizar o pedido.']);
        }
        $token = $request->session()->get('cart_token');

        if (! is_string($token)) {
            return to_route('carrinho')->withErrors(['cart' => 'Seu carrinho esta vazio.']);
        }

        try {
            $data = $request->validated();
            if ($data['checkoutType'] === 'payment') {
                $paymentGateway->handle();
            }
            $user = Auth::user();
            $customerName = $user instanceof User ? $user->name : (string) $data['customerName'];
            $customerEmail = $user instanceof User ? $user->email : (string) $data['customerEmail'];
            $couponCode = $request->session()->get('coupon_code');
            $shippingQuote = $request->session()->get('shipping_quote');
            $order = $checkout->handle(
                (string) Str::uuid(),
                $token,
                new CheckoutData(
                    (string) $data['checkoutType'],
                    $customerName,
                    $customerEmail,
                    (string) $data['customerPhone'],
                    isset($data['customerDocument']) ? (string) $data['customerDocument'] : null,
                    (string) $data['shippingZip'],
                    (string) $data['shippingAddress'],
                    (string) $data['shippingNumber'],
                    (string) $data['shippingCity'],
                    (string) $data['shippingState'],
                    (string) $data['deliveryMethod'],
                    (string) $data['paymentMethod'],
                    isset($data['notes']) ? (string) $data['notes'] : null,
                    is_string($couponCode) ? $couponCode : null,
                    is_array($shippingQuote) ? $shippingQuote : null,
                    $user instanceof User ? (int) $user->getAuthIdentifier() : null,
                ),
            );
        } catch (DomainException|RuntimeException $exception) {
            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        $paymentError = null;
        if ($order->details()->checkoutType === 'payment') {
            try {
                $card = $order->details()->paymentMethod === 'credit_card'
                    ? new CreditCardData(
                        (string) $data['cardHolderName'],
                        (string) $data['cardNumber'],
                        (string) $data['cardExpiryMonth'],
                        (string) $data['cardExpiryYear'],
                        (string) $data['cardCcv'],
                        (string) ($request->ip() ?? ''),
                    )
                    : null;
                $payments->handle($order->id, $card);
            } catch (PaymentCardDeclined) {
                return to_route('checkout')->withErrors([
                    'cardNumber' => 'O cartao nao foi autorizado. Confira os dados ou informe outro cartao.',
                ]);
            } catch (Throwable $exception) {
                report($exception);
                $paymentError = $order->details()->paymentMethod === 'credit_card'
                    ? 'O pedido foi criado, mas o cartao nao pode ser processado agora. Fale com a loja antes de tentar novamente.'
                    : 'O pedido foi criado, mas o pagamento nao pode ser processado agora. Tentaremos novamente automaticamente.';
            }
        }

        $request->session()->forget('cart_token');
        $request->session()->forget('coupon_code');
        $request->session()->forget(['shipping_quote', 'shipping_quotes', 'shipping_zip']);
        $request->session()->put('checkout_order_id', $order->id);
        $redirect = to_route('checkout.success', ['order' => $order->number]);

        return $paymentError === null ? $redirect : $redirect->withErrors(['payment' => $paymentError]);
    }

    public function success(string $order, Request $request, ShowCheckoutSuccess $query): Response
    {
        $sessionOrderId = $request->session()->get('checkout_order_id');
        $user = $request->user();
        $view = $query->handle(
            $order,
            is_string($sessionOrderId) ? $sessionOrderId : null,
            $user instanceof User ? (int) $user->getAuthIdentifier() : null,
        );
        abort_if($view === null, 404);

        return Inertia::render('pedido-confirmado', [
            'orderNumber' => $view->orderNumber,
            'checkoutType' => $view->checkoutType,
            'paymentMethod' => $view->paymentMethod,
            'paymentStatus' => $view->paymentStatus,
            'paymentFailureCode' => $view->paymentFailureCode,
            'instructions' => $view->instructions,
        ]);
    }
}
