<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TenantWebhookToken;
use App\Services\DeliveryConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_marks_delivered_with_code_and_logs_activity(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'ACME-WH-1',
            'type' => 'delivery',
            'status' => 'out_for_delivery',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 50,
            'total' => 50,
        ]);
        $order->update(['delivery_confirmation_code' => '482910']);

        $token = TenantWebhookToken::create([
            'tenant_id' => $tenant->id,
            'name' => 'Integração',
            'token' => 'secret-webhook-token',
            'type' => 'delivery',
            'is_active' => true,
        ]);

        $this->postJson('/api/webhooks/delivery', [
            'order_number' => 'ACME-WH-1',
            'status' => 'delivered',
            'confirmation_code' => '482910',
        ], ['X-Tenant-Token' => 'secret-webhook-token'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertNotNull($order->delivery_confirmed_at);

        $this->assertTrue(
            ActivityLog::withoutGlobalScopes()
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->where('action', 'webhook.delivery_status')
                ->exists()
        );

        $this->assertNotNull($token->id);
    }

    public function test_webhook_rejects_wrong_confirmation_code(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'ACME-WH-2',
            'type' => 'delivery',
            'status' => 'out_for_delivery',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 50,
            'total' => 50,
        ]);
        app(DeliveryConfirmationService::class)->ensureCode($order->fresh());

        TenantWebhookToken::create([
            'tenant_id' => $tenant->id,
            'name' => 'Integração',
            'token' => 'token-2',
            'type' => 'delivery',
            'is_active' => true,
        ]);

        $this->postJson('/api/webhooks/delivery', [
            'order_number' => 'ACME-WH-2',
            'status' => 'delivered',
            'confirmation_code' => '000000',
        ], ['X-Tenant-Token' => 'token-2'])
            ->assertStatus(422);

        $this->assertSame('out_for_delivery', $order->fresh()->status);
    }

    public function test_webhook_requires_valid_token(): void
    {
        $this->postJson('/api/webhooks/delivery', [
            'order_number' => 'X',
            'status' => 'delivered',
        ], ['X-Tenant-Token' => 'invalid'])
            ->assertUnauthorized();
    }
}
