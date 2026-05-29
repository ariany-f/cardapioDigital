<?php

namespace Tests\Feature;

use App\Mail\MarketingLeadMail;
use App\Models\MarketingLead;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformMarketingLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    public function test_contact_form_persists_lead(): void
    {
        Mail::fake();
        config(['marketing.contact_email' => 'leads@test.com']);

        $this->post('/contato', [
            'restaurant_name' => 'Burger House',
            'contact_name' => 'João Silva',
            'email' => 'joao@burger.test',
            'phone' => '11999998888',
            'city' => 'São Paulo',
            'message' => 'Quero testar o sistema.',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('marketing_leads', [
            'restaurant_name' => 'Burger House',
            'email' => 'joao@burger.test',
            'status' => MarketingLead::STATUS_PENDING,
        ]);

        Mail::assertQueued(MarketingLeadMail::class);
    }

    public function test_platform_user_can_list_leads(): void
    {
        MarketingLead::create([
            'restaurant_name' => 'Pizza Top',
            'contact_name' => 'Maria',
            'email' => 'maria@test.com',
            'status' => MarketingLead::STATUS_PENDING,
        ]);

        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->get(route('platform.marketing-leads.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/MarketingLeads/Index')
                ->has('leads.data', 1)
                ->where('leads.data.0.restaurant_name', 'Pizza Top'));
    }

    public function test_platform_user_can_update_lead_status(): void
    {
        $lead = MarketingLead::create([
            'restaurant_name' => 'Pizza Top',
            'contact_name' => 'Maria',
            'email' => 'maria@test.com',
            'status' => MarketingLead::STATUS_PENDING,
        ]);

        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->put(route('platform.marketing-leads.update', $lead), [
                'status' => MarketingLead::STATUS_CONTACTED,
                'internal_notes' => 'Ligou na segunda.',
            ])
            ->assertRedirect(route('platform.marketing-leads.show', $lead));

        $lead->refresh();

        $this->assertSame(MarketingLead::STATUS_CONTACTED, $lead->status);
        $this->assertSame('Ligou na segunda.', $lead->internal_notes);
        $this->assertNotNull($lead->contacted_at);
    }

    public function test_tenant_admin_cannot_access_leads(): void
    {
        $user = User::factory()->create(['is_platform_user' => false]);

        $this->actingAs($user)
            ->get(route('platform.marketing-leads.index'))
            ->assertForbidden();
    }
}
