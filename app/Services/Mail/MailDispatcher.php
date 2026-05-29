<?php

namespace App\Services\Mail;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailDispatcher
{
    public function send(string|array $to, MailableContract $mailable): void
    {
        if ($this->shouldSuppress()) {
            $this->logSuppressed($to, $mailable);

            return;
        }

        Mail::to($to)->queue($mailable);
    }

    public function sendNow(string|array $to, MailableContract $mailable, bool $force = false): void
    {
        if (! $force && $this->shouldSuppress()) {
            $this->logSuppressed($to, $mailable);

            return;
        }

        Mail::to($to)->send($mailable);
    }

    protected function logSuppressed(string|array $to, MailableContract $mailable): void
    {
        Log::channel('mail')->info('Email suppressed (local)', [
            'to' => $to,
            'mailable' => $mailable::class,
            'subject' => method_exists($mailable, 'envelope') ? $mailable->envelope()->subject : null,
        ]);
    }

    protected function shouldSuppress(): bool
    {
        if (config('mail.send_in_local') === true) {
            return false;
        }

        return app()->environment('local');
    }
}
