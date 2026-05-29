<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SeoService;
use App\Support\GuestOrderAccess;
use App\Support\TenantContext;
use App\Support\TenantOrderSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestOrderLookupController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function create(): Response
    {
        $tenant = TenantContext::get();

        abort_unless(TenantOrderSettings::guestCheckoutEnabled($tenant), 404);

        return Inertia::render('Public/TrackOrderLookup', [
            'seo' => $this->seo->forTenant($tenant, [
                'title' => __('nav.track').' — '.$tenant->name,
                'robots' => 'noindex, nofollow',
            ]),
            'tenantSlug' => $tenant->slug,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();

        abort_unless(TenantOrderSettings::guestCheckoutEnabled($tenant), 404);

        $data = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:50'],
        ]);

        $code = GuestOrderAccess::normalizeCode($data['code']);

        $query = Order::query()
            ->where('tenant_id', $tenant->id)
            ->where('guest_access_code', $code);

        if (! empty($data['order_number'])) {
            $query->where('order_number', $data['order_number']);
        }

        $order = $query->latest('id')->get()->first(
            fn (Order $candidate) => GuestOrderAccess::matchesContact(
                $candidate,
                $data['phone'] ?? null,
                $data['email'] ?? null,
            ),
        );

        if (! $order || ! GuestOrderAccess::verifyCode($order, $code)) {
            return back()->withErrors([
                'code' => __('order.access.invalid'),
            ])->withInput();
        }

        GuestOrderAccess::grant($request, $order);

        return redirect()->to(GuestOrderAccess::trackUrl($order, $tenant, false));
    }

    public function verify(Request $request, string $tenant, string $order_number): RedirectResponse
    {
        $tenantModel = TenantContext::get();
        $order = Order::query()
            ->where('order_number', $order_number)
            ->firstOrFail();

        if (! GuestOrderAccess::shouldProtect($order)) {
            return redirect()->route('tenant.track', [
                'tenant' => $tenantModel->slug,
                'order_number' => $order->order_number,
            ]);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255'],
        ]);

        if (! GuestOrderAccess::verifyCode($order, $data['code'])
            || ! GuestOrderAccess::matchesContact($order, $data['phone'] ?? null, $data['email'] ?? null)) {
            return back()->withErrors([
                'code' => __('order.access.invalid'),
            ])->withInput();
        }

        GuestOrderAccess::grant($request, $order);

        return redirect()->route('tenant.track', [
            'tenant' => $tenantModel->slug,
            'order_number' => $order->order_number,
        ]);
    }
}
