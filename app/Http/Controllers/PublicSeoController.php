<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\StoreSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class PublicSeoController extends Controller
{
    public function sitemap(StoreSettings $settings): Response
    {
        abort_unless((bool) ($settings->seo()['sitemapEnabled'] ?? true), 404);

        $urls = collect(['/', '/produtos', '/empresas', '/escolas', '/personalizados', '/orcamento'])
            ->map(fn (string $path): string => url($path))
            ->merge(DB::table('catalog_products')->where('status', 'active')->pluck('id')->map(fn (string $id): string => route('produtos.show', ['product' => $id])))
            ->map(fn (string $url): string => '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>')
            ->implode('');

        return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(StoreSettings $settings): Response
    {
        $content = trim((string) ($settings->seo()['robotsContent'] ?? ''));
        if ($content === '') {
            $content = "User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml');
        }

        return response($content."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
