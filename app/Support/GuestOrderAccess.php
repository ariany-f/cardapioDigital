<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Http\Request;

class GuestOrderAccess
{
    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function normalizeCode(?string $code): string
    {
        return preg_replace('/\D/', '', $code ?? '');
    }

    public static function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D/', '', $phone ?? '');
    }

    public static function shouldIssueCode(?Tenant $tenant, bool $isGuestOrder): bool
    {
        return $isGuestOrder
            && $tenant
            && TenantOrderSettings::guestCheckoutEnabled($tenant);
    }

    public static function shouldProtect(Order $order): bool
    {
        if ($order->customer_id !== null || $order->guest_access_code === null) {
            return false;
        }

        $order->loadMissing('tenant');

        return TenantOrderSettings::guestCheckoutEnabled($order->tenant);
    }

    public static function verifyCode(Order $order, ?string $code): bool
    {
        if ($order->guest_access_code === null) {
            return true;
        }

        $given = self::normalizeCode($code);

        return strlen($given) === 6
            && hash_equals($order->guest_access_code, $given);
    }

    public static function matchesContact(Order $order, ?string $phone, ?string $email): bool
    {
        if ($email !== null && trim($email) !== '') {
            return strcasecmp(trim($order->guest_email ?? ''), trim($email)) === 0;
        }

        if ($phone === null || trim($phone) === '') {
            return false;
        }

        $stored = self::normalizePhone($order->guest_phone);
        $given = self::normalizePhone($phone);

        if ($stored === '' || $given === '') {
            return false;
        }

        return $stored === $given
            || str_ends_with($stored, $given)
            || str_ends_with($given, $stored);
    }

    public static function sessionKey(int $orderId): string
    {
        return "guest_order_access.{$orderId}";
    }

    public static function grant(Request $request, Order $order): void
    {
        $request->session()->put(self::sessionKey($order->id), true);
    }

    public static function hasAccess(Request $request, Order $order): bool
    {
        if (! self::shouldProtect($order)) {
            return true;
        }

        if ($request->session()->get(self::sessionKey($order->id))) {
            return true;
        }

        $queryCode = $request->query('code');
        if ($queryCode && self::verifyCode($order, $queryCode)) {
            self::grant($request, $order);

            return true;
        }

        return false;
    }

    public static function trackUrl(Order $order, Tenant $tenant, bool $withCode = true): string
    {
        $url = route('tenant.track', [
            'tenant' => $tenant->slug,
            'order_number' => $order->order_number,
        ]);

        if ($withCode && $order->guest_access_code) {
            $url .= '?code='.urlencode($order->guest_access_code);
        }

        return $url;
    }
}
