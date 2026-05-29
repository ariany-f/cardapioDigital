<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\TenantFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformTenantPlanModulesTest extends TestCase
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
            'password' => bcrypt('password'),
            'is_platform_user' => true,
        ]);
        $this->admin->assignRole('superadmin');
    }

    public function test_cannot_enable_pos_when_plan_excludes_it(): void
    {
        $plan = Plan::create([
            'name' => 'Básico',
            'slug' => 'basico-sem-pos',
            'price_monthly' => 29.90,
            'features_json' => [
                'max_branches' => 1,
                'kds' => true,
                'pos' => false,
                'reports' => false,
                'delivery_webhooks' => false,
                'motoboys' => false,
            ],
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Loja',
            'slug' => 'loja-pos',
            'status' => 'active',
            'settings_json' => ['pos_enabled' => true],
        ]);

        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        $this->assertFalse(TenantFeatures::posEnabled($tenant->fresh()));

        $this->actingAs($this->admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Loja',
                'slug' => 'loja-pos',
                'pos_enabled' => true,
            ])
            ->assertSessionHasErrors('pos_enabled');
    }

    public function test_cannot_enable_motoboys_on_create_when_plan_excludes_it(): void
    {
        $plan = Plan::create([
            'name' => 'Sem entregadores',
            'slug' => 'sem-entregadores',
            'price_monthly' => 19.90,
            'features_json' => [
                'max_branches' => 1,
                'kds' => true,
                'pos' => true,
                'reports' => false,
                'delivery_webhooks' => false,
                'motoboys' => false,
            ],
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('platform.tenants.store'), [
                'name' => 'Nova Loja',
                'plan_id' => $plan->id,
                'motoboys_enabled' => true,
            ])
            ->assertSessionHasErrors('motoboys_enabled');

        $this->assertDatabaseMissing('tenants', ['name' => 'Nova Loja']);
    }
}
