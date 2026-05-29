<?php

namespace Tests\Feature;

use App\Models\PlatformGoogleMapsSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformGoogleMapsSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');

        $this->admin = User::create([
            'name' => 'Super',
            'email' => 'super@test.com',
            'password' => Hash::make('password'),
            'is_platform_user' => true,
        ]);
        $this->admin->assignRole('superadmin');
    }

    public function test_platform_user_can_save_google_maps_settings(): void
    {
        $this->actingAs($this->admin)
            ->put(route('platform.settings.maps.update'), [
                'enabled' => true,
                'api_key' => 'test-google-key-123',
            ])
            ->assertRedirect();

        $settings = PlatformGoogleMapsSetting::current();
        $this->assertTrue($settings->enabled);
        $this->assertSame('test-google-key-123', $settings->api_key);
    }

    public function test_google_maps_shared_on_inertia_when_configured(): void
    {
        PlatformGoogleMapsSetting::current()->update([
            'enabled' => true,
            'api_key' => 'shared-key-abc',
        ]);

        $this->actingAs($this->admin)
            ->get(route('platform.settings.maps'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('googleMaps.enabled', true)
                ->where('googleMaps.api_key', 'shared-key-abc'));
    }
}
