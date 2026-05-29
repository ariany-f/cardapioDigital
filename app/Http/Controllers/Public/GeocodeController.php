<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodeController extends Controller
{
    public function forward(Request $request, GeocodingService $geocoding): JsonResponse
    {
        $data = $request->validate([
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:50'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $coords = $geocoding->forward($data);

        if ($coords === null) {
            return response()->json(['ok' => false], 404);
        }

        return response()->json([
            'ok' => true,
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
        ]);
    }

    public function reverse(Request $request, GeocodingService $geocoding): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $address = $geocoding->reverse((float) $data['lat'], (float) $data['lng']);

        if ($address === null) {
            return response()->json(['ok' => false], 404);
        }

        return response()->json([
            'ok' => true,
            'address' => $address,
        ]);
    }
}
