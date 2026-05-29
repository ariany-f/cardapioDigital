<?php

namespace App\Services;

use App\Support\PlatformGoogleMaps;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * @param  array{street?: string, number?: string, neighborhood?: string, city?: string, state?: string, postal_code?: string}  $address
     * @return array{lat: float, lng: float}|null
     */
    public function forward(array $address): ?array
    {
        $parts = [
            trim(($address['street'] ?? '').' '.($address['number'] ?? '')),
            $address['neighborhood'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            preg_replace('/\D/', '', $address['postal_code'] ?? '') ?: null,
            'Brasil',
        ];

        $query = implode(', ', array_filter($parts, fn ($p) => filled($p)));
        if ($query === '' || $query === 'Brasil') {
            return null;
        }

        $google = $this->googleGeocode(['address' => $query]);
        if ($google !== null) {
            return $google;
        }

        return $this->nominatimForward($query);
    }

    /**
     * @return array{street: string, neighborhood: string, city: string, state: string, postal_code: string, complement: string}|null
     */
    public function reverse(float $lat, float $lng): ?array
    {
        $google = $this->googleGeocode(['latlng' => "{$lat},{$lng}"]);
        if ($google !== null) {
            return $this->parseGoogleAddressComponents($google['raw'] ?? []);
        }

        return $this->nominatimReverse($lat, $lng);
    }

    /**
     * @param  array<string, string>  $params
     * @return array{lat: float, lng: float, raw?: array}|null
     */
    protected function googleGeocode(array $params): ?array
    {
        $key = PlatformGoogleMaps::apiKey();
        if (! $key) {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    ...$params,
                    'key' => $key,
                    'language' => 'pt-BR',
                    'region' => 'br',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            if (($data['status'] ?? '') !== 'OK' || empty($data['results'][0])) {
                return null;
            }

            $result = $data['results'][0];
            $location = $result['geometry']['location'] ?? null;
            if (! $location) {
                return null;
            }

            return [
                'lat' => (float) $location['lat'],
                'lng' => (float) $location['lng'],
                'raw' => $result,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google geocode failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{street: string, neighborhood: string, city: string, state: string, postal_code: string, complement: string}|null
     */
    protected function parseGoogleAddressComponents(array $result): ?array
    {
        $components = collect($result['address_components'] ?? []);
        $get = function (string $type, bool $short = false) use ($components) {
            $match = $components->first(
                fn ($c) => in_array($type, $c['types'] ?? [], true),
            );

            if (! $match) {
                return '';
            }

            return $short ? ($match['short_name'] ?? '') : ($match['long_name'] ?? '');
        };

        $street = trim($get('route').' '.$get('street_number'));

        return [
            'street' => $street,
            'neighborhood' => $get('sublocality') ?: $get('sublocality_level_1') ?: $get('political'),
            'city' => $get('administrative_area_level_2') ?: $get('locality'),
            'state' => $get('administrative_area_level_1', true),
            'postal_code' => preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', preg_replace('/\D/', '', $get('postal_code', true))),
            'complement' => '',
        ];
    }

    protected function nominatimForward(string $query): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Accept-Language' => 'pt-BR',
                    'User-Agent' => config('app.name', 'AppCardapio').'/1.0',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'q' => $query,
                    'limit' => 1,
                    'countrycodes' => 'br',
                ]);

            if (! $response->successful() || empty($response->json()[0])) {
                return null;
            }

            $row = $response->json()[0];

            return [
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lon'],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{street: string, neighborhood: string, city: string, state: string, postal_code: string, complement: string}|null
     */
    protected function nominatimReverse(float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Accept-Language' => 'pt-BR',
                    'User-Agent' => config('app.name', 'AppCardapio').'/1.0',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $a = $data['address'] ?? [];
            $street = trim(implode(' ', array_filter([
                $a['road'] ?? null,
                $a['pedestrian'] ?? null,
                $a['residential'] ?? null,
            ])));

            return [
                'street' => $street,
                'neighborhood' => $a['suburb'] ?? $a['neighbourhood'] ?? $a['quarter'] ?? '',
                'city' => $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['municipality'] ?? '',
                'state' => str_replace('BR-', '', $a['ISO3166-2-lvl4'] ?? '') ?: ($a['state'] ?? ''),
                'postal_code' => preg_replace('/^(\d{5})(\d{3})$/', '$1-$2', preg_replace('/\D/', '', $a['postcode'] ?? '')),
                'complement' => '',
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
