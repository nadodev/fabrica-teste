<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->recoverPartialAttempt();

        Schema::create('inventory_stock_levels', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->uuid('product_id')->index();
            $table->string('variation_key', 80)->nullable();
            $table->string('sku', 64)->unique();
            $table->unsignedBigInteger('on_hand')->default(0);
            $table->unsignedBigInteger('reserved')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'variation_key']);
            $table->foreign('product_id')->references('id')->on('catalog_products')->cascadeOnDelete();
        });

        Schema::create('inventory_reservations_v2', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('stock_id', 100)->nullable()->index();
            $table->uuid('product_id')->index();
            $table->string('variation_key', 80)->nullable();
            $table->string('sku', 64);
            $table->unsignedInteger('quantity');
            $table->string('status', 16)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->foreign('stock_id')->references('id')->on('inventory_stock_levels')->restrictOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
        });

        Schema::create('inventory_movements_v2', function (Blueprint $table): void {
            $table->id();
            $table->string('stock_id', 100)->nullable()->index();
            $table->uuid('product_id')->index();
            $table->string('variation_key', 80)->nullable();
            $table->string('sku', 64);
            $table->string('type', 32);
            $table->bigInteger('quantity');
            $table->unsignedBigInteger('balance_after');
            $table->string('reference', 128)->unique();
            $table->timestamp('created_at');
            $table->foreign('stock_id')->references('id')->on('inventory_stock_levels')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
        });

        $products = DB::table('catalog_products')->orderBy('id')->get();
        $levelsByProduct = [];

        foreach ($products as $product) {
            $legacy = DB::table('inventory_stock')->where('product_id', $product->id)->first();
            $variations = $this->decodeVariations($product->variations ?? null);
            $levelsByProduct[(string) $product->id] = [];

            if ($variations === []) {
                $stockId = (string) $product->id;
                $levelsByProduct[(string) $product->id][''] = $stockId;
                $this->insertLevel(
                    $stockId,
                    (string) $product->id,
                    null,
                    (string) $product->sku,
                    (int) ($legacy->on_hand ?? 0),
                    (int) ($legacy->reserved ?? 0),
                    5,
                    (int) ($legacy->version ?? 0),
                    $legacy->created_at ?? now(),
                    $legacy->updated_at ?? now(),
                );

                continue;
            }

            $clean = [];
            $jsonTotal = 0;
            foreach ($variations as $variation) {
                $variationKey = (string) $variation['id'];
                $stockId = $this->stockId((string) $product->id, $variationKey);
                $sku = $this->variationSku((string) $product->sku, $variationKey, $variation['sku'] ?? null);
                $quantity = max(0, (int) ($variation['stock'] ?? 0));
                $threshold = max(0, (int) ($variation['lowStockThreshold'] ?? 5));
                $jsonTotal += $quantity;
                $levelsByProduct[(string) $product->id][$variationKey] = $stockId;
                $this->insertLevel($stockId, (string) $product->id, $variationKey, $sku, $quantity, 0, $threshold, 0, now(), now());
                $clean[] = [
                    'id' => $variationKey,
                    'name' => (string) $variation['name'],
                    'value' => (string) $variation['value'],
                    'sku' => $sku,
                ];
            }

            if ($legacy !== null && (int) $legacy->on_hand !== $jsonTotal) {
                throw new RuntimeException("Inventory mismatch for product {$product->sku}: aggregate and variation totals differ.");
            }

            if ($legacy !== null && (int) $legacy->reserved > 0) {
                throw new RuntimeException("Product {$product->sku} has aggregate reservations that cannot be assigned safely to a variation.");
            }

            DB::table('catalog_products')->where('id', $product->id)->update([
                'variations' => json_encode($clean, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('inventory_reservations')->orderBy('created_at')->get() as $reservation) {
            $productId = (string) $reservation->product_id;
            $stockId = $levelsByProduct[$productId][''] ?? null;
            $sku = (string) DB::table('catalog_products')->where('id', $productId)->value('sku');
            $expired = (string) $reservation->status === 'active' && now()->greaterThan($reservation->expires_at);

            if ($stockId === null && (string) $reservation->status === 'active' && ! $expired) {
                throw new RuntimeException("Active reservation {$reservation->id} cannot be assigned safely to a variation.");
            }

            DB::table('inventory_reservations_v2')->insert([
                'id' => $reservation->id,
                'stock_id' => $stockId,
                'product_id' => $productId,
                'variation_key' => null,
                'sku' => $sku,
                'quantity' => $reservation->quantity,
                'status' => $expired ? 'expired' : $reservation->status,
                'expires_at' => $reservation->expires_at,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
            ]);
        }

        foreach (DB::table('inventory_movements')->orderBy('id')->get() as $movement) {
            $productId = (string) $movement->product_id;
            DB::table('inventory_movements_v2')->insert([
                'stock_id' => $levelsByProduct[$productId][''] ?? null,
                'product_id' => $productId,
                'variation_key' => null,
                'sku' => (string) DB::table('catalog_products')->where('id', $productId)->value('sku'),
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'balance_after' => $movement->balance_after,
                'reference' => $movement->reference,
                'created_at' => $movement->created_at,
            ]);
        }

        Schema::drop('inventory_movements');
        Schema::drop('inventory_reservations');
        Schema::drop('inventory_stock');
        Schema::rename('inventory_movements_v2', 'inventory_movements');
        Schema::rename('inventory_reservations_v2', 'inventory_reservations');
    }

    public function down(): void
    {
        Schema::create('inventory_stock', function (Blueprint $table): void {
            $table->uuid('product_id')->primary();
            $table->unsignedBigInteger('on_hand')->default(0);
            $table->unsignedBigInteger('reserved')->default(0);
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('catalog_products')->cascadeOnDelete();
        });

        foreach (DB::table('catalog_products')->orderBy('id')->get() as $product) {
            $levels = DB::table('inventory_stock_levels')->where('product_id', $product->id)->orderBy('variation_key')->get();
            DB::table('inventory_stock')->insert([
                'product_id' => $product->id,
                'on_hand' => $levels->sum('on_hand'),
                'reserved' => $levels->sum('reserved'),
                'version' => $levels->max('version') ?? 0,
                'created_at' => $levels->min('created_at') ?? now(),
                'updated_at' => $levels->max('updated_at') ?? now(),
            ]);

            $variations = $this->decodeVariations($product->variations ?? null);
            if ($variations !== []) {
                $restored = array_map(function (array $variation) use ($levels): array {
                    $level = $levels->firstWhere('variation_key', $variation['id']);
                    unset($variation['sku']);
                    $stock = (int) ($level->on_hand ?? 0);
                    $threshold = (int) ($level->low_stock_threshold ?? 5);

                    return [
                        ...$variation,
                        'stock' => $stock,
                        'lowStockThreshold' => $threshold,
                        'purchasable' => $stock > 0,
                        'lowStock' => $stock <= $threshold,
                    ];
                }, $variations);
                DB::table('catalog_products')->where('id', $product->id)->update(['variations' => json_encode($restored, JSON_THROW_ON_ERROR)]);
            }
        }

        Schema::create('inventory_reservations_legacy', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->index();
            $table->unsignedInteger('quantity');
            $table->string('status', 16)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
        });

        DB::table('inventory_reservations')->orderBy('created_at')->get()->each(function (object $reservation): void {
            DB::table('inventory_reservations_legacy')->insert([
                'id' => $reservation->id,
                'product_id' => $reservation->product_id,
                'quantity' => $reservation->quantity,
                'status' => $reservation->status,
                'expires_at' => $reservation->expires_at,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at,
            ]);
        });

        Schema::create('inventory_movements_legacy', function (Blueprint $table): void {
            $table->id();
            $table->uuid('product_id')->index();
            $table->string('type', 32);
            $table->bigInteger('quantity');
            $table->unsignedBigInteger('balance_after');
            $table->string('reference', 128)->unique();
            $table->timestamp('created_at');
            $table->foreign('product_id')->references('id')->on('catalog_products')->restrictOnDelete();
        });

        DB::table('inventory_movements')->orderBy('id')->get()->each(function (object $movement): void {
            DB::table('inventory_movements_legacy')->insert([
                'product_id' => $movement->product_id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'balance_after' => $movement->balance_after,
                'reference' => $movement->reference,
                'created_at' => $movement->created_at,
            ]);
        });

        Schema::drop('inventory_movements');
        Schema::drop('inventory_reservations');
        Schema::drop('inventory_stock_levels');
        Schema::rename('inventory_movements_legacy', 'inventory_movements');
        Schema::rename('inventory_reservations_legacy', 'inventory_reservations');
    }

    /** @return list<array<string, mixed>> */
    private function decodeVariations(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    private function stockId(string $productId, string $variationKey): string
    {
        return $productId.':'.$variationKey;
    }

    private function variationSku(string $productSku, string $variationKey, mixed $configured): string
    {
        $configured = trim((string) $configured);
        if ($configured !== '') {
            return mb_substr($configured, 0, 64);
        }

        return mb_substr($productSku, 0, 47).'-'.substr(hash('sha256', $variationKey), 0, 16);
    }

    private function insertLevel(
        string $id,
        string $productId,
        ?string $variationKey,
        string $sku,
        int $onHand,
        int $reserved,
        int $threshold,
        int $version,
        mixed $createdAt,
        mixed $updatedAt,
    ): void {
        DB::table('inventory_stock_levels')->insert([
            'id' => $id,
            'product_id' => $productId,
            'variation_key' => $variationKey,
            'sku' => $sku,
            'on_hand' => $onHand,
            'reserved' => $reserved,
            'low_stock_threshold' => $threshold,
            'version' => $version,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }

    private function recoverPartialAttempt(): void
    {
        if (! Schema::hasTable('inventory_stock_levels')) {
            return;
        }

        foreach (DB::table('catalog_products')->whereNotNull('variations')->get(['id', 'variations']) as $product) {
            $variations = $this->decodeVariations($product->variations);
            if ($variations === []) {
                continue;
            }

            $restored = array_map(function (array $variation) use ($product): array {
                $level = DB::table('inventory_stock_levels')
                    ->where('product_id', $product->id)
                    ->where('variation_key', $variation['id'])
                    ->first();

                if ($level === null) {
                    return $variation;
                }

                return [
                    ...$variation,
                    'stock' => (int) $level->on_hand,
                    'lowStockThreshold' => (int) $level->low_stock_threshold,
                ];
            }, $variations);

            DB::table('catalog_products')->where('id', $product->id)->update([
                'variations' => json_encode($restored, JSON_THROW_ON_ERROR),
            ]);
        }

        Schema::dropIfExists('inventory_movements_v2');
        Schema::dropIfExists('inventory_reservations_v2');
        Schema::dropIfExists('inventory_stock_levels');
    }
};
