<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\Command\ArchiveProduct;
use App\Modules\Catalog\Application\Command\CreateProduct;
use App\Modules\Catalog\Application\Command\UpdateProduct;
use App\Modules\Catalog\Application\Query\FindProduct;
use App\Modules\Catalog\Application\Query\ListAllProducts;
use App\Modules\Catalog\Presentation\Http\Request\StoreProductRequest;
use App\Modules\Catalog\Presentation\Http\Request\UpdateProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

final class AdminProductController extends Controller
{
    public function index(ListAllProducts $query): Response
    {
        return Inertia::render('admin/products/index', ['products' => $query->handle()]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create');
    }

    public function store(StoreProductRequest $request, CreateProduct $command): RedirectResponse
    {
        $data = $request->validated();
        [$imageUrl, $storedPath] = $this->resolveNewImage($request->file('image'), $data['imageUrl'] ?? null);
        [$gallery, $storedGalleryPaths] = $this->storeGalleryImages($request->file('galleryImages', []));
        $variations = $request->variations();

        try {
            $productId = (string) Str::uuid();
            $command->handle(
                $productId,
                (string) $data['sku'],
                (string) $data['name'],
                (string) ($data['description'] ?? ''),
                $request->priceInCents(),
                (string) $data['status'],
                $imageUrl,
                (string) ($data['category'] ?? 'Uniformes'),
                $this->galleryWithMainImage($imageUrl, $gallery),
                $variations,
            );
            $this->syncStock($productId, $this->stockQuantity((int) ($data['stock'] ?? 100), $variations));
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }
            Storage::disk('public')->delete($storedGalleryPaths);

            throw $exception;
        }

        return to_route('admin.products.index')->with('success', 'Produto cadastrado com sucesso.');
    }

    public function edit(string $product, FindProduct $query): Response
    {
        $result = $query->handle($product);
        abort_if($result === null, 404);

        return Inertia::render('admin/products/edit', ['product' => $result]);
    }

    public function update(string $product, UpdateProductRequest $request, FindProduct $query, UpdateProduct $command): RedirectResponse
    {
        $current = $query->handle($product);
        abort_if($current === null, 404);
        $data = $request->validated();
        [$imageUrl, $storedPath] = $this->resolveUpdatedImage($request, $current->imageUrl);
        [$uploadedGallery, $storedGalleryPaths] = $this->storeGalleryImages($request->file('galleryImages', []));
        $gallery = [...$request->existingGalleryImages(), ...$uploadedGallery];
        $variations = $request->variations();

        try {
            $command->handle(
                $product,
                (string) $data['name'],
                (string) ($data['description'] ?? ''),
                $request->priceInCents(),
                (string) $data['status'],
                $imageUrl,
                (string) ($data['category'] ?? 'Uniformes'),
                $this->galleryWithMainImage($imageUrl, $gallery),
                $variations,
            );
            $this->syncStock($product, $this->stockQuantity((int) ($data['stock'] ?? $current->stockAvailable), $variations));
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }
            Storage::disk('public')->delete($storedGalleryPaths);

            throw $exception;
        }

        if ($current->imageUrl !== $imageUrl) {
            $this->deleteManagedImage($current->imageUrl);
        }
        $this->deleteRemovedGalleryImages($current->galleryImages, $this->galleryWithMainImage($imageUrl, $gallery));

        return to_route('admin.products.index')->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(string $product, ArchiveProduct $command): RedirectResponse
    {
        $command->handle($product);

        return to_route('admin.products.index')->with('success', 'Produto arquivado com sucesso.');
    }

    /** @return array{?string, ?string} */
    private function resolveNewImage(?UploadedFile $image, mixed $externalUrl): array
    {
        if ($image === null) {
            return [is_string($externalUrl) && $externalUrl !== '' ? $externalUrl : null, null];
        }

        $path = $image->store('products', 'public');

        if ($path === false) {
            throw new RuntimeException('Product image could not be stored.');
        }

        return ['/storage/'.$path, $path];
    }

    /** @return array{?string, ?string} */
    private function resolveUpdatedImage(UpdateProductRequest $request, ?string $currentUrl): array
    {
        if ($request->hasFile('image')) {
            return $this->resolveNewImage($request->file('image'), null);
        }

        if ($request->boolean('removeImage')) {
            return [null, null];
        }

        $externalUrl = $request->validated('imageUrl');

        return [is_string($externalUrl) && $externalUrl !== '' ? $externalUrl : $currentUrl, null];
    }

    private function deleteManagedImage(?string $url): void
    {
        if ($url !== null && str_starts_with($url, '/storage/products/')) {
            Storage::disk('public')->delete(substr($url, strlen('/storage/')));
        }
    }

    /** @param array<int, UploadedFile>|UploadedFile|null $files @return array{list<string>, list<string>} */
    private function storeGalleryImages(array|UploadedFile|null $files): array
    {
        $files = $files instanceof UploadedFile ? [$files] : ($files ?? []);
        $urls = [];
        $paths = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('products', 'public');

            if ($path === false) {
                throw new RuntimeException('Product gallery image could not be stored.');
            }

            $paths[] = $path;
            $urls[] = '/storage/'.$path;
        }

        return [$urls, $paths];
    }

    /** @param list<string> $gallery */
    private function galleryWithMainImage(?string $imageUrl, array $gallery): array
    {
        return array_values(array_unique(array_filter([
            $imageUrl,
            ...$gallery,
        ], fn (?string $url): bool => is_string($url) && $url !== '')));
    }

    /** @param list<string> $previous @param list<string> $next */
    private function deleteRemovedGalleryImages(array $previous, array $next): void
    {
        foreach (array_diff($previous, $next) as $url) {
            $this->deleteManagedImage($url);
        }
    }

    private function syncStock(string $productId, int $quantity): void
    {
        $exists = DB::table('inventory_stock')->where('product_id', $productId)->exists();

        if ($exists) {
            DB::table('inventory_stock')->where('product_id', $productId)->update([
                'on_hand' => $quantity,
                'reserved' => 0,
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('inventory_stock')->insert([
                'product_id' => $productId,
                'on_hand' => $quantity,
                'reserved' => 0,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
        ]);
    }

    /** @param list<array{id?: string, name: string, value: string, stock: int, lowStockThreshold: int}> $variations */
    private function stockQuantity(int $fallback, array $variations): int
    {
        if ($variations === []) {
            return $fallback;
        }

        return array_sum(array_map(fn (array $variation): int => max(0, (int) $variation['stock']), $variations));
    }
}
