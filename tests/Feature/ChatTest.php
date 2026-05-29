<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ChatEligibility;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
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

    protected function withRecentGuestChatSession(): static
    {
        return $this->withSession([
            ChatEligibility::purchasedSessionKey($this->tenant->id, $this->branch->id) => now()->toIso8601String(),
        ]);
    }

    public function test_guest_can_start_chat_and_send_message(): void
    {
        $start = $this->withRecentGuestChatSession()->postJson(route('tenant.chat.start', [
            'tenant' => $this->tenant->slug,
            'branch' => $this->branch->slug,
        ]), [
            'guest_name' => 'Maria',
            'guest_phone' => '11999999999',
        ])->assertOk();

        $uuid = $start->json('conversation.uuid');
        $guestKey = $start->json('guest_key');

        $this->postJson(route('tenant.chat.send', [
            'tenant' => $this->tenant->slug,
            'uuid' => $uuid,
        ]), [
            'body' => 'Olá, quero fazer um pedido',
            'guest_key' => $guestKey,
        ], [
            'X-Chat-Guest-Key' => $guestKey,
        ])->assertOk();

        $this->actingAs($this->admin)
            ->getJson(route('tenant.admin.chat.messages', [
                'tenant' => $this->tenant->slug,
                'uuid' => $uuid,
            ]))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Olá, quero fazer um pedido');
    }

    public function test_guest_cannot_start_chat_without_purchase(): void
    {
        $this->postJson(route('tenant.chat.start', [
            'tenant' => $this->tenant->slug,
            'branch' => $this->branch->slug,
        ]), [
            'guest_name' => 'Maria',
        ])->assertForbidden();
    }

    public function test_customer_without_orders_cannot_start_chat(): void
    {
        $customer = Customer::create([
            'name' => 'Sem pedidos',
            'email' => 'sempedidos@test.com',
            'phone' => '11977776666',
            'password' => 'password',
        ]);

        $this->actingAs($customer, 'customer')
            ->postJson(route('tenant.chat.start', [
                'tenant' => $this->tenant->slug,
                'branch' => $this->branch->slug,
            ]), [
                'guest_name' => 'ignored',
            ])
            ->assertForbidden();
    }

    public function test_customer_with_orders_can_start_chat(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente',
            'email' => 'cliente@test.com',
            'phone' => '11988887777',
            'password' => 'password',
        ]);

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'order_number' => 'CHAT-TEST-001',
            'status' => 'delivered',
            'type' => 'pickup',
            'source' => 'web',
            'subtotal' => 10,
            'total' => 10,
            'payment_status' => 'pending',
        ]);

        $this->actingAs($customer, 'customer')
            ->postJson(route('tenant.chat.start', [
                'tenant' => $this->tenant->slug,
                'branch' => $this->branch->slug,
            ]), [
                'guest_name' => 'ignored',
            ])
            ->assertOk();
    }

    public function test_admin_can_reply_in_chat(): void
    {
        $conversation = ChatConversation::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'guest_name' => 'João',
            'guest_key' => 'guest-test-key',
            'status' => 'open',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('tenant.admin.chat.send', [
                'tenant' => $this->tenant->slug,
                'uuid' => $conversation->uuid,
            ]), ['body' => 'Olá! Como posso ajudar?'])
            ->assertOk()
            ->assertJsonPath('message.body', 'Olá! Como posso ajudar?');
    }

    public function test_guest_receives_staff_reply_via_polling(): void
    {
        $start = $this->withRecentGuestChatSession()->postJson(route('tenant.chat.start', [
            'tenant' => $this->tenant->slug,
            'branch' => $this->branch->slug,
        ]), [
            'guest_name' => 'Maria',
        ])->assertOk();

        $uuid = $start->json('conversation.uuid');
        $guestKey = $start->json('guest_key');

        $customerMsg = $this->postJson(route('tenant.chat.send', [
            'tenant' => $this->tenant->slug,
            'uuid' => $uuid,
        ]), ['body' => 'Preciso de ajuda'], [
            'X-Chat-Guest-Key' => $guestKey,
        ])->assertOk()->json('message');

        $this->actingAs($this->admin)
            ->postJson(route('tenant.admin.chat.send', [
                'tenant' => $this->tenant->slug,
                'uuid' => $uuid,
            ]), ['body' => 'Já vou te atender!'])
            ->assertOk();

        $this->getJson(
            route('tenant.chat.messages', [
                'tenant' => $this->tenant->slug,
                'uuid' => $uuid,
            ]).'?after_id='.$customerMsg['id'],
            ['X-Chat-Guest-Key' => $guestKey],
        )
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Já vou te atender!');
    }

    public function test_customer_unread_persists_until_mark_read(): void
    {
        $start = $this->withRecentGuestChatSession()->postJson(route('tenant.chat.start', [
            'tenant' => $this->tenant->slug,
            'branch' => $this->branch->slug,
        ]), ['guest_name' => 'Maria'])->assertOk();

        $uuid = $start->json('conversation.uuid');
        $guestKey = $start->json('guest_key');

        $this->actingAs($this->admin)
            ->postJson(route('tenant.admin.chat.send', [
                'tenant' => $this->tenant->slug,
                'uuid' => $uuid,
            ]), ['body' => 'Olá, Maria!'])
            ->assertOk();

        $this->getJson(
            route('tenant.chat.messages', ['tenant' => $this->tenant->slug, 'uuid' => $uuid]),
            ['X-Chat-Guest-Key' => $guestKey],
        )
            ->assertOk()
            ->assertJsonPath('conversation.customer_unread_count', 1);

        $this->getJson(
            route('tenant.chat.messages', ['tenant' => $this->tenant->slug, 'uuid' => $uuid]).'?mark_read=1',
            ['X-Chat-Guest-Key' => $guestKey],
        )
            ->assertOk()
            ->assertJsonPath('conversation.customer_unread_count', 0);
    }

    public function test_admin_unread_summary_endpoint(): void
    {
        $conversation = ChatConversation::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'guest_name' => 'Ana',
            'guest_key' => 'guest-unread',
            'status' => 'open',
            'staff_unread_count' => 3,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('tenant.admin.chat.unread', ['tenant' => $this->tenant->slug]))
            ->assertOk()
            ->assertJsonPath('total', 3);

        $conversation->update(['staff_unread_count' => 0]);

        $this->actingAs($this->admin)
            ->getJson(route('tenant.admin.chat.unread', ['tenant' => $this->tenant->slug]))
            ->assertJsonPath('total', 0);
    }

    public function test_guest_can_resume_conversation_with_stored_key(): void
    {
        $conversation = ChatConversation::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'guest_name' => 'Pedro',
            'guest_key' => 'persistent-guest-key',
            'status' => 'open',
        ]);

        $this->withRecentGuestChatSession()->postJson(route('tenant.chat.start', [
            'tenant' => $this->tenant->slug,
            'branch' => $this->branch->slug,
        ]), [
            'guest_name' => 'Pedro',
            'guest_key' => 'persistent-guest-key',
            'conversation_uuid' => $conversation->uuid,
        ], [
            'X-Chat-Guest-Key' => 'persistent-guest-key',
        ])
            ->assertOk()
            ->assertJsonPath('conversation.uuid', $conversation->uuid);
    }
}
