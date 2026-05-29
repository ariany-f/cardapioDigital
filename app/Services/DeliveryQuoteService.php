<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\DeliveryZone;
use Illuminate\Validation\ValidationException;

class DeliveryQuoteService
{
    public static function branchUsesPerKmPricing(Branch $branch): bool
    {
        return DeliveryZone::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where('type', 'per_km')
            ->exists();
    }

    public function quote(Branch $branch, string $type, ?array $address = null, ?float $customerLat = null, ?float $customerLng = null): array
    {
        if ($type !== 'delivery') {
            return ['allowed' => true, 'fee' => 0.0, 'distance_km' => null];
        }

        $zones = DeliveryZone::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $zone = $this->matchZone($zones, $address);

        if ($zone && $zone->type === 'neighborhood' && $address) {
            $neighborhoods = $zone->rules['neighborhoods'] ?? [];
            $customerNeighborhood = mb_strtolower(trim($address['neighborhood'] ?? ''));
            $allowed = collect($neighborhoods)
                ->map(fn ($n) => mb_strtolower(trim($n)))
                ->contains($customerNeighborhood);

            if (! $allowed && ! empty($neighborhoods)) {
                throw ValidationException::withMessages([
                    'delivery_address.neighborhood' => ['Entrega não disponível para este bairro.'],
                ]);
            }
        }

        $distanceKm = null;
        $needsDistance = ($zone && $zone->type === 'per_km')
            || ($branch->latitude && $branch->longitude && $branch->delivery_radius_km);

        if ($needsDistance) {
            if ($customerLat === null || $customerLng === null) {
                throw ValidationException::withMessages([
                    'delivery_lat' => ['Ative a localização no navegador para calcular a entrega.'],
                ]);
            }

            if (! $branch->latitude || ! $branch->longitude) {
                throw ValidationException::withMessages([
                    'delivery_lat' => ['Esta filial ainda não tem coordenadas cadastradas para calcular entrega por km.'],
                ]);
            }

            $distanceKm = $this->distanceKm(
                (float) $branch->latitude,
                (float) $branch->longitude,
                $customerLat,
                $customerLng,
            );

            if ($branch->delivery_radius_km && $distanceKm > (float) $branch->delivery_radius_km) {
                throw ValidationException::withMessages([
                    'delivery_lat' => [
                        sprintf('Endereço fora da área de entrega (%.1f km; máximo %.1f km).', $distanceKm, $branch->delivery_radius_km),
                    ],
                ]);
            }
        }

        $fee = $this->resolveFee($zone, $zones, $distanceKm);

        $minOverride = $zone?->min_order_override;

        return [
            'allowed' => true,
            'fee' => $fee,
            'distance_km' => $distanceKm,
            'min_order_override' => $minOverride ? (float) $minOverride : null,
        ];
    }

    protected function matchZone($zones, ?array $address): ?DeliveryZone
    {
        if (! $address || $zones->isEmpty()) {
            return $zones->first();
        }

        $neighborhood = mb_strtolower(trim($address['neighborhood'] ?? ''));

        foreach ($zones as $zone) {
            if ($zone->type !== 'neighborhood') {
                continue;
            }
            $list = collect($zone->rules['neighborhoods'] ?? [])
                ->map(fn ($n) => mb_strtolower(trim($n)));
            if ($list->contains($neighborhood)) {
                return $zone;
            }
        }

        return $zones->firstWhere('type', 'flat')
            ?? $zones->firstWhere('type', 'per_km')
            ?? $zones->first();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DeliveryZone>  $zones
     */
    protected function resolveFee(?DeliveryZone $zone, $zones, ?float $distanceKm): float
    {
        if ($zone && $zone->type === 'per_km') {
            if ($distanceKm === null) {
                throw ValidationException::withMessages([
                    'delivery_lat' => ['Ative a localização no navegador para calcular a entrega por km.'],
                ]);
            }

            $base = (float) $zone->delivery_fee;
            $perKm = (float) ($zone->rules['fee_per_km'] ?? 0);

            return round($base + ($distanceKm * $perKm), 2);
        }

        if ($zone) {
            return (float) $zone->delivery_fee;
        }

        if ($zones->isNotEmpty()) {
            return (float) $zones->first()->delivery_fee;
        }

        return 0.0;
    }

    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round(2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
