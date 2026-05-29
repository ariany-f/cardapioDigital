<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Delivery;
use App\Models\Motoboy;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantFeatures;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantMotoboysFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    public function test_motoboys_enabled_by_default(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);

        $this->assertTrue(TenantFeatures::motoboysEnabled($tenant));
    }

    public function test_motoboys_disabled_when_plan_excludes_feature(): void
    {
        $plan = \App\Models\Plan::create([
            'name' => 'Sem motoboy',
            'slug' => 'sem-motoboy',
            'price_monthly' => 10,
            'features_json' => ['motoboys' => false],
            'is_active' => true,
        ]);
        $tenant = Tenant::create([
            'name' => 'Loja',
            'slug' => 'loja-plan',
            'status' => 'active',
            'settings_json' => ['motoboys_enabled' => true],
        ]);
        \App\Models\TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        $this->assertFalse(TenantFeatures::motoboysEnabled($tenant->fresh()));
    }

    public function test_cannot_disable_motoboys_while_delivery_in_progress(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'João',
            'phone' => '11999990000',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'is_active' => true,
            'uses_app' => false,
        ]);
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'LOJA-1001',
            'type' => 'delivery',
            'status' => 'out_for_delivery',
            'subtotal' => 30,
            'total' => 30,
        ]);
        Delivery::create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'motoboy_id' => $motoboy->id,
            'status' => 'on_route',
            'motoboy_assignment_status' => 'accepted',
        ]);

        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Loja',
                'slug' => 'loja',
                'motoboys_enabled' => false,
            ])
            ->assertSessionHasErrors('motoboys_enabled');

        $tenant->refresh();

        $this->assertTrue(TenantFeatures::motoboysEnabled($tenant));
        $this->assertTrue(TenantFeatures::hasMotoboyDeliveriesInProgress($tenant));
    }

    public function test_can_disable_motoboys_after_active_deliveries_finish(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'João',
            'phone' => '11999990000',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'is_active' => true,
            'uses_app' => false,
        ]);
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'LOJA-1002',
            'type' => 'delivery',
            'status' => 'delivered',
            'subtotal' => 30,
            'total' => 30,
        ]);
        Delivery::create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'motoboy_id' => $motoboy->id,
            'status' => 'delivered',
            'motoboy_assignment_status' => 'accepted',
        ]);

        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Loja',
                'slug' => 'loja',
                'motoboys_enabled' => false,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertFalse(TenantFeatures::motoboysEnabled($tenant->fresh()));
    }

    public function test_platform_can_disable_motoboys_for_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Loja',
                'slug' => 'loja',
                'motoboys_enabled' => false,
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertFalse(TenantFeatures::motoboysEnabled($tenant));
    }

    public function test_platform_update_ignores_empty_plan_id_when_disabling_modules(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Loja',
                'slug' => 'loja',
                'plan_id' => '',
                'motoboys_enabled' => false,
                'pos_enabled' => false,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $tenant->refresh();

        $this->assertFalse(TenantFeatures::motoboysEnabled($tenant));
        $this->assertFalse(TenantFeatures::posEnabled($tenant));
    }

    public function test_admin_motoboys_page_blocked_when_feature_disabled(): void
    {
        $tenant = Tenant::create([
            'name' => 'Loja',
            'slug' => 'loja',
            'status' => 'active',
            'settings_json' => ['motoboys_enabled' => false],
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->givePermissionTo('deliveries.update');

        $this->actingAs($user)
            ->get(route('tenant.admin.motoboys.index', ['tenant' => $tenant->slug]))
            ->assertForbidden();
    }

    public function test_entregador_login_blocked_when_feature_disabled(): void
    {
        $tenant = Tenant::create([
            'name' => 'Loja',
            'slug' => 'loja',
            'status' => 'active',
            'settings_json' => ['motoboys_enabled' => false],
        ]);

        $this->get(route('tenant.entregador.login', ['tenant' => $tenant->slug]))
            ->assertRedirect(route('tenant.home', ['tenant' => $tenant->slug]))
            ->assertSessionHas('error');
    }
}
