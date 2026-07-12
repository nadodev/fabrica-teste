<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Catalog\Application\Query\ListActiveProducts;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(ListActiveProducts $products): Response
    {
        return Inertia::render('home', [
            'banners' => DB::table('site_banners')->where('is_active', true)->orderBy('sort_order')->orderByDesc('created_at')->get(),
            'categories' => DB::table('site_categories')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'products' => $products->handle(),
            'stores' => DB::table('site_stores')->where('is_active', true)->orderBy('sort_order')->orderBy('city')->get(),
            'history' => DB::table('site_history_sections')->where('is_active', true)->latest('updated_at')->first(),
        ]);
    }
}
