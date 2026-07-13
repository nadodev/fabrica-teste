<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\CouponCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AdminCouponController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/coupons/index', [
            'coupons' => DB::table('commerce_coupons')->orderByDesc('created_at')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/coupons/form', ['coupon' => null]);
    }

    public function store(Request $request, CouponCalculator $calculator): RedirectResponse
    {
        $data = $this->validated($request);
        $data['id'] = (string) Str::uuid();
        $data['code'] = $calculator->normalize((string) $data['code']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('commerce_coupons')->insert($data);

        return to_route('admin.coupons.index')->with('success', 'Cupom criado com sucesso.');
    }

    public function edit(string $coupon): Response
    {
        $record = DB::table('commerce_coupons')->where('id', $coupon)->first();
        abort_if($record === null, 404);

        return Inertia::render('admin/coupons/form', ['coupon' => $record]);
    }

    public function update(string $coupon, Request $request, CouponCalculator $calculator): RedirectResponse
    {
        $record = DB::table('commerce_coupons')->where('id', $coupon)->first();
        abort_if($record === null, 404);

        $data = $this->validated($request, $coupon);
        $data['code'] = $calculator->normalize((string) $data['code']);
        $data['updated_at'] = now();

        DB::table('commerce_coupons')->where('id', $coupon)->update($data);

        return to_route('admin.coupons.index')->with('success', 'Cupom atualizado com sucesso.');
    }

    public function destroy(string $coupon): RedirectResponse
    {
        DB::table('commerce_coupons')->where('id', $coupon)->delete();

        return to_route('admin.coupons.index')->with('success', 'Cupom removido.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?string $ignoreId = null): array
    {
        $unique = Rule::unique('commerce_coupons', 'code');
        if ($ignoreId !== null) {
            $unique->ignore($ignoreId, 'id');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', $unique],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'integer', 'min:1', 'max:10000000'],
            'minimum_amount' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['description'] = $data['description'] ?? '';
        $data['minimum_amount'] = (int) ($data['minimum_amount'] ?? 0);
        $data['usage_limit'] = $data['usage_limit'] === null ? null : (int) $data['usage_limit'];
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
