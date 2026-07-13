<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Infrastructure\Persistence;

use App\Modules\Inventory\Application\DTO\StockLevel;
use App\Modules\Inventory\Application\Port\StockGateway;
use App\Modules\Inventory\Application\Port\StockManager;
use App\Modules\Inventory\Application\Port\StockReservationLifecycle;
use App\Modules\Inventory\Domain\Exception\InsufficientStock;
use App\Modules\Inventory\Domain\Exception\ReservationConflict;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class DatabaseStockGateway implements StockGateway, StockManager, StockReservationLifecycle
{
    public function __construct(private ConnectionInterface $database) {}

    public function tracked(string $productId, ?string $variationKey = null): bool
    {
        $query = $this->levelQuery($productId, $variationKey);

        return $query->exists();
    }

    public function available(string $productId, ?string $variationKey = null): int
    {
        if ($variationKey === null) {
            return (int) $this->database->table('inventory_stock_levels')
                ->where('product_id', $productId)
                ->selectRaw('COALESCE(SUM(on_hand - reserved), 0) AS available')
                ->value('available');
        }

        $stock = $this->levelQuery($productId, $variationKey)->first();

        return $stock === null ? 0 : max(0, (int) $stock->on_hand - (int) $stock->reserved);
    }

    public function levels(string $productId): array
    {
        $levels = $this->database->table('inventory_stock_levels')
            ->where('product_id', $productId)
            ->orderByRaw('variation_key IS NULL DESC')
            ->orderBy('variation_key')
            ->get()
            ->map(fn (object $level): StockLevel => $this->toLevel((array) $level))
            ->values()
            ->all();

        return array_values($levels);
    }

    public function receive(string $reference, string $productId, int $quantity, ?string $variationKey = null): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Received quantity must be at least one.');
        }

        $this->database->transaction(function () use ($reference, $productId, $quantity, $variationKey): void {
            if ($this->database->table('inventory_movements')->where('reference', $reference)->exists()) {
                return;
            }

            $stock = $this->lockedLevel($productId, $variationKey);
            if ($stock === null && $variationKey === null) {
                $sku = $this->database->table('catalog_products')->where('id', $productId)->value('sku');
                if (! is_string($sku)) {
                    throw new RuntimeException('Product not found for stock receipt.');
                }
                $this->insertLevel($productId, $productId, null, $sku, 0, 5);
                $stock = $this->lockedLevel($productId, null);
            }
            if ($stock === null) {
                throw new RuntimeException('Stock level not found for receipt.');
            }

            $this->changeOnHand($stock, $quantity, 'received', $reference);
        }, 3);
    }

    public function adjust(string $reference, string $stockId, int $quantity): void
    {
        if ($quantity === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        $this->database->transaction(function () use ($reference, $stockId, $quantity): void {
            if ($this->database->table('inventory_movements')->where('reference', $reference)->exists()) {
                return;
            }

            $stock = $this->database->table('inventory_stock_levels')->where('id', $stockId)->lockForUpdate()->first();
            if ($stock === null) {
                throw new RuntimeException('Stock level not found.');
            }
            if ((int) $stock->on_hand + $quantity < (int) $stock->reserved) {
                throw new RuntimeException('Adjustment cannot reduce stock below the reserved quantity.');
            }

            $this->changeOnHand((array) $stock, $quantity, 'adjustment', $reference);
        }, 3);
    }

    public function synchronizeProduct(string $reference, string $productId, string $baseSku, int $simpleQuantity, array $variations): void
    {
        $this->database->transaction(function () use ($reference, $productId, $baseSku, $simpleQuantity, $variations): void {
            $desired = [];
            if ($variations === []) {
                $desired[$productId] = [
                    'variationKey' => null,
                    'sku' => $baseSku,
                    'quantity' => max(0, $simpleQuantity),
                    'threshold' => 5,
                ];
            } else {
                foreach ($variations as $variation) {
                    $variationKey = trim((string) ($variation['id'] ?? ''));
                    if ($variationKey === '') {
                        throw new RuntimeException('Variation ID is required to synchronize stock.');
                    }
                    $stockId = $this->stockId($productId, $variationKey);
                    $desired[$stockId] = [
                        'variationKey' => $variationKey,
                        'sku' => $this->variationSku($baseSku, $variationKey, $variation['sku'] ?? null),
                        'quantity' => max(0, (int) ($variation['stock'] ?? 0)),
                        'threshold' => max(0, (int) ($variation['lowStockThreshold'] ?? 5)),
                    ];
                }
            }

            $existing = $this->database->table('inventory_stock_levels')->where('product_id', $productId)->lockForUpdate()->get()->keyBy('id');
            foreach ($existing as $stockId => $level) {
                if (isset($desired[(string) $stockId])) {
                    continue;
                }
                if ((int) $level->reserved > 0) {
                    throw new RuntimeException('A stock variation with active reservations cannot be removed.');
                }
                $this->database->table('inventory_stock_levels')->where('id', $stockId)->delete();
            }

            foreach ($desired as $stockId => $target) {
                $current = $this->database->table('inventory_stock_levels')->where('id', $stockId)->lockForUpdate()->first();
                if ($current === null) {
                    $this->insertLevel(
                        (string) $stockId,
                        $productId,
                        $target['variationKey'],
                        (string) $target['sku'],
                        (int) $target['quantity'],
                        (int) $target['threshold'],
                    );
                    $this->recordMovement((string) $stockId, $productId, $target['variationKey'], (string) $target['sku'], (int) $target['quantity'], (int) $target['quantity'], $reference.':'.hash('sha256', (string) $stockId), 'synchronized');

                    continue;
                }

                if ((int) $target['quantity'] < (int) $current->reserved) {
                    throw new RuntimeException('Stock cannot be synchronized below the reserved quantity.');
                }
                $delta = (int) $target['quantity'] - (int) $current->on_hand;
                $this->database->table('inventory_stock_levels')->where('id', $stockId)->update([
                    'sku' => $target['sku'],
                    'on_hand' => $target['quantity'],
                    'low_stock_threshold' => $target['threshold'],
                    'version' => (int) $current->version + 1,
                    'updated_at' => now(),
                ]);
                if ($delta !== 0) {
                    $this->recordMovement((string) $stockId, $productId, $target['variationKey'], (string) $target['sku'], $delta, (int) $target['quantity'], $reference.':'.hash('sha256', (string) $stockId), 'synchronized');
                }
            }
        }, 3);
    }

    public function reserve(string $reservationId, string $productId, int $quantity, ?string $variationKey = null): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Reserved quantity must be at least one.');
        }

        $this->database->transaction(function () use ($reservationId, $productId, $quantity, $variationKey): void {
            $existing = $this->database->table('inventory_reservations')->where('id', $reservationId)->first();
            if ($existing !== null) {
                if ((string) $existing->product_id !== $productId || $existing->variation_key !== $variationKey || (int) $existing->quantity !== $quantity) {
                    throw new ReservationConflict('Reservation key was reused with different data.');
                }

                return;
            }

            $stock = $this->lockedLevel($productId, $variationKey);
            $available = $stock === null ? 0 : max(0, (int) $stock['on_hand'] - (int) $stock['reserved']);
            if ($stock === null || $available < $quantity) {
                throw InsufficientStock::forProduct($productId, $quantity, $available);
            }

            $this->database->table('inventory_stock_levels')->where('id', $stock['id'])->update([
                'reserved' => (int) $stock['reserved'] + $quantity,
                'version' => (int) $stock['version'] + 1,
                'updated_at' => now(),
            ]);
            $this->database->table('inventory_reservations')->insert([
                'id' => $reservationId,
                'stock_id' => $stock['id'],
                'product_id' => $productId,
                'variation_key' => $variationKey,
                'sku' => $stock['sku'],
                'quantity' => $quantity,
                'status' => 'active',
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->recordMovement(
                (string) $stock['id'],
                $productId,
                $variationKey,
                (string) $stock['sku'],
                0,
                (int) $stock['on_hand'],
                'reservation-reserved:'.$reservationId,
                'reservation_reserved',
                $reservationId,
                $quantity,
                (int) $stock['reserved'] + $quantity,
            );
        }, 3);
    }

    public function confirm(string $reservationId): void
    {
        $result = $this->database->transaction(function () use ($reservationId): string {
            $reservation = $this->database->table('inventory_reservations')->where('id', $reservationId)->lockForUpdate()->first();
            if ($reservation === null) {
                return 'missing';
            }
            if ($reservation->status === 'confirmed') {
                return 'confirmed';
            }
            if ($reservation->status !== 'active') {
                return (string) $reservation->status;
            }
            if (now()->greaterThanOrEqualTo($reservation->expires_at)) {
                $this->releaseLockedReservation((array) $reservation, 'expired', 'reservation_expired');

                return 'expired';
            }
            if ($reservation->stock_id === null) {
                throw new RuntimeException('Active reservation has no stock level.');
            }

            $stock = $this->database->table('inventory_stock_levels')->where('id', $reservation->stock_id)->lockForUpdate()->firstOrFail();
            $quantity = (int) $reservation->quantity;
            if ((int) $stock->reserved < $quantity || (int) $stock->on_hand < $quantity) {
                throw new RuntimeException('Stock balance is inconsistent with the active reservation.');
            }

            $onHand = (int) $stock->on_hand - $quantity;
            $reserved = (int) $stock->reserved - $quantity;
            $this->database->table('inventory_stock_levels')->where('id', $stock->id)->update([
                'on_hand' => $onHand,
                'reserved' => $reserved,
                'version' => (int) $stock->version + 1,
                'updated_at' => now(),
            ]);
            $this->database->table('inventory_reservations')->where('id', $reservationId)->update([
                'status' => 'confirmed',
                'updated_at' => now(),
            ]);
            $this->recordMovement(
                (string) $stock->id,
                (string) $reservation->product_id,
                $reservation->variation_key === null ? null : (string) $reservation->variation_key,
                (string) $reservation->sku,
                -$quantity,
                $onHand,
                'reservation-confirmed:'.$reservationId,
                'reservation_confirmed',
                $reservationId,
                -$quantity,
                $reserved,
            );

            return 'confirmed';
        }, 3);

        if ($result !== 'confirmed') {
            throw new ReservationConflict("Reservation cannot be confirmed from status {$result}.");
        }
    }

    public function release(string $reservationId): void
    {
        $this->database->transaction(function () use ($reservationId): void {
            $reservation = $this->database->table('inventory_reservations')->where('id', $reservationId)->lockForUpdate()->first();
            if ($reservation === null || $reservation->status !== 'active') {
                return;
            }

            $this->releaseLockedReservation((array) $reservation, 'released', 'reservation_released');
        }, 3);
    }

    public function expireDue(int $limit): int
    {
        $ids = $this->database->table('inventory_reservations')
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at')
            ->limit($limit)
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $expired = 0;
        foreach ($ids as $reservationId) {
            $changed = $this->database->transaction(function () use ($reservationId): bool {
                $reservation = $this->database->table('inventory_reservations')->where('id', $reservationId)->lockForUpdate()->first();
                if ($reservation === null || $reservation->status !== 'active' || now()->lessThan($reservation->expires_at)) {
                    return false;
                }

                $this->releaseLockedReservation((array) $reservation, 'expired', 'reservation_expired');

                return true;
            }, 3);

            if ($changed) {
                $expired++;
            }
        }

        return $expired;
    }

    /** @param array<string, mixed> $reservation */
    private function releaseLockedReservation(array $reservation, string $status, string $movementType): void
    {
        if ($reservation['stock_id'] === null) {
            throw new RuntimeException('Active reservation has no stock level.');
        }

        $stock = $this->database->table('inventory_stock_levels')->where('id', $reservation['stock_id'])->lockForUpdate()->firstOrFail();
        $quantity = (int) $reservation['quantity'];
        if ((int) $stock->reserved < $quantity) {
            throw new RuntimeException('Reserved stock is inconsistent with the active reservation.');
        }

        $reserved = (int) $stock->reserved - $quantity;
        $this->database->table('inventory_stock_levels')->where('id', $stock->id)->update([
            'reserved' => $reserved,
            'version' => (int) $stock->version + 1,
            'updated_at' => now(),
        ]);
        $this->database->table('inventory_reservations')->where('id', $reservation['id'])->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
        $this->recordMovement(
            (string) $stock->id,
            (string) $reservation['product_id'],
            $reservation['variation_key'] === null ? null : (string) $reservation['variation_key'],
            (string) $reservation['sku'],
            0,
            (int) $stock->on_hand,
            'reservation-'.$status.':'.$reservation['id'],
            $movementType,
            (string) $reservation['id'],
            -$quantity,
            $reserved,
        );
    }

    private function levelQuery(string $productId, ?string $variationKey): mixed
    {
        $query = $this->database->table('inventory_stock_levels')->where('product_id', $productId);

        return $variationKey === null ? $query : $query->where('variation_key', $variationKey);
    }

    /** @return array<string, mixed>|null */
    private function lockedLevel(string $productId, ?string $variationKey): ?array
    {
        $query = $this->database->table('inventory_stock_levels')->where('product_id', $productId);
        $variationKey === null ? $query->whereNull('variation_key') : $query->where('variation_key', $variationKey);

        $record = $query->lockForUpdate()->first();

        return $record === null ? null : (array) $record;
    }

    /** @param array<string, mixed> $stock */
    private function changeOnHand(array $stock, int $quantity, string $type, string $reference): void
    {
        $balance = (int) $stock['on_hand'] + $quantity;
        $this->database->table('inventory_stock_levels')->where('id', $stock['id'])->update([
            'on_hand' => $balance,
            'version' => (int) $stock['version'] + 1,
            'updated_at' => now(),
        ]);
        $this->recordMovement((string) $stock['id'], (string) $stock['product_id'], $stock['variation_key'] === null ? null : (string) $stock['variation_key'], (string) $stock['sku'], $quantity, $balance, $reference, $type);
    }

    private function recordMovement(
        string $stockId,
        string $productId,
        ?string $variationKey,
        string $sku,
        int $quantity,
        int $balance,
        string $reference,
        string $type,
        ?string $reservationId = null,
        int $reservedDelta = 0,
        ?int $reservedAfter = null,
    ): void {
        $this->database->table('inventory_movements')->insert([
            'stock_id' => $stockId,
            'product_id' => $productId,
            'variation_key' => $variationKey,
            'sku' => $sku,
            'reservation_id' => $reservationId,
            'type' => $type,
            'quantity' => $quantity,
            'reserved_delta' => $reservedDelta,
            'balance_after' => $balance,
            'reserved_after' => $reservedAfter,
            'reference' => mb_substr($reference, 0, 128),
            'created_at' => now(),
        ]);
    }

    private function insertLevel(string $id, string $productId, ?string $variationKey, string $sku, int $onHand, int $threshold): void
    {
        $this->database->table('inventory_stock_levels')->insert([
            'id' => $id,
            'product_id' => $productId,
            'variation_key' => $variationKey,
            'sku' => $sku,
            'on_hand' => $onHand,
            'reserved' => 0,
            'low_stock_threshold' => $threshold,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function stockId(string $productId, string $variationKey): string
    {
        return $productId.':'.$variationKey;
    }

    private function variationSku(string $baseSku, string $variationKey, mixed $configured): string
    {
        $configured = trim((string) $configured);

        return $configured !== '' ? mb_substr($configured, 0, 64) : mb_substr($baseSku, 0, 47).'-'.substr(hash('sha256', $variationKey), 0, 16);
    }

    /** @param array<string, mixed> $level */
    private function toLevel(array $level): StockLevel
    {
        return new StockLevel(
            (string) $level['id'],
            (string) $level['product_id'],
            $level['variation_key'] === null ? null : (string) $level['variation_key'],
            (string) $level['sku'],
            (int) $level['on_hand'],
            (int) $level['reserved'],
            (int) $level['low_stock_threshold'],
        );
    }
}
