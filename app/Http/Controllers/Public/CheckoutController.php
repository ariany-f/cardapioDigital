<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\BranchHoursService;
use App\Services\OrderItemValidationService;
use App\Services\CouponService;
use App\Services\DeliveryQuoteService;
use App\Services\OrderDeliveryEstimateService;
use App\Services\OrderNotificationService;
use App\Services\OrderPaymentService;
use App\Services\StockService;
use App\Services\GuestOrderAccessNotificationService;
use App\Support\ChatEligibility;
use App\Support\GuestOrderAccess;
use App\Support\OrderDisposableConfig;
use App\Support\TenantContext;
use App\Support\TenantOrderSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function store(
        Request $request,
        BranchHoursService $hours,
        DeliveryQuoteService $deliveryQuote,
        CouponService $coupons,
        StockService $stock,
        OrderNotificationService $notifications,
        OrderItemValidationService $itemValidation,
        ActivityLogService $activityLog,
        OrderPaymentService $payments,
        GuestOrderAccessNotificationService $guestAccessNotifications,
        OrderDeliveryEstimateService $deliveryEstimate,
    ): RedirectResponse {
        $tenant = TenantContext::get();
        $customer = auth('customer')->user();

        $data = $request->validate([
            'branch_slug' => ['required', 'string'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:30'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'type' => ['required', 'in:pickup,delivery,dine_in'],
            'table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'delivery_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_address' => ['nullable', 'array'],
            'delivery_address.street' => ['required_if:type,delivery', 'nullable', 'string', 'max:255'],
            'delivery_address.number' => ['required_if:type,delivery', 'nullable', 'string', 'max:20'],
            'delivery_address.neighborhood' => ['required_if:type,delivery', 'nullable', 'string', 'max:255'],
            'delivery_address.city' => ['required_if:type,delivery', 'nullable', 'string', 'max:255'],
            'delivery_address.state' => ['nullable', 'string', 'max:2'],
            'delivery_address.postal_code' => ['required_if:type,delivery', 'nullable', 'string', 'max:12'],
            'delivery_address.complement' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.combo_id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.variations' => ['nullable', 'array'],
            'items.*.variations.*.option_id' => ['required', 'integer'],
            'items.*.variations.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'order_disposables' => ['nullable', 'array'],
            'tip_amount' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
            'payment_method' => ['required', 'in:on_delivery,pix_online,card_online'],
            'payment_channel' => ['nullable', 'string', 'in:pix,cash,card,debit'],
        ]);

        $branch = Branch::query()
            ->where('slug', $data['branch_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $isScheduled = ! empty($data['scheduled_for']);

        if (! $isScheduled && ! $hours->isOpen($branch)) {
            throw ValidationException::withMessages(['branch' => [__('branch.orders_closed')]]);
        }

        $isTableOrder = $data['type'] === 'dine_in' && ! empty($data['table_id']);
        if (! $customer && ! TenantOrderSettings::guestCheckoutEnabled($tenant) && ! $isTableOrder) {
            throw ValidationException::withMessages([
                'guest' => [__('branch.guest_checkout_disabled')],
            ]);
        }

        if ($data['type'] === 'delivery' && ! $branch->delivery_available) {
            throw ValidationException::withMessages(['type' => [__('branch.delivery_unavailable')]]);
        }

        if ($data['type'] === 'pickup' && ! $branch->pickup_available) {
            throw ValidationException::withMessages(['type' => [__('branch.pickup_unavailable')]]);
        }

        if ($data['type'] === 'dine_in') {
            if (empty($data['table_id'])) {
                throw ValidationException::withMessages(['table_id' => ['Mesa inválida. Escaneie o QR novamente.']]);
            }
            $tableValid = \App\Models\DiningTable::query()
                ->where('id', $data['table_id'])
                ->where('branch_id', $branch->id)
                ->exists();
            if (! $tableValid) {
                throw ValidationException::withMessages(['table_id' => ['Mesa não pertence a esta unidade.']]);
            }
        }

        $disposableConfig = OrderDisposableConfig::normalizeList($branch->order_disposables);
        $validatedDisposables = OrderDisposableConfig::validateSelection(
            $disposableConfig,
            $data['order_disposables'] ?? [],
        );
        $this->validateOrderDisposables($disposableConfig, $validatedDisposables);

        $itemValidation->validate($data['items'], $branch);

        $subtotal = collect($data['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

        if ($branch->minimum_order_amount && $subtotal < (float) $branch->minimum_order_amount) {
            throw ValidationException::withMessages([
                'items' => [
                    sprintf('Pedido mínimo de R$ %.2f.', $branch->minimum_order_amount),
                ],
            ]);
        }

        $quote = $deliveryQuote->quote(
            $branch,
            $data['type'],
            $data['delivery_address'] ?? null,
            isset($data['delivery_lat']) ? (float) $data['delivery_lat'] : null,
            isset($data['delivery_lng']) ? (float) $data['delivery_lng'] : null,
        );

        $minOrder = (float) ($quote['min_order_override'] ?? $branch->minimum_order_amount ?? 0);
        if ($minOrder > 0 && $subtotal < $minOrder) {
            throw ValidationException::withMessages([
                'items' => [sprintf('Pedido mínimo de R$ %.2f.', $minOrder)],
            ]);
        }

        $couponResult = $coupons->apply($data['coupon_code'] ?? null, $subtotal, $branch);
        $discount = $couponResult['discount'];
        $deliveryFee = $quote['fee'];
        $packagingFee = $data['type'] === 'dine_in' ? 0 : (float) $branch->packaging_fee_default;
        $tipAmount = max(0, (float) ($data['tip_amount'] ?? 0));

        if (! empty($data['scheduled_for']) && ! $branch->allow_scheduled_orders) {
            throw ValidationException::withMessages([
                'scheduled_for' => ['Esta unidade não aceita pedidos agendados.'],
            ]);
        }

        $total = max(0, $subtotal + $deliveryFee + $packagingFee + $tipAmount - $discount);

        $initialStatus = $branch->auto_accept_orders ? 'confirmed' : 'pending_approval';

        $orderNumber = strtoupper($tenant->slug).'-'.str_pad(
            (string) (Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count() + 1),
            4,
            '0',
            STR_PAD_LEFT
        );

        if ($customer) {
            $data['guest_name'] = $customer->name;
            $data['guest_phone'] = $customer->phone;
            $data['guest_email'] = $customer->email;
        }

        $guestAccessCode = null;
        if (GuestOrderAccess::shouldIssueCode($tenant, ! $customer)) {
            $guestAccessCode = GuestOrderAccess::generateCode();
        }

        $prepMinutes = $deliveryEstimate->estimatePrepMinutes($branch, $data['items']);
        $timezone = $tenant->timezone ?? config('app.timezone', 'America/Sao_Paulo');
        $estimatedReadyAt = ! empty($data['scheduled_for'])
            ? \Carbon\Carbon::parse($data['scheduled_for'], $timezone)
            : now($timezone)->addMinutes($prepMinutes);

        $order = Order::create([
            'branch_id' => $branch->id,
            'customer_id' => $customer?->id,
            'table_id' => $data['type'] === 'dine_in' ? $data['table_id'] : null,
            'coupon_id' => $couponResult['coupon']?->id,
            'order_number' => $orderNumber,
            'type' => $data['type'],
            'status' => $initialStatus,
            'source' => 'web',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'packaging_fee' => $packagingFee,
            'discount_amount' => $discount,
            'tip_amount' => $tipAmount,
            'total' => $total,
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'payment_method' => 'on_delivery',
            'payment_channel' => null,
            'payment_status' => 'pending',
            'guest_name' => $data['guest_name'],
            'guest_phone' => $data['guest_phone'],
            'guest_email' => $data['guest_email'] ?? null,
            'guest_access_code' => $guestAccessCode,
            'delivery_address' => $data['type'] === 'delivery' ? $data['delivery_address'] : null,
            'disposables_snapshot' => OrderDisposableConfig::buildSnapshot($disposableConfig, $validatedDisposables),
            'prep_time_minutes' => $prepMinutes,
            'estimated_ready_at' => $estimatedReadyAt,
        ]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['unit_price'] * $item['quantity'],
                'variations_snapshot' => $item['variations'] ?? null,
            ]);

            $product = Product::find($item['product_id']);
            if ($product) {
                $stock->decrementForOrder($product, $item['quantity'], $branch->id, $order->id, $tenant->id);
            }
        }

        if ($data['type'] === 'delivery') {
            Delivery::create([
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'status' => 'pending',
            ]);
        }

        if ($couponResult['coupon']) {
            $coupons->incrementUsage($couponResult['coupon']);
        }

        OrderStatusHistory::create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => $initialStatus,
            'origin' => 'customer',
            'changed_by' => null,
            'notes' => $branch->auto_accept_orders
                ? 'Pedido criado e confirmado automaticamente'
                : 'Pedido criado pelo cardápio digital',
        ]);

        if ($initialStatus === 'confirmed') {
            $order->update([
                'approved_at' => now(),
                'approved_by_user_id' => null,
            ]);
        }

        $activityLog->log(
            $order,
            'order.created',
            'Pedido criado pelo cardápio digital',
            [
                'source' => 'web',
                'order_number' => $order->order_number,
                'initial_status' => $initialStatus,
                'auto_accept' => (bool) $branch->auto_accept_orders,
            ],
            'customer',
        );

        if ($initialStatus === 'confirmed') {
            $activityLog->log(
                $order,
                'order.approved',
                'Pedido aprovado automaticamente (configuração da filial)',
                ['auto_accept' => true],
                'system',
            );
        }

        $paymentMethod = $data['payment_method'];
        if ($paymentMethod === 'pix_online') {
            $payments->applyCheckoutPayment($order, $tenant, 'pix_online', 'pix');
        } elseif ($paymentMethod === 'card_online') {
            $payments->applyCheckoutPayment($order, $tenant, 'card_online', 'card');
        } else {
            $order->update([
                'payment_method' => 'on_delivery',
                'payment_channel' => $data['payment_channel'] ?? null,
                'payment_status' => 'pending',
            ]);
        }

        $notifications->notifyStatusChange($order->fresh(), $initialStatus);

        ChatEligibility::markPurchased($branch, $request, array_filter([
            'name' => $data['guest_name'],
            'phone' => $data['guest_phone'] ?? null,
            'email' => $data['guest_email'] ?? null,
        ]));

        if ($guestAccessCode) {
            GuestOrderAccess::grant($request, $order);
            $guestAccessNotifications->send($order->fresh(), $tenant);
        }

        $flash = ['success' => 'Pedido realizado com sucesso!'];
        if ($guestAccessCode) {
            $flash['guest_access_code'] = $guestAccessCode;
        }
        if ($paymentMethod === 'pix_online') {
            $flash['show_pix'] = true;
        }

        return redirect()->route('tenant.track', [
            'tenant' => $tenant->slug,
            'order_number' => $order->order_number,
        ])->with($flash);
    }

    protected function validateOrderDisposables(array $config, array $selected): void
    {
        foreach ($config as $item) {
            if ($item['min_qty'] > 0 && ($selected[$item['key']] ?? 0) < $item['min_qty']) {
                throw ValidationException::withMessages([
                    'order_disposables' => [
                        sprintf('Informe pelo menos %d unidade(s) de %s.', $item['min_qty'], $item['label']),
                    ],
                ]);
            }
        }
    }

}
