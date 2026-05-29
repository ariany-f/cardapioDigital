<?php

namespace Tests\Feature;

use App\Mail\MarketingLeadMail;
use App\Models\MarketingLead;
use App\Models\Plan;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketingLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_plan_price_from_database(): void
    {
        $this->seed(PlatformSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Landing')
                ->where('plan.slug', 'basico')
                ->where('plan.name', 'Básico')
                ->where('plan.price', 29.9));

        Plan::where('slug', 'basico')->update(['price_monthly' => 39.90]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page->where('plan.price', 39.9));
    }

    public function test_contact_form_sends_email(): void
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
        ])->assertRedirect('/')
            ->assertSessionHas('success');

        Mail::assertQueued(MarketingLeadMail::class, function (MarketingLeadMail $mail) {
            return $mail->hasTo('leads@test.com')
                && $mail->lead['restaurant_name'] === 'Burger House';
        });

        $this->assertDatabaseHas('marketing_leads', [
            'restaurant_name' => 'Burger House',
            'status' => MarketingLead::STATUS_PENDING,
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->post('/contato', [])
            ->assertSessionHasErrors(['restaurant_name', 'contact_name', 'email']);
    }
}
