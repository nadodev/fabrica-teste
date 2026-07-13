<?php

declare(strict_types=1);

namespace App\Modules\Cart\Infrastructure\Persistence;

use App\Modules\Cart\Domain\Cart;
use App\Modules\Cart\Domain\CartItem;
use App\Modules\Cart\Domain\Exception\CartConcurrencyConflict;
use App\Modules\Cart\Domain\Port\CartRepository;
use App\Modules\Shared\Domain\ValueObject\Money;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Ramsey\Uuid\Uuid;

final readonly class DatabaseCartRepository implements CartRepository
{
    public function __construct(private ConnectionInterface $database) {}

    public function find(string $id): ?Cart
    {
        return $this->hydrate($this->database->table('cart_carts')->where('id', $id)->where('status', 'active')->first());
    }

    public function findByTokenHash(string $tokenHash, bool $onlyActive = true): ?Cart
    {
        $query = $this->database->table('cart_carts')->where('token_hash', $tokenHash);

        if ($onlyActive) {
            $query->where('status', 'active');
        }

        return $this->hydrate($query->first());
    }

    public function save(Cart $cart): void
    {
        try {
            $this->database->transaction(function () use ($cart): void {
                $this->persistVersion($cart);
                $this->database->table('cart_items')->where('cart_id', $cart->id)->delete();

                foreach ($cart->items() as $item) {
                    $this->database->table('cart_items')->insert([
                        'cart_id' => $cart->id,
                        'product_id' => $item->productId,
                        'cart_item_key' => $item->cartItemKey,
                        'variation_key' => $item->variationKey,
                        'variation_label' => $item->variationLabel,
                        'notes' => $item->notes,
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'unit_price_amount' => $item->unitPrice->amount,
                        'price_currency' => $item->unitPrice->currency,
                        'quantity' => $item->quantity,
                        'image_url' => $item->imageUrl,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true)) {
                throw new CartConcurrencyConflict('Cart was created or modified concurrently.', previous: $exception);
            }

            throw $exception;
        }

        $cart->markPersisted();
    }

    public function markConverted(Cart $cart): void
    {
        $updated = $this->database->table('cart_carts')
            ->where('id', $cart->id)
            ->where('version', $cart->version())
            ->where('status', 'active')
            ->update(['status' => 'converted', 'version' => $cart->version() + 1, 'updated_at' => now()]);

        if ($updated !== 1) {
            throw new CartConcurrencyConflict('Cart could not be converted.');
        }

        $cart->markPersisted();
    }

    public function restoreAfterFailedCheckout(string $cartId): void
    {
        $this->database->transaction(function () use ($cartId): void {
            $cart = $this->database->table('cart_carts')
                ->where('id', $cartId)
                ->where('status', 'converted')
                ->lockForUpdate()
                ->first();

            if ($cart === null) {
                throw new CartConcurrencyConflict('Converted cart could not be restored after payment failure.');
            }

            $tokenHash = (string) $cart->token_hash;
            $newCartId = (string) Uuid::uuid4();
            $this->database->table('cart_carts')->where('id', $cartId)->update([
                'token_hash' => hash('sha256', 'converted:'.$cartId),
                'version' => (int) $cart->version + 1,
                'updated_at' => now(),
            ]);
            $this->database->table('cart_carts')->insert([
                'id' => $newCartId,
                'token_hash' => $tokenHash,
                'currency' => (string) $cart->currency,
                'status' => 'active',
                'version' => 1,
                'expires_at' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($this->database->table('cart_items')->where('cart_id', $cartId)->orderBy('id')->get() as $item) {
                $this->database->table('cart_items')->insert([
                    'cart_id' => $newCartId,
                    'product_id' => $item->product_id,
                    'cart_item_key' => $item->cart_item_key,
                    'variation_key' => $item->variation_key,
                    'variation_label' => $item->variation_label,
                    'notes' => $item->notes,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'unit_price_amount' => $item->unit_price_amount,
                    'price_currency' => $item->price_currency,
                    'quantity' => $item->quantity,
                    'image_url' => $item->image_url,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }, 3);
    }

    private function persistVersion(Cart $cart): void
    {
        if ($cart->version() === 0) {
            $this->database->table('cart_carts')->insert([
                'id' => $cart->id,
                'token_hash' => $cart->tokenHash,
                'currency' => $cart->currency,
                'status' => 'active',
                'version' => 1,
                'expires_at' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $updated = $this->database->table('cart_carts')
            ->where('id', $cart->id)
            ->where('version', $cart->version())
            ->update(['version' => $cart->version() + 1, 'updated_at' => now()]);

        if ($updated !== 1) {
            throw new CartConcurrencyConflict('Cart was modified by another request.');
        }
    }

    private function hydrate(?object $record): ?Cart
    {
        if ($record === null) {
            return null;
        }

        $row = (array) $record;

        $items = $this->database->table('cart_items')->where('cart_id', $row['id'])->orderBy('id')->get()
            ->map(fn (object $item): CartItem => new CartItem(
                (string) $item->product_id,
                (string) ($item->cart_item_key ?? $item->product_id),
                (string) $item->name,
                new Money((int) $item->unit_price_amount, (string) $item->price_currency),
                (int) $item->quantity,
                (string) $item->sku,
                $item->image_url === null ? null : (string) $item->image_url,
                $item->variation_key === null ? null : (string) $item->variation_key,
                $item->variation_label === null ? null : (string) $item->variation_label,
                $item->notes === null ? null : (string) $item->notes,
            ))->all();

        return Cart::restore(
            (string) $row['id'],
            (string) $row['token_hash'],
            (string) $row['currency'],
            (int) $row['version'],
            array_values($items),
        );
    }
}
