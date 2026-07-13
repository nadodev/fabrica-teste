<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ShippingToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AdminShippingController extends Controller
{
    public function __construct(private readonly ShippingToken $tokens) {}

    public function edit(): Response
    {
        $settings = DB::table('shipping_settings')->where('id', 1)->first();

        return Inertia::render('admin/shipping', [
            'shipping' => [
                'isEnabled' => (bool) ($settings->is_enabled ?? false),
                'environment' => (string) ($settings->environment ?? 'sandbox'),
                'originZip' => (string) ($settings->origin_zip ?? ''),
                'hasToken' => $this->tokens->decode($settings->token ?? null) !== '',
                'tokenPreview' => $this->tokens->preview($settings->token ?? null),
                'token' => '',
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
            'token' => ['nullable', 'string', 'max:5000'],
            'options' => ['nullable', 'array'],
        ]);

        $current = DB::table('shipping_settings')->where('id', 1)->first();
        $currentToken = $current->token ?? null;
        $token = trim((string) ($data['token'] ?? ''));
        if ((bool) $data['isEnabled'] && $token === '' && $this->tokens->decode($currentToken) === '') {
            throw ValidationException::withMessages(['token' => 'Informe o token do Melhor Envio antes de ativar o frete.']);
        }
        if ($token === '' && $current !== null && (string) $current->environment !== (string) $data['environment']) {
            throw ValidationException::withMessages(['token' => 'Informe um token do ambiente selecionado ao trocar entre sandbox e producao.']);
        }

        DB::table('shipping_settings')->updateOrInsert(
            ['id' => 1],
            [
                'is_enabled' => (bool) $data['isEnabled'],
                'environment' => (string) $data['environment'],
                'origin_zip' => preg_replace('/\D+/', '', (string) ($data['originZip'] ?? '')),
                'options' => json_encode($data['options'] ?? [], JSON_THROW_ON_ERROR),
                'token' => $token === '' ? $currentToken : $this->tokens->encode($token),
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
}
