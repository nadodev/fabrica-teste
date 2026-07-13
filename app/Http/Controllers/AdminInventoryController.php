<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Inventory\Application\Command\AdjustStock;
use App\Modules\Inventory\Application\Query\ShowInventoryDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class AdminInventoryController extends Controller
{
    public function index(ShowInventoryDashboard $query): Response
    {
        $dashboard = $query->handle();

        return Inertia::render('admin/inventory/index', [
            'stocks' => $dashboard->stocks,
            'movements' => $dashboard->movements,
        ]);
    }

    public function adjust(Request $request, AdjustStock $command): RedirectResponse
    {
        $data = $request->validate([
            'stock_id' => ['required', 'string', 'max:100', 'exists:inventory_stock_levels,id'],
            'quantity' => ['required', 'integer', 'min:-1000000', 'max:1000000', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:120'],
        ]);

        $reason = trim((string) ($data['reason'] ?? 'manual'));
        $command->handle('admin-adjustment-'.Str::uuid().'-'.hash('sha256', $reason), (string) $data['stock_id'], (int) $data['quantity']);

        return back()->with('success', 'Estoque ajustado.');
    }
}
