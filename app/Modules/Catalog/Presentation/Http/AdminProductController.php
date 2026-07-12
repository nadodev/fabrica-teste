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

        try {
            $command->handle(
                (string) Str::uuid(),
                (string) $data['sku'],
                (string) $data['name'],
                (string) ($data['description'] ?? ''),
                $request->priceInCents(),
                (string) $data['status'],
                $imageUrl,
            );
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

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

        try {
            $command->handle($product, (string) $data['name'], (string) ($data['description'] ?? ''), $request->priceInCents(), (string) $data['status'], $imageUrl);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('public')->delete($storedPath);
            }

            throw $exception;
        }

        if ($current->imageUrl !== $imageUrl) {
            $this->deleteManagedImage($current->imageUrl);
        }

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
}
