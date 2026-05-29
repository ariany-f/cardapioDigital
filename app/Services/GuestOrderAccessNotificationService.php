<?php

namespace App\Services;

use App\Mail\GuestOrderAccessMail;
use App\Models\Order;
use App\Models\Tenant;
use App\Support\GuestOrderAccess;
use Illuminate\Support\Facades\Mail;

class GuestOrderAccessNotificationService
{
    public function send(Order $order, Tenant $tenant): void
    {
        if (! $order->guest_access_code) {
            return;
        }

        $trackUrl = GuestOrderAccess::trackUrl($order, $tenant);
        $lookupUrl = route('tenant.track.lookup', ['tenant' => $tenant->slug]);

        if ($order->guest_email) {
            Mail::to($order->guest_email)->send(
                new GuestOrderAccessMail($order, $tenant, $trackUrl, $lookupUrl),
            );
        }
    }
}
