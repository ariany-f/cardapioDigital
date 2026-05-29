<?php

namespace App\Services;

use App\Models\Motoboy;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\User;

class OrderRatingService
{
    public function createForOrder(Order $order, array $data): OrderRating
    {
        $order->loadMissing('delivery');

        return OrderRating::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'motoboy_id' => $order->delivery?->motoboy_id,
            'customer_id' => $order->customer_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'delivery_rating' => $order->type === 'delivery' ? ($data['delivery_rating'] ?? null) : null,
            'delivery_comment' => $order->type === 'delivery' ? ($data['delivery_comment'] ?? null) : null,
            'restaurant_rating' => $data['restaurant_rating'],
            'restaurant_comment' => $data['restaurant_comment'] ?? null,
            'status' => OrderRating::STATUS_APPROVED,
        ]);
    }

    public function setStatus(OrderRating $rating, string $status, ?User $moderator = null): OrderRating
    {
        $rating->update([
            'status' => $status,
            'moderated_at' => now(),
            'moderated_by_user_id' => $moderator?->id,
        ]);

        return $rating->fresh();
    }

    /**
     * @return array{restaurant: ?float, order: ?float, delivery: ?float, count: int}
     */
    public function tenantAverages(int $tenantId): array
    {
        return $this->tenantAveragesForMany([$tenantId])[$tenantId] ?? $this->emptyTenantAverages();
    }

    /**
     * @param  list<int>  $tenantIds
     * @return array<int, array{restaurant: ?float, order: ?float, delivery: ?float, count: int}>
     */
    public function tenantAveragesForMany(array $tenantIds): array
    {
        $tenantIds = array_values(array_unique(array_filter($tenantIds)));

        if ($tenantIds === []) {
            return [];
        }

        $rows = OrderRating::query()
            ->visible()
            ->whereIn('tenant_id', $tenantIds)
            ->groupBy('tenant_id')
            ->select('tenant_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('ROUND(AVG(restaurant_rating), 1) as restaurant_avg')
            ->selectRaw('ROUND(AVG(rating), 1) as order_avg')
            ->selectRaw('ROUND(AVG(delivery_rating), 1) as delivery_avg')
            ->get();

        $result = [];
        foreach ($tenantIds as $id) {
            $result[$id] = $this->emptyTenantAverages();
        }

        foreach ($rows as $row) {
            $result[(int) $row->tenant_id] = $this->mapSummaryFromRow($row);
        }

        return $result;
    }

    /**
     * @param  list<int>  $branchIds
     * @return array<int, array{restaurant: ?float, order: ?float, delivery: ?float, count: int}>
     */
    public function branchAveragesForMany(int $tenantId, array $branchIds): array
    {
        $branchIds = array_values(array_unique(array_filter($branchIds)));

        if ($branchIds === []) {
            return [];
        }

        $rows = OrderRating::query()
            ->visible()
            ->where('tenant_id', $tenantId)
            ->whereIn('branch_id', $branchIds)
            ->groupBy('branch_id')
            ->select('branch_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('ROUND(AVG(restaurant_rating), 1) as restaurant_avg')
            ->selectRaw('ROUND(AVG(rating), 1) as order_avg')
            ->selectRaw('ROUND(AVG(delivery_rating), 1) as delivery_avg')
            ->get();

        $result = [];
        foreach ($branchIds as $id) {
            $result[$id] = $this->emptyTenantAverages();
        }

        foreach ($rows as $row) {
            $result[(int) $row->branch_id] = $this->mapSummaryFromRow($row);
        }

        return $result;
    }

    /**
     * @return array{restaurant: ?float, order: ?float, delivery: ?float, count: int}
     */
    protected function mapSummaryFromRow(object $row): array
    {
        return [
            'restaurant' => $row->restaurant_avg ? (float) $row->restaurant_avg : null,
            'order' => $row->order_avg ? (float) $row->order_avg : null,
            'delivery' => $row->delivery_avg ? (float) $row->delivery_avg : null,
            'count' => (int) $row->total,
        ];
    }

    /**
     * @return array{restaurant: ?float, order: ?float, delivery: ?float, count: int}
     */
    protected function emptyTenantAverages(): array
    {
        return [
            'restaurant' => null,
            'order' => null,
            'delivery' => null,
            'count' => 0,
        ];
    }

    /**
     * @return array<int, array{motoboy_id: int, name: string, average: float, count: int}>
     */
    public function motoboyAverages(int $tenantId, int $limit = 10): array
    {
        $rows = OrderRating::query()
            ->visible()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('motoboy_id')
            ->whereNotNull('delivery_rating')
            ->select('motoboy_id')
            ->selectRaw('ROUND(AVG(delivery_rating), 1) as average')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('motoboy_id')
            ->orderByDesc('average')
            ->limit($limit)
            ->get();

        $motoboys = Motoboy::query()
            ->whereIn('id', $rows->pluck('motoboy_id'))
            ->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'motoboy_id' => (int) $r->motoboy_id,
            'name' => $motoboys[$r->motoboy_id] ?? 'Entregador',
            'average' => (float) $r->average,
            'count' => (int) $r->count,
        ])->all();
    }

    /**
     * Média de avaliação de entrega por entregador (apenas avaliações visíveis).
     *
     * @return array<int, array{average: float, count: int}>
     */
    public function motoboyRatingMap(int $tenantId): array
    {
        return OrderRating::query()
            ->visible()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('motoboy_id')
            ->whereNotNull('delivery_rating')
            ->select('motoboy_id')
            ->selectRaw('ROUND(AVG(delivery_rating), 1) as average')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('motoboy_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->motoboy_id => [
                    'average' => (float) $row->average,
                    'count' => (int) $row->count,
                ],
            ])
            ->all();
    }
}
