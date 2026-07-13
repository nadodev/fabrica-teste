<?php

declare(strict_types=1);

namespace App\Modules\Cart\Presentation\Http;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Application\Query\LookupPostalAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PostalAddressController extends Controller
{
    public function __invoke(Request $request, LookupPostalAddress $lookup): JsonResponse
    {
        $data = $request->validate([
            'zip' => ['required', 'string', 'regex:/^(?:\D*\d){8}\D*$/'],
        ]);

        try {
            $address = $lookup->handle((string) $data['zip']);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        if ($address === null) {
            return response()->json(['message' => 'CEP nao encontrado.'], 404);
        }

        return response()->json($address->toArray());
    }
}
