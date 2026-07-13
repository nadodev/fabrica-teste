<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Ordering\Application\Query\ListCustomerOrders;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerAccountController extends Controller
{
    public function __invoke(Request $request, ListCustomerOrders $orders): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return Inertia::render('cliente/conta', [
            'orders' => $orders->handle((int) $user->getAuthIdentifier()),
        ]);
    }
}
