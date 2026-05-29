<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TenantWebhookToken;
use App\Services\ActivityLogService;
use App\Services\DeliveryStatusService;
use App\Support\SecureCompare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryWebhookController extends Controller
{
    public function handle(Request $request, DeliveryStatusService $delivery, ActivityLogService $logger): JsonResponse
    {
        $token = trim((string) $request->header('X-Tenant-Token', ''));

        if ($token === '') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $webhookToken = TenantWebhookToken::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (TenantWebhookToken $row) => SecureCompare::equals($row->token, $token));

        if (! $webhookToken) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'order_number' => ['required', 'string'],
            'status' => ['required', 'string', 'in:pending,assigned,picked_up,on_route,delivered,failed'],
            'confirmation_code' => ['nullable', 'string', 'min:4', 'max:8'],
        ]);

        $order = Order::withoutGlobalScopes()
            ->where('tenant_id', $webhookToken->tenant_id)
            ->where('order_number', $data['order_number'])
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->type !== 'delivery') {
            return response()->json(['message' => 'Order is not a delivery order'], 422);
        }

        try {
            $delivery->updateStatus(
                $order,
                $data['status'],
                null,
                $data['confirmation_code'] ?? null,
                null,
                'webhook',
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        $logger->log(
            $order->fresh(),
            'webhook.delivery_status',
            'Webhook de entrega: '.$data['status'],
            ['status' => $data['status'], 'token_id' => $webhookToken->id],
            'webhook',
        );

        return response()->json(['ok' => true]);
    }
}
