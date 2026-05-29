<?php

namespace App\Support;

class PlatformCommunicationDisclaimer
{
    /**
     * Textos compartilhados com o frontend (Inertia) e e-mails.
     *
     * @return array<string, mixed>
     */
    public static function forInertia(?string $restaurantName = null): array
    {
        return [
            'customer' => [
                'title' => __('platform.communication.customer_title'),
                'body' => __('platform.communication.customer_body'),
                'alerts' => __('platform.communication.customer_alerts'),
                'support_hint' => __('platform.communication.customer_support_hint'),
            ],
            'restaurant' => [
                'title' => __('platform.communication.restaurant_title'),
                'body' => __('platform.communication.restaurant_body'),
                'alerts' => __('platform.communication.restaurant_alerts'),
            ],
            'footer_customer_hint' => __('platform.communication.footer_customer_hint'),
            'footer_short' => __('platform.communication.footer_short'),
            'footer_admin' => __('platform.communication.footer_admin'),
            'footer_platform' => __('platform.communication.footer_platform'),
            'restaurant_name' => $restaurantName,
            'delivery' => self::deliveryForInertia(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function deliveryForInertia(): array
    {
        return [
            'onboarding_title' => __('platform.delivery.onboarding_title'),
            'onboarding_body' => __('platform.delivery.onboarding_body'),
            'onboarding_plan_note' => __('platform.delivery.onboarding_plan_note'),
            'motoboys_module_label' => __('platform.delivery.motoboys_module_label'),
            'motoboys_module_help' => __('platform.delivery.motoboys_module_help'),
            'motoboys_plan_blocked' => __('platform.delivery.motoboys_plan_blocked'),
            'motoboys_plan_blocked_edit' => __('platform.delivery.motoboys_plan_blocked_edit'),
            'motoboy_admin_title' => __('platform.delivery.motoboy_admin_title'),
            'motoboy_admin_body' => __('platform.delivery.motoboy_admin_body'),
        ];
    }

    public static function emailStatusIntro(?string $restaurantName): string
    {
        $name = $restaurantName ?: 'o restaurante';

        return __('platform.communication.email_status_intro', ['restaurant' => $name]);
    }

    public static function emailFooter(): string
    {
        return __('platform.communication.email_footer');
    }
}
