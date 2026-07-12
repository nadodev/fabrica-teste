<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class AdminSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/settings', [
            'settings' => $this->settings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'storeName' => ['required', 'string', 'max:120'],
            'primaryColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondaryColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
        ]);
        $settings = $this->settings();
        $logoUrl = $settings->logo_url;

        if ($request->hasFile('logo')) {
            $logoUrl = $this->storeLogo($request->file('logo'));
            $this->deleteManagedLogo($settings->logo_url);
        }

        DB::table('site_settings')->updateOrInsert(
            ['id' => 1],
            [
                'store_name' => $data['storeName'],
                'primary_color' => $data['primaryColor'],
                'secondary_color' => $data['secondaryColor'],
                'logo_url' => $logoUrl,
                'updated_at' => now(),
                'created_at' => $settings->created_at ?? now(),
            ],
        );

        return back()->with('success', 'Configuracoes atualizadas com sucesso.');
    }

    private function settings(): object
    {
        $settings = DB::table('site_settings')->where('id', 1)->first();

        if ($settings !== null) {
            return $settings;
        }

        return (object) [
            'id' => 1,
            'store_name' => 'Fabrica de Fardamentos',
            'logo_url' => '/logo.png',
            'primary_color' => '#123a6b',
            'secondary_color' => '#f5c542',
            'created_at' => now(),
        ];
    }

    private function storeLogo(?UploadedFile $logo): ?string
    {
        if ($logo === null) {
            return null;
        }

        $path = $logo->store('site', 'public');

        if ($path === false) {
            throw new RuntimeException('Logo could not be stored.');
        }

        return '/storage/'.$path;
    }

    private function deleteManagedLogo(mixed $url): void
    {
        if (is_string($url) && str_starts_with($url, '/storage/site/')) {
            Storage::disk('public')->delete(substr($url, strlen('/storage/')));
        }
    }
}
