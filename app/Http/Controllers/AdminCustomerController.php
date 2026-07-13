<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class AdminCustomerController extends Controller
{
    public function index(): Response
    {
        $customers = DB::table('ordering_orders')
            ->whereNotNull('customer_email')
            ->select(
                DB::raw('MIN(id) as id'),
                DB::raw('MAX(customer_name) as name'),
                'customer_email as email',
                DB::raw('MAX(customer_phone) as phone'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('MAX(created_at) as last_order_at'),
            )
            ->groupBy('customer_email')
            ->orderByDesc('last_order_at')
            ->get();

        return Inertia::render('admin/customers/index', ['customers' => $customers]);
    }

    public function show(string $email): Response
    {
        $orders = DB::table('ordering_orders')
            ->where('customer_email', $email)
            ->orderByDesc('created_at')
            ->get();

        abort_if($orders->isEmpty(), 404);
        $first = $orders->first();

        return Inertia::render('admin/customers/show', [
            'customer' => [
                'name' => $first->customer_name,
                'email' => $first->customer_email,
                'phone' => $first->customer_phone,
                'document' => $first->customer_document,
                'ordersCount' => $orders->count(),
                'totalAmount' => (int) $orders->sum('total_amount'),
            ],
            'orders' => $orders,
        ]);
    }
}
