<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class AdminSiteContentController extends Controller
{
    private const TYPES = [
        'categorias' => ['table' => 'site_categories', 'title' => 'Categorias', 'image' => true, 'fields' => ['name', 'description', 'link_url', 'sort_order', 'is_active']],
        'banners' => ['table' => 'site_banners', 'title' => 'Banners', 'image' => true, 'fields' => ['eyebrow', 'title', 'subtitle', 'button_label', 'link_url', 'sort_order', 'is_active']],
        'notificacoes' => ['table' => 'site_topbar_notifications', 'title' => 'Notificacoes', 'image' => false, 'fields' => ['message', 'link_label', 'link_url', 'starts_at', 'ends_at', 'sort_order', 'is_active']],
        'lojas' => ['table' => 'site_stores', 'title' => 'Lojas', 'image' => false, 'fields' => ['type', 'city', 'address', 'phone', 'hours', 'map_url', 'sort_order', 'is_active']],
        'historia' => ['table' => 'site_history_sections', 'title' => 'Nossa historia', 'image' => false, 'fields' => ['eyebrow', 'title', 'body', 'mission', 'vision', 'values', 'is_active']],
    ];

    public function index(string $type): Response
    {
        $config = $this->config($type);

        return Inertia::render('admin/content/index', [
            'type' => $type,
            'title' => $config['title'],
            'items' => DB::table($config['table'])->orderBy('sort_order')->orderByDesc('created_at')->get(),
        ]);
    }

    public function create(string $type): Response
    {
        $config = $this->config($type);

        return Inertia::render('admin/content/form', [
            'type' => $type,
            'title' => 'Novo '.$config['title'],
            'item' => null,
            'hasImage' => $config['image'],
        ]);
    }

    public function store(string $type, Request $request): RedirectResponse
    {
        $config = $this->config($type);
        $data = $this->validated($request, $type, $config);
        $data['id'] = (string) Str::uuid();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        if ($config['image']) {
            $data['image_url'] = $this->storeImage($request->file('image'));
        }

        DB::table($config['table'])->insert($data);

        return to_route('admin.content.index', ['type' => $type])->with('success', 'Conteudo criado com sucesso.');
    }

    public function edit(string $type, string $id): Response
    {
        $config = $this->config($type);
        $item = DB::table($config['table'])->where('id', $id)->first();
        abort_if($item === null, 404);

        return Inertia::render('admin/content/form', [
            'type' => $type,
            'title' => 'Editar '.$config['title'],
            'item' => $item,
            'hasImage' => $config['image'],
        ]);
    }

    public function update(string $type, string $id, Request $request): RedirectResponse
    {
        $config = $this->config($type);
        $current = DB::table($config['table'])->where('id', $id)->first();
        abort_if($current === null, 404);
        $data = $this->validated($request, $type, $config);
        $data['updated_at'] = now();

        if ($config['image'] && $request->hasFile('image')) {
            $data['image_url'] = $this->storeImage($request->file('image'));
            $this->deleteManagedImage($current->image_url ?? null);
        }

        DB::table($config['table'])->where('id', $id)->update($data);

        return to_route('admin.content.index', ['type' => $type])->with('success', 'Conteudo atualizado com sucesso.');
    }

    public function destroy(string $type, string $id): RedirectResponse
    {
        $config = $this->config($type);
        $current = DB::table($config['table'])->where('id', $id)->first();
        abort_if($current === null, 404);
        $this->deleteManagedImage($current->image_url ?? null);
        DB::table($config['table'])->where('id', $id)->delete();

        return to_route('admin.content.index', ['type' => $type])->with('success', 'Conteudo removido com sucesso.');
    }

    /** @return array{table: string, title: string, image: bool, fields: list<string>} */
    private function config(string $type): array
    {
        abort_if(! isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    /** @param array{fields: list<string>} $config @return array<string, mixed> */
    private function validated(Request $request, string $type, array $config): array
    {
        $rules = ['is_active' => ['sometimes', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000']];
        foreach ($config['fields'] as $field) {
            $rules[$field] ??= in_array($field, ['body'], true) ? ['nullable', 'string', 'max:5000'] : ['nullable', 'string', 'max:255'];
        }
        if ($config['image']) {
            $rules['image'] = ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
        }
        foreach (['name', 'title', 'city'] as $required) {
            if (in_array($required, $config['fields'], true)) {
                $rules[$required] = ['required', 'string', 'max:160'];
            }
        }
        if (in_array('message', $config['fields'], true)) {
            $rules['message'] = ['required', 'string', 'max:180'];
            $rules['starts_at'] = ['nullable', 'date'];
            $rules['ends_at'] = ['nullable', 'date', 'after_or_equal:starts_at'];
        }

        $data = $request->validate($rules);
        unset($data['image']);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function storeImage(mixed $image): ?string
    {
        if (! $image instanceof UploadedFile) {
            return null;
        }
        $path = $image->store('site', 'public');
        if ($path === false) {
            throw new RuntimeException('Image could not be stored.');
        }

        return '/storage/'.$path;
    }

    private function deleteManagedImage(mixed $url): void
    {
        if (is_string($url) && str_starts_with($url, '/storage/site/')) {
            Storage::disk('public')->delete(substr($url, strlen('/storage/')));
        }
    }
}
