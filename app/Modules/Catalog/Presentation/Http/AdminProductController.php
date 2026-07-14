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
use App\Modules\Inventory\Application\Port\StockManager;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AdminProductController extends Controller
{
    public function index(ListAllProducts $query): Response
    {
        return Inertia::render('admin/products/index', ['products' => $query->handle()]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create', ['categories' => $this->categories()]);
    }

    public function export(): StreamedResponse
    {
        abort_unless((bool) (app(StoreSettings::class)->system()['productImportExport'] ?? true), 404);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                throw new RuntimeException('Export stream could not be opened.');
            }
            fputcsv($handle, ['sku', 'name', 'description', 'category', 'price', 'stock', 'weight_grams', 'width_cm', 'height_cm', 'length_cm', 'status']);

            $stockTotals = DB::table('inventory_stock_levels')
                ->select('product_id')
                ->selectRaw('SUM(on_hand) AS on_hand')
                ->groupBy('product_id');

            DB::table('catalog_products')
                ->leftJoinSub($stockTotals, 'inventory_totals', 'inventory_totals.product_id', '=', 'catalog_products.id')
                ->orderBy('catalog_products.name')
                ->select('catalog_products.*', 'inventory_totals.on_hand')
                ->get()
                ->each(function (object $product) use ($handle): void {
                    fputcsv($handle, [
                        $product->sku,
                        $product->name,
                        $product->description,
                        $product->category,
                        number_format(((int) $product->price_amount) / 100, 2, '.', ''),
                        (int) ($product->on_hand ?? 0),
                        (int) ($product->weight_grams ?? 300),
                        (int) ($product->width_centimeters ?? 20),
                        (int) ($product->height_centimeters ?? 5),
                        (int) ($product->length_centimeters ?? 30),
                        $product->status,
                    ]);
                });

            fclose($handle);
        }, 'produtos.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, StockManager $stock): RedirectResponse
    {
        abort_unless((bool) (app(StoreSettings::class)->system()['productImportExport'] ?? true), 404);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $file = $data['file'];
        if (! $file instanceof UploadedFile) {
            return back()->withErrors(['file' => 'Arquivo invalido.']);
        }

        $rows = array_map('str_getcsv', file($file->getRealPath()) ?: []);
        $header = array_map(fn (?string $value): string => trim((string) $value), array_shift($rows) ?? []);
        $created = 0;

        DB::transaction(function () use ($rows, $header, &$created, $stock): void {
            foreach ($rows as $row) {
                if (count($header) !== count($row)) {
                    continue;
                }
                $data = array_combine($header, $row);
                if (trim((string) ($data['sku'] ?? '')) === '' || trim((string) ($data['name'] ?? '')) === '') {
                    continue;
                }

                $id = (string) (DB::table('catalog_products')->where('sku', $data['sku'])->value('id') ?? Str::uuid());
                $price = (int) round(((float) str_replace(',', '.', (string) ($data['price'] ?? '0'))) * 100);

                DB::table('catalog_products')->updateOrInsert(['id' => $id], [
                    'sku' => mb_substr((string) $data['sku'], 0, 64),
                    'name' => mb_substr((string) $data['name'], 0, 160),
                    'description' => (string) ($data['description'] ?? ''),
                    'category' => mb_substr((string) ($data['category'] ?? 'Uniformes'), 0, 80),
                    'price_amount' => max(0, $price),
                    'price_currency' => 'BRL',
                    'weight_grams' => max(1, min(30000, (int) ($data['weight_grams'] ?? 300))),
                    'width_centimeters' => max(1, min(200, (int) ($data['width_cm'] ?? 20))),
                    'height_centimeters' => max(1, min(200, (int) ($data['height_cm'] ?? 5))),
                    'length_centimeters' => max(1, min(200, (int) ($data['length_cm'] ?? 30))),
                    'status' => in_array($data['status'] ?? 'draft', ['draft', 'active'], true) ? $data['status'] : 'draft',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);

                $stock->synchronizeProduct('catalog-import-'.hash('sha256', (string) $data['sku']), $id, (string) $data['sku'], max(0, (int) ($data['stock'] ?? 0)), []);
                $created++;
            }
        });

        return back()->with('success', "{$created} produto(s) importado(s).");
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
                (int) ($data['stock'] ?? 100),
                (int) ($data['weightGrams'] ?? 300),
                (int) ($data['widthCentimeters'] ?? 20),
                (int) ($data['heightCentimeters'] ?? 5),
                (int) ($data['lengthCentimeters'] ?? 30),
            );
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

        return Inertia::render('admin/products/edit', ['product' => $result, 'categories' => $this->categories()]);
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
                (int) ($data['stock'] ?? $current->stockAvailable),
                (int) ($data['weightGrams'] ?? $current->weightGrams),
                (int) ($data['widthCentimeters'] ?? $current->widthCentimeters),
                (int) ($data['heightCentimeters'] ?? $current->heightCentimeters),
                (int) ($data['lengthCentimeters'] ?? $current->lengthCentimeters),
            );
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

    /**
     * @param  array<int, mixed>|UploadedFile|null  $files
     * @return array{list<string>, list<string>}
     */
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

    /**
     * @param  list<string>  $gallery
     * @return list<string>
     */
    private function galleryWithMainImage(?string $imageUrl, array $gallery): array
    {
        return array_values(array_unique(array_filter([
            $imageUrl,
            ...$gallery,
        ], fn (?string $url): bool => is_string($url) && $url !== '')));
    }

    /**
     * @param  list<string>  $previous
     * @param  list<string>  $next
     */
    private function deleteRemovedGalleryImages(array $previous, array $next): void
    {
        foreach (array_diff($previous, $next) as $url) {
            $this->deleteManagedImage($url);
        }
    }

    /** @return list<string> */
    private function categories(): array
    {
        return array_values(DB::table('catalog_categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (mixed $name): string => (string) $name)
            ->all());
    }
}
