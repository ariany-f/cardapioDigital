<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Order;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_order_records_user_and_activity(): void
    {
        Permission::findOrCreate('orders.accept');
        Permission::findOrCreate('orders.view');

        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
        ]);
        $admin = User::create([
            'name' => 'Maria Admin',
            'email' => 'maria@acme.test',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);
        $admin->givePermissionTo(['orders.accept', 'orders.view']);

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'ACME-0001',
            'type' => 'pickup',
            'status' => 'pending_approval',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 10,
            'total' => 10,
        ]);

        $this->actingAs($admin)
            ->post("/{$tenant->slug}/admin/orders/{$order->id}/accept")
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('confirmed', $order->status);
        $this->assertNotNull($order->approved_at);
        $this->assertSame($admin->id, $order->approved_by_user_id);

        $this->assertTrue(
            ActivityLog::withoutGlobalScopes()
                ->where('action', 'order.approved')
                ->where('subject_id', $order->id)
                ->where('actor_user_id', $admin->id)
                ->exists()
        );
    }

    public function test_support_response_is_logged(): void
    {
        Permission::findOrCreate('requests.close');
        Permission::findOrCreate('requests.view');

        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $admin = User::create([
            'name' => 'Suporte ACME',
            'email' => 'suporte@acme.test',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);
        $admin->givePermissionTo(['requests.close', 'requests.view']);

        $request = SupportRequest::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'type' => 'help',
            'subject' => 'Dúvida',
            'message' => 'Onde está meu pedido?',
            'status' => 'open',
            'guest_name' => 'João',
        ]);

        $this->actingAs($admin)
            ->patch("/{$tenant->slug}/admin/requests/{$request->id}", [
                'admin_notes' => 'Já enviamos o pedido.',
                'status' => 'in_progress',
            ])
            ->assertRedirect();

        $request->refresh();

        $this->assertSame($admin->id, $request->last_responded_by_user_id);
        $this->assertNotNull($request->last_responded_at);

        $this->assertTrue(
            ActivityLog::withoutGlobalScopes()
                ->where('subject_id', $request->id)
                ->where('action', 'support.note_updated')
                ->exists()
        );
    }
}
