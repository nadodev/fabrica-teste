<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Application\Command;

use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Catalog\Domain\Port\ProductRepository;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\ValueObject\ProductId;
use App\Modules\Inventory\Application\Port\StockGateway;
use App\Modules\Ordering\Application\DTO\CheckoutData;
use App\Modules\Ordering\Application\Port\CouponGateway;
use App\Modules\Ordering\Domain\Order;
use App\Modules\Ordering\Domain\OrderDetails;
use App\Modules\Ordering\Domain\OrderItem;
use App\Modules\Ordering\Domain\Port\OrderRepository;
use App\Modules\Payment\Application\Command\CreatePaymentIntent;
use App\Modules\Shared\Application\Port\OutboxStore;
use App\Modules\Shared\Application\Port\TransactionManager;
use App\Modules\Shared\Domain\ValueObject\Money;
use App\Modules\Shipping\Application\Query\ResolveFreeShipping;
use App\Support\StoreSettings;
use DomainException;
use Ramsey\Uuid\Uuid;

final readonly class CheckoutCart
{
    public function __construct(
        private CartRepository $carts,
        private ProductRepository $products,
        private OrderRepository $orders,
        private StockGateway $stock,
        private CouponGateway $coupons,
        private OutboxStore $outbox,
        private TransactionManager $transactions,
        private StoreSettings $settings,
        private CreatePaymentIntent $createPayment,
        private ResolveFreeShipping $freeShipping,
    ) {}

    public function handle(string $orderId, string $plainCartToken, CheckoutData $data): Order
    {
        return $this->transactions->run(function () use ($orderId, $plainCartToken, $data): Order {
            $cart = $this->carts->findByTokenHash(hash('sha256', $plainCartToken), false)
                ?? throw new DomainException('Active cart not found.');
            $existing = $this->orders->findByCartId($cart->id);

            if ($existing !== null) {
                return $existing;
            }

            if ($cart->total()->amount < $this->settings->minimumOrderAmount()) {
                throw new DomainException('O valor minimo do pedido ainda nao foi atingido.');
            }

            $paymentMethod = $data->checkoutType === 'quote' ? 'combine' : $data->paymentMethod;
            if ($data->checkoutType === 'payment' && ! in_array($paymentMethod, $this->settings->enabledPaymentMethods(), true)) {
                throw new DomainException('A forma de pagamento selecionada nao esta disponivel.');
            }

            $discount = null;
            if (is_string($data->couponCode) && trim($data->couponCode) !== '') {
                $discount = $this->coupons->consume($data->couponCode, $cart->total()->amount);
            }

            if ($data->deliveryMethod !== 'shipping') {
                throw new DomainException('A entrega deve ser realizada por uma opcao de frete valida.');
            }

            $eligibleAmount = max(0, $cart->total()->amount - ($discount === null ? 0 : $discount->amount));
            $quote = $this->freeShipping->handle($eligibleAmount)
                ?? $data->shippingQuote
                ?? throw new DomainException('Calcule e selecione uma opcao de frete antes de finalizar.');
            $shippingService = trim((string) ($quote['name'] ?? ''));
            $shippingCompany = trim((string) ($quote['companyName'] ?? '')) ?: null;
            $shippingAmount = max(0, (int) ($quote['priceAmount'] ?? -1));
            $shippingDeliveryTime = max(0, (int) ($quote['deliveryTime'] ?? 0));

            if ($shippingService === '' || (int) ($quote['priceAmount'] ?? -1) < 0) {
                throw new DomainException('Calcule e selecione uma opcao de frete antes de finalizar.');
            }

            $items = array_map(
                function ($item): OrderItem {
                    $product = $this->products->find(ProductId::fromString($item->productId));
                    if ($product === null || $product->status() !== ProductStatus::Active) {
                        throw new DomainException("O produto {$item->name} nao esta mais disponivel.");
                    }

                    if ($product->variationSku($item->variationKey) !== $item->sku || $product->price()->amount !== $item->unitPrice->amount || $product->price()->currency !== $item->unitPrice->currency) {
                        throw new DomainException("O produto {$item->name} foi atualizado. Remova-o e adicione-o novamente ao carrinho.");
                    }

                    $variationLabel = $product->variationLabel($item->variationKey);
                    if ($variationLabel !== $item->variationLabel) {
                        throw new DomainException("A variacao de {$item->name} nao esta mais disponivel.");
                    }

                    return new OrderItem($item->productId, $item->sku, $product->name(), $item->unitPrice, $item->quantity, $item->variationKey, $variationLabel, $item->notes);
                },
                $cart->items(),
            );
            $details = new OrderDetails(
                $data->checkoutType,
                $data->customerName,
                $data->customerEmail,
                $data->customerPhone,
                $data->customerDocument,
                $data->shippingZip,
                $data->shippingAddress,
                $data->shippingNumber,
                $data->shippingCity,
                $data->shippingState,
                $data->deliveryMethod,
                $shippingService,
                $shippingCompany,
                new Money($shippingAmount, $cart->currency),
                $shippingDeliveryTime,
                $paymentMethod,
                'pending',
                $data->notes,
                $discount?->code,
                new Money($discount === null ? 0 : $discount->amount, $cart->currency),
            );
            $order = Order::place($orderId, $this->orders->nextIdentity(), $cart->id, $items, $details, $data->customerUserId);

            if ($this->settings->controlsStock()) {
                foreach ($order->items() as $item) {
                    $reservationId = Uuid::uuid5(Uuid::NAMESPACE_URL, $order->id.':'.$item->productId.':'.($item->variationKey ?? 'default'))->toString();
                    $this->stock->reserve($reservationId, $item->productId, $item->quantity, $item->variationKey);
                }
            }

            $this->orders->save($order);
            if ($data->checkoutType === 'payment') {
                $this->createPayment->handle(
                    $order->id,
                    $order->total()->amount,
                    $order->total()->currency,
                    $paymentMethod,
                    $this->settings->controlsStock(),
                );
                $this->outbox->add(
                    Uuid::uuid5(Uuid::NAMESPACE_URL, 'payment-requested:'.$order->id)->toString(),
                    'payment.requested',
                    $order->id,
                    ['orderId' => $order->id],
                );
            }
            $this->carts->markConverted($cart);
            $this->outbox->add(
                Uuid::uuid5(Uuid::NAMESPACE_URL, 'order-placed:'.$order->id)->toString(),
                'ordering.order_placed',
                $order->id,
                ['orderId' => $order->id],
            );

            return $order;
        });
    }
}
