<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformTenantPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Tenant $tenant;

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

        $this->tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
    }

    public function test_platform_user_can_update_payment(): void
    {
        $payment = TenantPayment::create([
            'tenant_id' => $this->tenant->id,
            'amount' => 99.90,
            'reference' => 'PIX-OLD',
            'paid_at' => now()->subDay(),
            'marked_by' => $this->admin->id,
            'notes' => 'Antigo',
        ]);

        $this->actingAs($this->admin)
            ->put(route('platform.payments.update', $payment), [
                'amount' => 149.50,
                'reference' => 'PIX-NEW',
                'paid_at' => now()->format('Y-m-d'),
                'notes' => 'Corrigido',
            ])
            ->assertRedirect(route('platform.payments.index', ['tenant_id' => $this->tenant->id]));

        $payment->refresh();
        $this->assertSame('149.50', $payment->amount);
        $this->assertSame('PIX-NEW', $payment->reference);
        $this->assertSame('Corrigido', $payment->notes);
    }
}
