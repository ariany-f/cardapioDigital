<?php

namespace App\Services\Mail;

use App\Models\PlatformMailSetting;
use Illuminate\Support\Facades\Schema;

class PlatformMailConfigurator
{
    public function apply(): void
    {
        if (! Schema::hasTable('platform_mail_settings')) {
            return;
        }

        $settings = PlatformMailSetting::current();

        if (! $settings->enabled || ! filled($settings->host)) {
            return;
        }

        $scheme = $settings->encryption === 'ssl' ? 'smtps' : null;

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port ?: 587,
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $settings->password,
            'mail.mailers.smtp.scheme' => $scheme,
        ]);

        if (filled($settings->from_address)) {
            config([
                'mail.from.address' => $settings->from_address,
                'mail.from.name' => $settings->from_name ?: config('app.name'),
            ]);
        }
    }
}
