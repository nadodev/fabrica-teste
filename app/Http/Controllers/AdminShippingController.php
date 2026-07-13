<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AdminShippingController extends Controller
{
    public function edit(): Response
    {
        $settings = DB::table('shipping_settings')->where('id', 1)->first();

        return Inertia::render('admin/shipping', [
            'shipping' => [
                'isEnabled' => (bool) ($settings->is_enabled ?? false),
                'environment' => (string) ($settings->environment ?? 'sandbox'),
                'originZip' => (string) ($settings->origin_zip ?? ''),
                'hasToken' => is_string($settings->token ?? null) && trim((string) $settings->token) !== '',
                'tokenPreview' => $this->tokenPreview($settings->token ?? null),
                'token' => (string) ($settings->token ?? ''),
                'options' => $this->options($settings->options ?? null),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'isEnabled' => ['required', 'boolean'],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'originZip' => ['nullable', 'string', 'max:20'],
            'token' => ['nullable', 'string', 'max:5000'],
            'options' => ['nullable', 'array'],
        ]);

        $currentToken = DB::table('shipping_settings')->where('id', 1)->value('token');
        $token = trim((string) ($data['token'] ?? ''));

        DB::table('shipping_settings')->updateOrInsert(
            ['id' => 1],
            [
                'is_enabled' => (bool) $data['isEnabled'],
                'environment' => (string) $data['environment'],
                'origin_zip' => preg_replace('/\D+/', '', (string) ($data['originZip'] ?? '')),
                'options' => json_encode($data['options'] ?? [], JSON_THROW_ON_ERROR),
                'token' => $token === '' ? $currentToken : $token,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return back()->with('success', 'Configuracao de frete salva.');
    }

    private function tokenPreview(mixed $token): ?string
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        return 'salvo, termina em '.substr(trim($token), -6);
    }

    /** @return array<string, mixed> */
    private function options(mixed $options): array
    {
        $decoded = json_decode((string) ($options ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }
}
