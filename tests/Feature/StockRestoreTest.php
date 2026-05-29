<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_order_restores_stock(): void
    {
        Permission::findOrCreate('orders.cancel');

        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $category = Category::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lanches',
            'slug' => 'lanches',
            'is_active' => true,
        ]);
        $product = Product::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'X-Burger',
            'base_price' => 25,
            'track_stock' => true,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);
        $admin->givePermissionTo('orders.cancel');

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'ACME-STK-1',
            'type' => 'pickup',
            'status' => 'preparing',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 50,
            'total' => 50,
        ]);
        OrderItem::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'name' => 'X-Burger',
            'quantity' => 2,
            'unit_price' => 25,
            'total_price' => 50,
        ]);

        app(StockService::class)->decrementForOrder($product, 2, $branch->id, $order->id, $tenant->id);
        $this->assertSame(8, $product->fresh()->stock_quantity);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/orders/{$order->id}/cancel", [
                'cancel_reason' => 'Teste estoque',
            ])
            ->assertRedirect();

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertTrue(
            StockMovement::query()
                ->where('order_id', $order->id)
                ->where('reason', 'order_cancelled')
                ->exists()
        );
    }
}
