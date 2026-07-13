<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AdminCatalogCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/catalog-categories/index', [
            'categories' => DB::table('catalog_categories')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image_url'] = $this->storeImage($request->file('image')) ?? $data['image_url'];
        $data['id'] = (string) Str::uuid();
        $data['slug'] = $data['slug'] ?: Str::slug((string) $data['name']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('catalog_categories')->insert($data);

        return back()->with('success', 'Categoria criada.');
    }

    public function update(string $category, Request $request): RedirectResponse
    {
        $data = $this->validated($request, $category);
        $currentImage = DB::table('catalog_categories')->where('id', $category)->value('image_url');
        $data['image_url'] = $this->storeImage($request->file('image')) ?? $data['image_url'] ?? $currentImage;
        $data['slug'] = $data['slug'] ?: Str::slug((string) $data['name']);
        $data['updated_at'] = now();

        DB::table('catalog_categories')->where('id', $category)->update($data);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(string $category): RedirectResponse
    {
        DB::table('catalog_categories')->where('id', $category)->delete();

        return back()->with('success', 'Categoria removida.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?string $ignore = null): array
    {
        $unique = Rule::unique('catalog_categories', 'name');
        if ($ignore !== null) {
            $unique->ignore($ignore, 'id');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', $unique],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('catalog_categories', 'slug')->ignore($ignore, 'id')],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['description'] = $data['description'] ?? '';
        $data['image_url'] = $data['image_url'] ?? null;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** @param UploadedFile|array<int, UploadedFile>|null $file */
    private function storeImage(UploadedFile|array|null $file): ?string
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        $path = $file->store('categories', 'public');

        return $path === false ? null : '/storage/'.$path;
    }
}
