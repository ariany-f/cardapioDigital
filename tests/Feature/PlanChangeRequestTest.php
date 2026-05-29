<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanChangeRequest;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformAdmin;

    protected User $tenantAdmin;

    protected Tenant $tenant;

    protected Plan $basicPlan;

    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');

        $this->basicPlan = Plan::create([
            'name' => 'Básico',
            'slug' => 'basico-pcr',
            'price_monthly' => 29.90,
            'is_active' => true,
            'features_json' => $this->planFeatures(motoboys: false, pos: false),
        ]);

        $this->proPlan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro-pcr',
            'price_monthly' => 99.90,
            'is_active' => true,
            'features_json' => $this->planFeatures(motoboys: true, pos: true),
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Loja PCR',
            'slug' => 'loja-pcr',
            'status' => 'active',
        ]);

        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->basicPlan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        $this->platformAdmin = User::factory()->create([
            'is_platform_user' => true,
            'tenant_id' => null,
        ]);

        $this->tenantAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_platform_user' => false,
        ]);
        $this->tenantAdmin->assignRole('admin');
        Permission::findOrCreate('users.manage');
        $this->tenantAdmin->givePermissionTo('users.manage');
    }

    public function test_tenant_admin_can_request_plan_change(): void
    {
        $this->actingAs($this->tenantAdmin)
            ->post(route('tenant.admin.plan-change-requests.store', ['tenant' => $this->tenant->slug]), [
                'requested_plan_id' => $this->proPlan->id,
                'message' => 'Precisamos do plano Pro.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('plan_change_requests', [
            'tenant_id' => $this->tenant->id,
            'current_plan_id' => $this->basicPlan->id,
            'requested_plan_id' => $this->proPlan->id,
            'status' => PlanChangeRequest::STATUS_PENDING,
        ]);
    }

    public function test_cannot_request_same_plan(): void
    {
        $this->actingAs($this->tenantAdmin)
            ->post(route('tenant.admin.plan-change-requests.store', ['tenant' => $this->tenant->slug]), [
                'requested_plan_id' => $this->basicPlan->id,
            ])
            ->assertSessionHasErrors('requested_plan_id');
    }

    public function test_cannot_create_second_pending_request(): void
    {
        PlanChangeRequest::create([
            'tenant_id' => $this->tenant->id,
            'current_plan_id' => $this->basicPlan->id,
            'requested_plan_id' => $this->proPlan->id,
            'requested_by' => $this->tenantAdmin->id,
            'status' => PlanChangeRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->tenantAdmin)
            ->post(route('tenant.admin.plan-change-requests.store', ['tenant' => $this->tenant->slug]), [
                'requested_plan_id' => $this->proPlan->id,
            ])
            ->assertSessionHasErrors('requested_plan_id');
    }

    public function test_platform_admin_can_approve_plan_change(): void
    {
        $request = PlanChangeRequest::create([
            'tenant_id' => $this->tenant->id,
            'current_plan_id' => $this->basicPlan->id,
            'requested_plan_id' => $this->proPlan->id,
            'requested_by' => $this->tenantAdmin->id,
            'status' => PlanChangeRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->platformAdmin)
            ->post(route('platform.plan-change-requests.approve', $request), [
                'admin_notes' => 'Aprovado.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $request->refresh();
        $this->tenant->refresh()->load('activeSubscription');

        $this->assertSame(PlanChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($this->proPlan->id, $this->tenant->activeSubscription?->plan_id);
    }

    public function test_platform_admin_can_reject_plan_change(): void
    {
        $request = PlanChangeRequest::create([
            'tenant_id' => $this->tenant->id,
            'current_plan_id' => $this->basicPlan->id,
            'requested_plan_id' => $this->proPlan->id,
            'requested_by' => $this->tenantAdmin->id,
            'status' => PlanChangeRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->platformAdmin)
            ->post(route('platform.plan-change-requests.reject', $request), [
                'admin_notes' => 'Aguardar pagamento.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $request->refresh();
        $this->tenant->refresh()->load('activeSubscription');

        $this->assertSame(PlanChangeRequest::STATUS_REJECTED, $request->status);
        $this->assertSame($this->basicPlan->id, $this->tenant->activeSubscription?->plan_id);
    }

    /** @return array<string, mixed> */
    private function planFeatures(bool $motoboys, bool $pos): array
    {
        return [
            'max_branches' => 1,
            'kds' => true,
            'pos' => $pos,
            'reports' => false,
            'delivery_webhooks' => false,
            'motoboys' => $motoboys,
        ];
    }
}
