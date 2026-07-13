<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                'hasConfiguredToken' => $this->tokenConfigured(),
                'options' => $this->options($settings->options ?? null),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'isEnabled' => ['required', 'boolean'],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'originZip' => ['nullable', 'string', 'regex:/^(?:\D*\d){8}\D*$/'],
            'options' => ['nullable', 'array'],
            'options.freeShippingEnabled' => ['nullable', 'boolean'],
            'options.freeShippingMinimum' => ['nullable', 'decimal:0,2', 'min:0', 'max:1000000'],
            'options.estimatedDays' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        if ((bool) $data['isEnabled'] && ! $this->tokenConfigured()) {
            throw ValidationException::withMessages(['isEnabled' => 'Configure MELHOR_ENVIO_TOKEN no ambiente antes de ativar o frete.']);
        }

        if ((bool) $data['isEnabled'] && preg_replace('/\D+/', '', (string) ($data['originZip'] ?? '')) === '') {
            throw ValidationException::withMessages(['originZip' => 'Informe o CEP de origem antes de ativar o frete.']);
        }

        $options = (array) ($data['options'] ?? []);
        unset($options['pickupEnabled']);

        DB::table('shipping_settings')->updateOrInsert(
            ['id' => 1],
            [
                'is_enabled' => (bool) $data['isEnabled'],
                'environment' => (string) $data['environment'],
                'origin_zip' => preg_replace('/\D+/', '', (string) ($data['originZip'] ?? '')),
                'options' => json_encode($options, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return back()->with('success', 'Configuracao de frete salva.');
    }

    /** @return array<string, mixed> */
    private function options(mixed $options): array
    {
        $decoded = json_decode((string) ($options ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function tokenConfigured(): bool
    {
        $token = trim((string) config('services.melhor_envio.token'));

        return trim((string) preg_replace('/^Bearer\s+/i', '', $token)) !== '';
    }
}
