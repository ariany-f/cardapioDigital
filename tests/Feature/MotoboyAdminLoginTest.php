<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Motoboy;
use App\Models\MotoboyReport;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MotoboyAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->tenant = Tenant::where('slug', 'acme')->first();
        $this->admin = User::where('email', 'admin@acme.test')->first();
    }

    public function test_admin_can_update_motoboy_login(): void
    {
        $motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Novo App',
            'phone' => '11911110000',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'operational_status' => 'available',
            'max_active_deliveries' => 2,
            'is_active' => true,
            'uses_app' => false,
        ]);

        $this->actingAs($this->admin)
            ->post(route('tenant.admin.motoboys.login.update', [
                'tenant' => $this->tenant->slug,
                'motoboy' => $motoboy->id,
            ]), [
                'uses_app' => true,
                'email' => 'novo.app@acme.test',
                'password' => 'senha123',
                'is_active' => true,
            ])
            ->assertRedirect();

        $motoboy->refresh();
        $this->assertTrue($motoboy->uses_app);
        $this->assertSame('novo.app@acme.test', $motoboy->email);
        $this->assertTrue(Hash::check('senha123', $motoboy->password));
    }

    public function test_admin_can_reset_motoboy_password(): void
    {
        $motoboy = Motoboy::withoutGlobalScopes()->where('email', 'joao.motoboy@acme.test')->first();

        $this->actingAs($this->admin)
            ->post(route('tenant.admin.motoboys.reset-password', [
                'tenant' => $this->tenant->slug,
                'motoboy' => $motoboy->id,
            ]), [
                'password' => 'novaSenha1!',
                'password_confirmation' => 'novaSenha1!',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('novaSenha1!', $motoboy->fresh()->password));
    }

    public function test_motoboys_index_includes_login_management_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tenant.admin.motoboys.index', ['tenant' => $this->tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Motoboys/Index')
                ->has('entregadorLoginUrl')
                ->has('loginStats')
                ->has('motoboys.0.login_status'));
    }

    public function test_motoboys_index_shows_delivery_rating_and_open_reports(): void
    {
        $motoboy = Motoboy::withoutGlobalScopes()->where('email', 'joao.motoboy@acme.test')->first();
        $branch = Branch::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->first();

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'ACME-RATING-1',
            'type' => 'delivery',
            'status' => 'delivered',
            'subtotal' => 30,
            'total' => 30,
        ]);

        OrderRating::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'branch_id' => $branch->id,
            'motoboy_id' => $motoboy->id,
            'rating' => 5,
            'restaurant_rating' => 5,
            'delivery_rating' => 4,
            'status' => OrderRating::STATUS_APPROVED,
        ]);

        MotoboyReport::create([
            'tenant_id' => $this->tenant->id,
            'motoboy_id' => $motoboy->id,
            'message' => 'Entrega atrasada',
            'status' => 'open',
        ]);

        $this->actingAs($this->admin)
            ->get(route('tenant.admin.motoboys.index', ['tenant' => $this->tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Motoboys/Index')
                ->where('motoboys', function ($motoboys) use ($motoboy) {
                    $row = collect($motoboys)->firstWhere('id', $motoboy->id);

                    return $row
                        && (float) $row['delivery_rating_average'] === 4.0
                        && $row['delivery_rating_count'] === 1
                        && $row['open_reports_count'] === 1;
                }));
    }
}
