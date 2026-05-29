<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\DeliveryConfirmationService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        $redirect = $request->query('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            $request->session()->put('url.intended', $redirect);
        }

        return Inertia::render('Conta/Login', [
            'globalAccount' => $this->isGlobalContaRoute(),
        ]);
    }

    public function showRegister(): Response
    {
        return Inertia::render('Conta/Register', [
            'globalAccount' => $this->isGlobalContaRoute(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $customer || ! $customer->password || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha incorretos.'],
            ]);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->dashboardUrl());
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $customer = Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->to($this->dashboardUrl());
    }

    public function dashboard(DeliveryConfirmationService $deliveryConfirmation): Response
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->with(['branch:id,name,slug', 'tenant:id,name,slug'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (Order $order) use ($deliveryConfirmation) {
                $deliveryCode = $deliveryConfirmation->codeForCustomerDisplay($order);

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'type' => $order->type,
                    'total' => $order->total,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'branch' => $order->branch?->only(['name', 'slug']),
                    'tenant' => $order->tenant?->only(['name', 'slug']),
                    'show_delivery_code' => $deliveryCode !== null,
                    'delivery_confirmation_code' => $deliveryCode,
                ];
            });

        return Inertia::render('Conta/Dashboard', [
            'customer' => $customer->only(['id', 'name', 'email', 'phone']),
            'orders' => $orders,
            'globalAccount' => $this->isGlobalContaRoute(),
        ]);
    }

    public function repeatOrder(string $tenant, int $order): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::withoutGlobalScopes()
            ->with(['items', 'branch:id,slug', 'tenant:id,slug'])
            ->where('customer_id', $customer->id)
            ->findOrFail($order);

        $cart = $order->items
            ->filter(fn ($item) => $item->product_id)
            ->map(fn ($item) => [
                'line_key' => 'reorder-'.$item->product_id.'-'.uniqid(),
                'product_id' => $item->product_id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'variations' => $item->variations_snapshot ?? [],
            ])
            ->values()
            ->all();

        return redirect()->route('tenant.branch', [
            'tenant' => $order->tenant->slug,
            'branch' => $order->branch->slug,
        ])->with('reorder_cart', $cart);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($this->isGlobalContaRoute()) {
            return redirect()->route('app.conta.login');
        }

        $tenant = TenantContext::get();

        return redirect()->route('tenant.home', ['tenant' => $tenant->slug]);
    }

    protected function isGlobalContaRoute(): bool
    {
        return request()->routeIs('app.conta.*');
    }

    protected function dashboardRouteName(): string
    {
        return $this->isGlobalContaRoute() ? 'app.conta.dashboard' : 'tenant.conta.dashboard';
    }

    protected function dashboardRouteParams(): array
    {
        if ($this->isGlobalContaRoute()) {
            return [];
        }

        return ['tenant' => TenantContext::get()->slug];
    }

    protected function dashboardUrl(): string
    {
        return route($this->dashboardRouteName(), $this->dashboardRouteParams());
    }
}
