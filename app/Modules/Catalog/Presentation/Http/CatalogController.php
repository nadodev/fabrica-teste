<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\Query\FindProduct;
use App\Modules\Catalog\Application\Query\ListActiveProducts;
use Inertia\Inertia;
use Inertia\Response;

final class CatalogController extends Controller
{
    public function index(ListActiveProducts $query): Response
    {
        return Inertia::render('produtos', ['products' => $query->handle()]);
    }

    public function show(string $product, FindProduct $query): Response
    {
        $result = $query->handle($product);
        abort_if($result === null, 404);

        return Inertia::render('produto-detalhe', ['product' => $result]);
    }
}
