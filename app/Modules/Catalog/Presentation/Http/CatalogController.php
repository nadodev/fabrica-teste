<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Application\Query\FindProduct;
use App\Modules\Catalog\Application\Query\ListActiveProducts;
use App\Support\StoreSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CatalogController extends Controller
{
    public function index(Request $request, ListActiveProducts $query, StoreSettings $settings): Response
    {
        $search = trim((string) $request->query('busca', ''));
        $category = trim((string) $request->query('categoria', ''));
        $minPrice = (int) $request->query('preco_min', 0);
        $maxPrice = (int) $request->query('preco_max', 0);
        $stock = (string) $request->query('estoque', '');
        $variation = trim((string) $request->query('variacao', ''));
        $sort = (string) $request->query('ordenar', 'relevancia');
        $allProducts = $query->handle();
        $categories = collect($allProducts)->pluck('category')->filter()->unique()->sort()->values()->all();
        $products = array_values(array_filter($allProducts, function (object $product) use ($search, $category): bool {
            if ($category !== '' && $product->category !== $category) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $needle = mb_strtolower($search);

            return str_contains(mb_strtolower($product->name), $needle)
                || str_contains(mb_strtolower($product->sku), $needle)
                || str_contains(mb_strtolower($product->category), $needle)
                || str_contains(mb_strtolower($product->description), $needle);
        }));
        $products = array_values(array_filter($products, function (object $product) use ($minPrice, $maxPrice, $stock, $variation): bool {
            if ($minPrice > 0 && $product->priceAmount < $minPrice * 100) {
                return false;
            }
            if ($maxPrice > 0 && $product->priceAmount > $maxPrice * 100) {
                return false;
            }
            if ($stock === 'disponivel' && $product->stockAvailable < 1) {
                return false;
            }
            if ($variation !== '') {
                $needle = mb_strtolower($variation);
                $hasVariation = collect($product->variations)->contains(fn (array $row): bool => str_contains(mb_strtolower((string) ($row['name'] ?? '').' '.(string) ($row['value'] ?? '')), $needle));
                if (! $hasVariation) {
                    return false;
                }
            }

            return true;
        }));

        $products = match ($sort) {
            'menor-preco' => collect($products)->sortBy('priceAmount')->values()->all(),
            'maior-preco' => collect($products)->sortByDesc('priceAmount')->values()->all(),
            'nome' => collect($products)->sortBy('name')->values()->all(),
            default => $products,
        };
        $perPage = min(100, max(4, (int) ($settings->appearance()['productsPerPage'] ?? 12)));
        $total = count($products);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($lastPage, max(1, (int) $request->query('pagina', 1)));
        $products = array_slice($products, ($page - 1) * $perPage, $perPage);

        return Inertia::render('produtos', [
            'products' => $products,
            'categories' => $categories,
            'pagination' => ['currentPage' => $page, 'lastPage' => $lastPage, 'perPage' => $perPage, 'total' => $total],
            'filters' => ['busca' => $search, 'categoria' => $category, 'preco_min' => $minPrice, 'preco_max' => $maxPrice, 'estoque' => $stock, 'variacao' => $variation, 'ordenar' => $sort],
        ]);
    }

    public function show(string $product, FindProduct $query): Response
    {
        $result = $query->handle($product);
        abort_if($result === null, 404);

        return Inertia::render('produto-detalhe', ['product' => $result]);
    }
}
