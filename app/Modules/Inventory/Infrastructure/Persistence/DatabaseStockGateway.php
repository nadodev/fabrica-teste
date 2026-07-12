<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence;

use App\Modules\Inventory\Application\Port\StockGateway;
use App\Modules\Inventory\Application\Port\StockManager;
use App\Modules\Inventory\Domain\Exception\InsufficientStock;
use App\Modules\Inventory\Domain\Exception\ReservationConflict;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

final readonly class DatabaseStockGateway implements StockGateway, StockManager
{
    public function __construct(private ConnectionInterface $database) {}

    public function available(string $productId): int
    {
        $stock = $this->database->table('inventory_stock')->where('product_id', $productId)->first();

        return $stock === null ? 0 : (int) $stock->on_hand - (int) $stock->reserved;
    }

    public function receive(string $reference, string $productId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Received quantity must be at least one.');
        }

        $this->database->transaction(function () use ($reference, $productId, $quantity): void {
            $this->database->table('inventory_stock')->insertOrIgnore([
                'product_id' => $productId,
                'on_hand' => 0,
                'reserved' => 0,
                'version' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stock = $this->database->table('inventory_stock')->where('product_id', $productId)->lockForUpdate()->firstOrFail();

            if ($this->database->table('inventory_movements')->where('reference', $reference)->exists()) {
                return;
            }

            $newBalance = (int) $stock->on_hand + $quantity;

            $this->database->table('inventory_stock')->where('product_id', $productId)->update([
                'on_hand' => $newBalance,
                'version' => (int) $stock->version + 1,
                'updated_at' => now(),
            ]);

            $this->database->table('inventory_movements')->insert([
                'product_id' => $productId,
                'type' => 'received',
                'quantity' => $quantity,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'created_at' => now(),
            ]);
        }, 3);
    }

    public function reserve(string $reservationId, string $productId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Reserved quantity must be at least one.');
        }

        $this->database->transaction(function () use ($reservationId, $productId, $quantity): void {
            $existing = $this->database->table('inventory_reservations')->where('id', $reservationId)->first();

            if ($existing !== null) {
                if ($existing->product_id !== $productId || (int) $existing->quantity !== $quantity) {
                    throw new ReservationConflict('Reservation key was reused with different data.');
                }

                return;
            }

            $stock = $this->database->table('inventory_stock')->where('product_id', $productId)->lockForUpdate()->first();
            $available = $stock === null ? 0 : (int) $stock->on_hand - (int) $stock->reserved;

            if ($stock === null || $available < $quantity) {
                throw InsufficientStock::forProduct($productId, $quantity, $available);
            }

            $existing = $this->database->table('inventory_reservations')->where('id', $reservationId)->first();

            if ($existing !== null) {
                if ($existing->product_id !== $productId || (int) $existing->quantity !== $quantity) {
                    throw new ReservationConflict('Reservation key was reused with different data.');
                }

                return;
            }

            $this->database->table('inventory_stock')->where('product_id', $productId)->update([
                'reserved' => (int) $stock->reserved + $quantity,
                'version' => (int) $stock->version + 1,
                'updated_at' => now(),
            ]);

            $this->database->table('inventory_reservations')->insert([
                'id' => $reservationId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'status' => 'active',
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    public function release(string $reservationId): void
    {
        $this->database->transaction(function () use ($reservationId): void {
            $snapshot = $this->database->table('inventory_reservations')->where('id', $reservationId)->first();

            if ($snapshot === null || $snapshot->status !== 'active') {
                return;
            }

            $stock = $this->database->table('inventory_stock')->where('product_id', $snapshot->product_id)->lockForUpdate()->firstOrFail();
            $reservation = $this->database->table('inventory_reservations')->where('id', $reservationId)->lockForUpdate()->firstOrFail();

            if ($reservation->status !== 'active') {
                return;
            }

            $this->database->table('inventory_stock')->where('product_id', $snapshot->product_id)->update([
                'reserved' => (int) $stock->reserved - (int) $reservation->quantity,
                'version' => (int) $stock->version + 1,
                'updated_at' => now(),
            ]);
            $this->database->table('inventory_reservations')->where('id', $reservationId)->update([
                'status' => 'released',
                'updated_at' => now(),
            ]);
        }, 3);
    }
}
