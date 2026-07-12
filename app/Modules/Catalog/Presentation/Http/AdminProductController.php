<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\Command\CreateProduct;
use App\Modules\Catalog\Application\Query\ListAllProducts;
use App\Modules\Catalog\Presentation\Http\Request\StoreProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

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
        $command->handle(
            (string) Str::uuid(),
            (string) $data['sku'],
            (string) $data['name'],
            (string) ($data['description'] ?? ''),
            $request->priceInCents(),
            (string) $data['status'],
            isset($data['imageUrl']) ? (string) $data['imageUrl'] : null,
        );

        return to_route('admin.products.index')->with('success', 'Produto cadastrado com sucesso.');
    }
}
