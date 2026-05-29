<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SupportRequest;
use App\Services\ActivityLogService;
use App\Services\DeliveryConfirmationService;
use App\Services\OrderRatingService;
use App\Services\SeoService;
use App\Support\ChatEligibility;
use App\Support\DeliveryAddressFormatter;
use App\Support\GuestOrderAccess;
use App\Support\TenantContext;
use App\Support\TenantFeatures;
use App\Support\TenantOrderSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackOrderController extends Controller
{
    public function __construct(
        protected OrderRatingService $ratings,
        protected SeoService $seo,
        protected DeliveryConfirmationService $deliveryConfirmation,
    ) {}

    public function show(string $tenant, string $order_number): Response
    {
        $tenantModel = TenantContext::get();

        $order = Order::query()
            ->where('order_number', $order_number)
            ->with([
                'items', 'branch:id,name,slug,tenant_id', 'rating', 'delivery.motoboy:id,name',
                'payments' => fn ($q) => $q->latest(),
                'statusHistories' => fn ($q) => $q->orderBy('created_at'),
            ])
            ->firstOrFail();

        $order->loadMissing('branch:id,name,slug,tenant_id');
        $request = request();

        if (! GuestOrderAccess::hasAccess($request, $order)) {
            return Inertia::render('Public/TrackOrderAccess', [
                'seo' => $this->seo->forTenant($tenantModel, [
                    'title' => __('nav.track').' — '.$tenantModel->name,
                    'robots' => 'noindex, nofollow',
                ]),
                'tenantSlug' => $tenantModel->slug,
                'orderNumber' => $order_number,
                'lookupUrl' => route('tenant.track.lookup', ['tenant' => $tenantModel->slug]),
                'guestCheckoutEnabled' => TenantOrderSettings::guestCheckoutEnabled($tenantModel),
            ]);
        }

        $branch = $order->branch;
        $customer = auth('customer')->user();

        if (ChatEligibility::isRecent($order->created_at)) {
            ChatEligibility::markPurchased($branch, $request, array_filter([
                'name' => $order->guest_name,
                'phone' => $order->guest_phone,
                'email' => $order->guest_email,
            ]), $order->created_at);
        }

        $accessCode = $order->guest_access_code;
        $trackUrl = GuestOrderAccess::trackUrl($order, $tenantModel);

        return Inertia::render('Public/TrackOrder', [
            'seo' => $this->seo->forTenant($tenantModel, [
                'title' => 'Pedido '.$order->order_number.' — '.$tenantModel->name,
                'robots' => 'noindex, nofollow',
            ]),
            'order' => $this->trackingPayload($order, $request),
            'tenantSlug' => $tenantModel->slug,
            'chatAvailable' => ChatEligibility::canStart($branch, $customer, $request),
            'chatGuestProfile' => ChatEligibility::guestProfile($branch, $request),
            'guestAccess' => $accessCode ? [
                'code' => $accessCode,
                'track_url' => $trackUrl,
                'lookup_url' => route('tenant.track.lookup', ['tenant' => $tenantModel->slug]),
                'guest_email' => $order->guest_email,
                'guest_phone' => $order->guest_phone,
                'email_sent' => (bool) $order->guest_email,
            ] : null,
        ]);
    }

    public function rate(Request $request, string $tenant, string $order_number): RedirectResponse
    {
        $order = $this->findOrderWithAccess($request, $order_number);

        if ($order->status !== 'delivered') {
            return back()->with('error', 'Só é possível avaliar pedidos entregues.');
        }

        if ($order->rating) {
            return back()->with('error', 'Este pedido já foi avaliado.');
        }

        $rules = [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'restaurant_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'restaurant_comment' => ['nullable', 'string', 'max:1000'],
        ];

        if ($order->type === 'delivery') {
            $rules['delivery_rating'] = ['required', 'integer', 'min:1', 'max:5'];
            $rules['delivery_comment'] = ['nullable', 'string', 'max:1000'];
        }

        $data = $request->validate($rules);

        $this->ratings->createForOrder($order, $data);

        return back()->with('success', 'Obrigado pelas avaliações!');
    }

    public function reportOrder(Request $request, string $tenant, string $order_number, ActivityLogService $logger): RedirectResponse
    {
        $order = $this->findOrderWithAccess($request, $order_number);
        $customer = auth('customer')->user();

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $support = SupportRequest::create([
            'tenant_id' => $order->tenant_id,
            'customer_id' => $customer?->id,
            'order_id' => $order->id,
            'type' => 'complaint',
            'subject' => 'Problema com pedido '.$order->order_number,
            'message' => $data['message'],
            'status' => 'open',
            'guest_name' => $customer?->name ?? $order->guest_name,
            'guest_phone' => $customer?->phone ?? $order->guest_phone,
            'guest_email' => $customer?->email ?? $order->guest_email,
        ]);

        $logger->log(
            $support,
            'support.created',
            'Cliente relatou problema com o pedido',
            ['order_id' => $order->id, 'order_number' => $order->order_number],
            $customer ? 'customer' : 'guest',
        );

        return back()->with('success', 'Sua mensagem foi enviada ao restaurante.');
    }

    public function status(Request $request, string $tenant, string $order_number): JsonResponse
    {
        $order = Order::query()
            ->where('order_number', $order_number)
            ->with([
                'items',
                'branch:id,name,slug,tenant_id',
                'rating',
                'delivery.motoboy:id,name',
                'payments' => fn ($q) => $q->latest(),
                'statusHistories' => fn ($q) => $q->orderBy('created_at'),
            ])
            ->firstOrFail();

        abort_unless(GuestOrderAccess::hasAccess($request, $order), 403);

        return response()->json($this->trackingPayload($order, $request));
    }

    protected function findOrderWithAccess(Request $request, string $order_number): Order
    {
        $order = Order::query()
            ->where('order_number', $order_number)
            ->with('rating')
            ->firstOrFail();

        abort_unless(GuestOrderAccess::hasAccess($request, $order), 403);

        return $order;
    }

    protected function trackingPayload(Order $order, Request $request): array
    {
        $deliveryCode = $this->deliveryConfirmation->codeForCustomerDisplay($order);
        $order->loadMissing('branch:id,default_prep_time_minutes,delivery_time_minutes');

        $estimatedMinutes = null;
        if ($order->type === 'delivery' && $order->branch) {
            $estimatedMinutes = $order->prep_time_minutes
                + max(0, (int) ($order->branch->delivery_time_minutes ?? 0));
        } elseif (in_array($order->type, ['pickup', 'dine_in'], true)) {
            $estimatedMinutes = $order->prep_time_minutes;
        }

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'type' => $order->type,
            'type_label' => $this->orderTypeLabel($order->type),
            'total' => $order->total,
            'subtotal' => $order->subtotal,
            'delivery_fee' => $order->delivery_fee,
            'packaging_fee' => $order->packaging_fee,
            'service_fee' => $order->service_fee,
            'discount_amount' => $order->discount_amount,
            'tip_amount' => $order->tip_amount,
            'guest_name' => $order->guest_name,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'scheduled_for' => $order->scheduled_for?->toIso8601String(),
            'estimated_ready_at' => $order->estimated_ready_at?->toIso8601String(),
            'estimated_minutes' => $estimatedMinutes,
            'branch' => $order->branch?->only(['name', 'slug']),
            'delivery_address' => $order->delivery_address,
            'delivery_address_formatted' => DeliveryAddressFormatter::format($order->delivery_address),
            'can_rate' => $order->status === 'delivered' && ! $order->rating,
            'is_delivery' => $order->type === 'delivery',
            'rating' => $order->rating?->toPublicPayload(),
            'show_delivery_code' => $deliveryCode !== null,
            'delivery_confirmation_code' => $deliveryCode,
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'variations' => $item->variations_snapshot ?? [],
                'notes' => $item->notes,
            ]),
            'status_histories' => $order->statusHistories->map(fn ($h) => [
                'status' => $h->status,
                'created_at' => $h->created_at?->toIso8601String(),
            ]),
            'can_report_motoboy' => TenantFeatures::motoboysEnabled(TenantContext::get())
                && $order->type === 'delivery'
                && $order->delivery?->motoboy_id
                && in_array($order->status, ['out_for_delivery', 'delivered'], true),
            'can_report_order' => ! in_array($order->status, ['rejected'], true),
            'motoboy_name' => $order->delivery?->motoboy?->name,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $this->paymentStatusLabel($order->payment_status),
            'payment_method' => $order->payment_method,
            'payment_channel' => $order->payment_channel,
            'payment_label' => $this->paymentLabel($order),
            'pix_payment' => $this->pixPaymentPayload($order),
        ];
    }

    protected function orderTypeLabel(?string $type): string
    {
        return match ($type) {
            'delivery' => __('order.type.delivery'),
            'pickup' => __('order.type.pickup'),
            'dine_in' => __('order.type.dine_in'),
            default => (string) $type,
        };
    }

    protected function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => __('order.track.payment_paid'),
            'pending' => __('order.track.payment_pending'),
            default => (string) $status,
        };
    }

    protected function paymentLabel(Order $order): string
    {
        if ($order->payment_method === 'pix_online') {
            return __('payment.pix_online');
        }

        if ($order->payment_method === 'card_online') {
            return __('payment.card_online');
        }

        if ($order->payment_method === 'on_delivery') {
            $channel = match ($order->payment_channel) {
                'pix' => __('payment.channel.pix'),
                'cash' => __('payment.channel.cash'),
                'card' => __('payment.channel.card'),
                'debit' => __('payment.channel.debit'),
                default => null,
            };

            return $channel
                ? __('payment.on_delivery').' ('.$channel.')'
                : __('payment.on_delivery');
        }

        return $order->payment_method ?? '—';
    }

    protected function pixPaymentPayload(Order $order): ?array
    {
        if ($order->payment_status === 'paid') {
            return null;
        }

        $pending = $order->payments->firstWhere('status', 'pending')
            ?? $order->payments->firstWhere('gateway', 'pix_static');

        if (! $pending?->copy_paste && $order->payment_channel !== 'pix') {
            return null;
        }

        return [
            'amount' => $order->total,
            'copy_paste' => $pending?->copy_paste,
            'beneficiary' => $pending?->metadata_json['beneficiary'] ?? null,
            'instructions' => __('payment.pix_instructions'),
        ];
    }
}
