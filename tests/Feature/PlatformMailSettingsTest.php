<?php

namespace Tests\Feature;

use App\Mail\PlatformMailTestMail;
use App\Models\PlatformMailSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformUser = User::factory()->create([
            'is_platform_user' => true,
            'tenant_id' => null,
            'email' => 'platform@test.com',
        ]);
    }

    public function test_guest_cannot_access_mail_settings(): void
    {
        $this->get(route('platform.settings.email'))
            ->assertRedirect(route('login'));
    }

    public function test_platform_user_can_view_mail_settings(): void
    {
        $this->actingAs($this->platformUser)
            ->get(route('platform.settings.email'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Settings/Email')
                ->has('settings')
                ->where('settings.enabled', false));
    }

    public function test_platform_user_can_save_smtp_settings(): void
    {
        $this->actingAs($this->platformUser)
            ->put(route('platform.settings.email.update'), [
                'enabled' => true,
                'host' => 'smtp.test.com',
                'port' => 587,
                'username' => 'user@test.com',
                'password' => 'secret-pass',
                'encryption' => 'tls',
                'from_address' => 'noreply@test.com',
                'from_name' => 'App Test',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = PlatformMailSetting::current();
        $this->assertTrue($settings->enabled);
        $this->assertSame('smtp.test.com', $settings->host);
        $this->assertSame('secret-pass', $settings->password);
        $this->assertSame('noreply@test.com', $settings->from_address);
    }

    public function test_saved_settings_override_mail_config(): void
    {
        PlatformMailSetting::current()->update([
            'enabled' => true,
            'host' => 'db-smtp.example.com',
            'port' => 465,
            'username' => 'db-user',
            'password' => 'db-pass',
            'encryption' => 'ssl',
            'from_address' => 'from@example.com',
            'from_name' => 'DB Mail',
        ]);

        $this->app->make(\App\Services\Mail\PlatformMailConfigurator::class)->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('db-smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('from@example.com', config('mail.from.address'));
    }

    public function test_send_test_queues_mail_when_enabled(): void
    {
        Mail::fake();

        PlatformMailSetting::current()->update([
            'enabled' => true,
            'host' => 'smtp.test.com',
            'port' => 587,
            'password' => 'x',
            'from_address' => 'noreply@test.com',
        ]);

        $this->actingAs($this->platformUser)
            ->post(route('platform.settings.email.test'), [
                'to' => 'destino@test.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(PlatformMailTestMail::class, function (PlatformMailTestMail $mail) {
            return $mail->hasTo('destino@test.com');
        });
    }

    public function test_cannot_enable_without_password_on_first_save(): void
    {
        $this->actingAs($this->platformUser)
            ->from(route('platform.settings.email'))
            ->put(route('platform.settings.email.update'), [
                'enabled' => true,
                'host' => 'smtp.test.com',
                'port' => 587,
                'from_address' => 'noreply@test.com',
            ])
            ->assertSessionHasErrors('password');
    }
}
