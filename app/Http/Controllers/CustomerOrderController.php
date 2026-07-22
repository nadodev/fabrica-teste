<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Ordering\Application\Query\ShowCustomerOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerOrderController extends Controller
{
    public function show(string $order, Request $request, ShowCustomerOrder $query): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $view = $query->handle($order, (int) $user->getAuthIdentifier());
        abort_if($view === null, 404);

        return Inertia::render('cliente/pedido', ['order' => $view]);
    }
}
