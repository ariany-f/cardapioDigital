<?php

namespace Tests\Feature;

use App\Models\PlatformStorageSetting;
use App\Support\PlatformStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformStorageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected \App\Models\User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');

        $this->admin = \App\Models\User::create([
            'name' => 'Super',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'is_platform_user' => true,
        ]);
        $this->admin->assignRole('superadmin');
    }

    public function test_platform_user_can_open_storage_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('platform.settings.storage'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Settings/Storage'));
    }

    public function test_platform_user_can_save_s3_settings(): void
    {
        $this->actingAs($this->admin)
            ->put(route('platform.settings.storage.update'), [
                'enabled' => true,
                'key' => 'AKIATEST',
                'secret' => 'secret-test',
                'region' => 'sa-east-1',
                'bucket' => 'appcardapio-test',
                'url' => 'https://cdn.example.com',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = PlatformStorageSetting::current();
        $this->assertTrue($settings->enabled);
        $this->assertSame('AKIATEST', $settings->key);
        $this->assertSame('appcardapio-test', $settings->bucket);
        $this->assertTrue($settings->isConfigured());
    }

    public function test_storage_configurator_applies_s3_disk(): void
    {
        PlatformStorageSetting::current()->update([
            'enabled' => true,
            'key' => 'AKIATEST',
            'secret' => 'secret-test',
            'region' => 'us-east-1',
            'bucket' => 'my-bucket',
            'url' => 'https://s3.example.com',
        ]);

        $this->app->make(\App\Services\Storage\PlatformStorageConfigurator::class)->apply();

        $this->assertSame('s3', config('filesystems.default'));
        $this->assertSame('my-bucket', config('filesystems.disks.s3.bucket'));
        $this->assertSame('AKIATEST', config('filesystems.disks.s3.key'));
        $this->assertSame('https://s3.example.com', config('filesystems.disks.s3.url'));
    }

    public function test_upload_disk_uses_s3_when_configured(): void
    {
        PlatformStorageSetting::current()->update([
            'enabled' => true,
            'key' => 'AKIATEST',
            'secret' => 'secret-test',
            'region' => 'us-east-1',
            'bucket' => 'my-bucket',
        ]);

        $this->assertSame('s3', PlatformStorage::uploadDisk());
    }

    public function test_cannot_enable_without_secret_on_first_save(): void
    {
        $this->actingAs($this->admin)
            ->from(route('platform.settings.storage'))
            ->put(route('platform.settings.storage.update'), [
                'enabled' => true,
                'key' => 'AKIATEST',
                'region' => 'us-east-1',
                'bucket' => 'my-bucket',
            ])
            ->assertSessionHasErrors('secret');
    }

    public function test_connection_test_uses_s3_disk(): void
    {
        Storage::fake('s3');

        PlatformStorageSetting::current()->update([
            'enabled' => true,
            'key' => 'AKIATEST',
            'secret' => 'secret-test',
            'region' => 'us-east-1',
            'bucket' => 'my-bucket',
        ]);

        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key' => 'AKIATEST',
                'secret' => 'secret-test',
                'region' => 'us-east-1',
                'bucket' => 'my-bucket',
                'throw' => false,
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('platform.settings.storage.test'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }
}
