<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatEligibility
{
    public const RECENT_ORDER_DAYS = 15;

    public static function purchasedSessionKey(int $tenantId, int $branchId): string
    {
        return "chat_purchased.{$tenantId}.{$branchId}";
    }

    public static function guestProfileSessionKey(int $tenantId, int $branchId): string
    {
        return "chat_guest_profile.{$tenantId}.{$branchId}";
    }

    public static function recentOrderCutoff(): Carbon
    {
        return now()->subDays(self::RECENT_ORDER_DAYS);
    }

    public static function isRecent(?Carbon $at): bool
    {
        return $at !== null && $at->gte(self::recentOrderCutoff());
    }

    public static function markPurchased(
        Branch $branch,
        Request $request,
        array $guestProfile = [],
        ?Carbon $purchasedAt = null,
    ): void {
        $at = $purchasedAt ?? now();

        $request->session()->put(
            self::purchasedSessionKey($branch->tenant_id, $branch->id),
            $at->toIso8601String(),
        );

        if ($guestProfile !== []) {
            $request->session()->put(
                self::guestProfileSessionKey($branch->tenant_id, $branch->id),
                $guestProfile,
            );
        }
    }

    public static function guestProfile(Branch $branch, Request $request): ?array
    {
        $profile = $request->session()->get(
            self::guestProfileSessionKey($branch->tenant_id, $branch->id),
        );

        return is_array($profile) ? $profile : null;
    }

    public static function canStart(Branch $branch, ?Customer $customer, Request $request): bool
    {
        return self::isRecent(self::lastEligibleOrderAt($branch, $customer, $request));
    }

    public static function assertCanStart(Branch $branch, ?Customer $customer, Request $request): void
    {
        if (! self::canStart($branch, $customer, $request)) {
            abort(403, __('chat.not_eligible'));
        }
    }

    public static function lastEligibleOrderAt(Branch $branch, ?Customer $customer, Request $request): ?Carbon
    {
        if ($customer) {
            $createdAt = Order::query()
                ->where('tenant_id', $branch->tenant_id)
                ->where('customer_id', $customer->id)
                ->max('created_at');

            return $createdAt ? Carbon::parse($createdAt) : null;
        }

        $profile = self::guestProfile($branch, $request);
        if ($profile) {
            $guestLast = self::lastGuestOrderAt($branch->tenant_id, $profile);
            if ($guestLast) {
                return $guestLast;
            }
        }

        $sessionValue = $request->session()->get(
            self::purchasedSessionKey($branch->tenant_id, $branch->id),
        );

        if (is_string($sessionValue)) {
            try {
                return Carbon::parse($sessionValue);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * @param  array{name?: string, phone?: string, email?: string}  $profile
     */
    protected static function lastGuestOrderAt(int $tenantId, array $profile): ?Carbon
    {
        $phone = GuestOrderAccess::normalizePhone($profile['phone'] ?? null);
        $email = trim($profile['email'] ?? '');

        if ($phone === '' && $email === '') {
            return null;
        }

        $orders = Order::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('customer_id')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['created_at', 'guest_phone', 'guest_email']);

        foreach ($orders as $order) {
            if ($email !== '' && strcasecmp(trim($order->guest_email ?? ''), $email) === 0) {
                return $order->created_at;
            }

            if ($phone !== '' && GuestOrderAccess::matchesContact($order, $phone, null)) {
                return $order->created_at;
            }
        }

        return null;
    }
}
