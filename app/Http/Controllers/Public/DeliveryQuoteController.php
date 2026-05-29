<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\DeliveryQuoteService;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryQuoteController extends Controller
{
    public function quote(
        Request $request,
        string $tenant,
        string $branch,
        DeliveryQuoteService $quotes,
        GeocodingService $geocoding,
    ): JsonResponse {
        $branchModel = Branch::query()
            ->where('slug', $branch)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->validate([
            'delivery_address' => ['nullable', 'array'],
            'delivery_address.street' => ['nullable', 'string', 'max:255'],
            'delivery_address.number' => ['nullable', 'string', 'max:50'],
            'delivery_address.neighborhood' => ['nullable', 'string', 'max:255'],
            'delivery_address.city' => ['nullable', 'string', 'max:255'],
            'delivery_address.state' => ['nullable', 'string', 'max:2'],
            'delivery_address.postal_code' => ['nullable', 'string', 'max:20'],
            'delivery_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $address = $data['delivery_address'] ?? null;
        $lat = isset($data['delivery_lat']) ? (float) $data['delivery_lat'] : null;
        $lng = isset($data['delivery_lng']) ? (float) $data['delivery_lng'] : null;

        if (($lat === null || $lng === null) && is_array($address)) {
            $coords = $geocoding->forward($address);
            if ($coords !== null) {
                $lat = $coords['lat'];
                $lng = $coords['lng'];
            }
        }

        try {
            $quote = $quotes->quote($branchModel, 'delivery', $address, $lat, $lng);

            return response()->json([
                'ok' => true,
                'fee' => $quote['fee'],
                'distance_km' => $quote['distance_km'],
                'min_order_override' => $quote['min_order_override'],
                'delivery_lat' => $lat,
                'delivery_lng' => $lng,
            ]);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Entrega indisponível para este endereço.';

            return response()->json([
                'ok' => false,
                'message' => $message,
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
