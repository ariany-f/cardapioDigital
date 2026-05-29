<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRatingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->tenant = Tenant::where('slug', 'acme')->first();
        $this->branch = Branch::where('slug', 'centro')->first();
        $this->admin = User::where('email', 'admin@acme.test')->first();
    }

    public function test_customer_can_submit_multi_dimension_rating(): void
    {
        $order = $this->deliveredOrder();

        $this->post(route('tenant.track.rate', [
            'tenant' => $this->tenant->slug,
            'order_number' => $order->order_number,
        ]), [
            'rating' => 5,
            'comment' => 'Comida ótima',
            'restaurant_rating' => 4,
            'restaurant_comment' => 'Ambiente legal',
            'delivery_rating' => 5,
            'delivery_comment' => 'Chegou rápido',
        ])->assertRedirect();

        $rating = OrderRating::where('order_id', $order->id)->first();
        $this->assertSame(5, $rating->rating);
        $this->assertSame(4, $rating->restaurant_rating);
        $this->assertSame(5, $rating->delivery_rating);
        $this->assertSame($this->branch->id, $rating->branch_id);
    }

    public function test_admin_can_hide_rating(): void
    {
        $order = $this->deliveredOrder();
        $rating = OrderRating::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'rating' => 3,
            'restaurant_rating' => 3,
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('tenant.admin.ratings.update-status', [
                'tenant' => $this->tenant->slug,
                'rating' => $rating->id,
            ]), ['status' => 'hidden'])
            ->assertRedirect();

        $this->assertSame('hidden', $rating->fresh()->status);
    }

    public function test_platform_can_list_and_moderate_ratings(): void
    {
        $platform = User::where('email', env('SEED_SUPERADMIN_EMAIL', 'admin@admin.com.br'))->first();
        $order = $this->deliveredOrder();

        OrderRating::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'rating' => 2,
            'restaurant_rating' => 1,
            'restaurant_comment' => 'Ruim',
            'status' => 'approved',
        ]);

        $this->actingAs($platform)
            ->get(route('platform.ratings.index', ['type' => 'restaurant']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Ratings/Index'));
    }

    public function test_tenant_averages_exclude_hidden(): void
    {
        $order = $this->deliveredOrder();

        OrderRating::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'rating' => 5,
            'restaurant_rating' => 5,
            'status' => 'approved',
        ]);

        $order2 = Order::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'status' => 'delivered',
            'order_number' => 'ACME-9998',
            'type' => 'pickup',
            'subtotal' => 20,
            'total' => 20,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        OrderRating::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order2->id,
            'branch_id' => $order2->branch_id,
            'rating' => 1,
            'restaurant_rating' => 1,
            'status' => 'hidden',
        ]);

        $service = app(\App\Services\OrderRatingService::class);
        $avg = $service->tenantAverages($this->tenant->id);
        $this->assertSame(1, $avg['count']);
        $this->assertSame(5.0, $avg['restaurant']);

        $batch = $service->tenantAveragesForMany([$this->tenant->id, 99999]);
        $this->assertSame(5.0, $batch[$this->tenant->id]['restaurant']);
        $this->assertSame(0, $batch[99999]['count']);

        $branchBatch = $service->branchAveragesForMany($this->tenant->id, [$this->branch->id]);
        $this->assertSame(5.0, $branchBatch[$this->branch->id]['restaurant']);
    }

    protected function deliveredOrder(): Order
    {
        return Order::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('status', 'delivered')
            ->first()
            ?? Order::create([
                'tenant_id' => $this->tenant->id,
                'branch_id' => $this->branch->id,
                'order_number' => 'ACME-RATE-1',
                'type' => 'delivery',
                'status' => 'delivered',
                'subtotal' => 50,
                'total' => 50,
                'payment_status' => 'paid',
                'payment_method' => 'pix',
            ]);
    }
}
