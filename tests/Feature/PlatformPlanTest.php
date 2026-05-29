<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformPlanTest extends TestCase
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

    public function test_plans_page_includes_create_form_translations(): void
    {
        $this->actingAs($this->admin)
            ->get(route('platform.plans.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('platformTranslations.plans.create_title')
                ->has('platformTranslations.plans.limits_title')
                ->has('platformTranslations.plans.slug_help')
                ->where('platformTranslations.plans.create_submit', 'Salvar plano')
                ->where('platformTranslations.plans.features.max_branches', 'Filiais máximas'));
    }

    public function test_platform_user_can_create_plan_with_features(): void
    {
        $this->actingAs($this->admin)
            ->post(route('platform.plans.store'), [
                'name' => 'Starter',
                'slug' => 'starter',
                'price_monthly' => 49.90,
                'is_active' => true,
                'features_json' => [
                    'max_branches' => 3,
                    'kds' => true,
                    'pos' => false,
                    'reports' => true,
                    'delivery_webhooks' => false,
                    'motoboys' => false,
                ],
            ])
            ->assertRedirect();

        $plan = Plan::where('slug', 'starter')->first();
        $this->assertNotNull($plan);
        $this->assertSame('Starter', $plan->name);
        $this->assertSame('49.90', $plan->price_monthly);
        $this->assertSame(3, $plan->features_json['max_branches']);
        $this->assertTrue($plan->features_json['kds']);
        $this->assertFalse($plan->features_json['motoboys']);
    }

    public function test_platform_user_can_update_plan_limits(): void
    {
        $plan = Plan::create([
            'name' => 'Básico',
            'slug' => 'basico',
            'price_monthly' => 29.90,
            'is_active' => true,
            'features_json' => [
                'max_branches' => 2,
                'kds' => true,
                'pos' => false,
                'reports' => false,
                'delivery_webhooks' => false,
                'motoboys' => false,
            ],
        ]);

        $this->actingAs($this->admin)
            ->put(route('platform.plans.update', $plan), [
                'name' => 'Básico Plus',
                'price_monthly' => 39.90,
                'is_active' => true,
                'features_json' => [
                    'max_branches' => 4,
                    'kds' => true,
                    'pos' => true,
                    'reports' => true,
                    'delivery_webhooks' => true,
                    'motoboys' => true,
                ],
            ])
            ->assertRedirect();

        $plan->refresh();
        $this->assertSame('Básico Plus', $plan->name);
        $this->assertSame(4, $plan->features_json['max_branches']);
        $this->assertTrue($plan->features_json['pos']);
        $this->assertTrue($plan->features_json['motoboys']);
    }

    public function test_platform_user_can_delete_plan_without_subscriptions(): void
    {
        $plan = Plan::create([
            'name' => 'Legado',
            'slug' => 'legado',
            'price_monthly' => 19.90,
            'is_active' => false,
            'features_json' => $this->defaultPlanFeatures(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_cannot_delete_plan_with_subscriptions(): void
    {
        $plan = Plan::create([
            'name' => 'Básico',
            'slug' => 'basico-delete',
            'price_monthly' => 29.90,
            'is_active' => true,
            'features_json' => $this->defaultPlanFeatures(),
        ]);

        $tenant = Tenant::create([
            'name' => 'Loja',
            'slug' => 'loja-plano',
            'status' => 'active',
        ]);

        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    /** @return array<string, mixed> */
    private function defaultPlanFeatures(): array
    {
        return [
            'max_branches' => 1,
            'kds' => true,
            'pos' => false,
            'reports' => false,
            'delivery_webhooks' => false,
            'motoboys' => false,
        ];
    }
}
