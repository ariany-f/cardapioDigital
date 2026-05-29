<?php

namespace Tests\Feature;

use App\Models\Plan;
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
}
