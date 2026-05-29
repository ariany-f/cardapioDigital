<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Tenant;
use App\Support\TenantPaymentSettings;
use Illuminate\Support\Str;

class OrderPaymentService
{
    public function __construct(
        protected ActivityLogService $logger,
    ) {}

    public function applyCheckoutPayment(Order $order, Tenant $tenant, string $paymentMethod, ?string $paymentChannel = null): ?OrderPayment
    {
        if ($paymentMethod === 'on_delivery') {
            $order->update([
                'payment_method' => 'on_delivery',
                'payment_channel' => $paymentChannel,
                'payment_status' => 'pending',
            ]);

            return null;
        }

        $channel = $paymentChannel ?? ($paymentMethod === 'pix_online' ? 'pix' : 'card');
        $order->update([
            'payment_method' => 'online',
            'payment_channel' => $channel,
            'payment_status' => 'pending',
        ]);

        if ($channel === 'pix') {
            return $this->createPixPendingPayment($order, $tenant);
        }

        return OrderPayment::create([
            'order_id' => $order->id,
            'gateway' => 'manual',
            'amount' => $order->total,
            'status' => 'pending',
            'metadata_json' => [
                'instructions' => TenantPaymentSettings::from($tenant)['card_online_instructions'],
            ],
        ]);
    }

    public function createPixPendingPayment(Order $order, Tenant $tenant): ?OrderPayment
    {
        $pix = TenantPaymentSettings::copyPastePayload($tenant);
        if (! $pix) {
            return null;
        }

        return OrderPayment::create([
            'order_id' => $order->id,
            'gateway' => 'pix_static',
            'external_id' => 'PIX-'.$order->order_number,
            'amount' => $order->total,
            'status' => 'pending',
            'copy_paste' => $pix['copy_paste'],
            'metadata_json' => $pix,
        ]);
    }

    public function markPaid(Order $order, ?int $userId = null, string $origin = 'admin'): Order
    {
        $order->update(['payment_status' => 'paid']);

        $payment = OrderPayment::query()
            ->where('order_id', $order->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } else {
            OrderPayment::create([
                'order_id' => $order->id,
                'gateway' => 'manual',
                'amount' => $order->total,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        $this->logger->log(
            $order->fresh(),
            'order.payment_marked_paid',
            'Pagamento confirmado',
            ['payment_status' => 'paid', 'origin' => $origin],
            $origin,
        );

        return $order->fresh();
    }

    public function registerPosPayment(Order $order, string $posMethod, bool $markPaidNow = false): void
    {
        if ($posMethod === 'on_delivery') {
            $order->update([
                'payment_method' => 'on_delivery',
                'payment_channel' => null,
                'payment_status' => $markPaidNow ? 'paid' : 'pending',
            ]);
            if ($markPaidNow) {
                $this->markPaid($order, auth()->guard('web')->id(), 'pos');
            }

            return;
        }

        if ($posMethod === 'pix') {
            $order->load('tenant');
            $this->applyCheckoutPayment($order, $order->tenant, 'pix_online', 'pix');
            if ($markPaidNow) {
                $this->markPaid($order, auth()->guard('web')->id(), 'pos');
            }

            return;
        }

        $order->update([
            'payment_method' => 'online',
            'payment_channel' => $posMethod,
            'payment_status' => $markPaidNow ? 'paid' : 'pending',
        ]);

        OrderPayment::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'gateway' => 'manual',
            'amount' => $order->total,
            'status' => $markPaidNow ? 'paid' : 'pending',
            'paid_at' => $markPaidNow ? now() : null,
        ]);

        if ($markPaidNow) {
            $this->markPaid($order, auth()->guard('web')->id(), 'pos');
        }
    }

    public static function generateTxid(): string
    {
        return strtoupper(Str::random(8));
    }
}
