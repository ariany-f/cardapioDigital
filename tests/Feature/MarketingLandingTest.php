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

    public function test_landing_page_renders_all_active_plans_with_cheapest_featured(): void
    {
        $this->seed(PlatformSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Landing')
                ->has('plans', 3)
                ->where('featuredPlan.slug', 'basico')
                ->where('featuredPlan.is_featured', true)
                ->where('plans.0.slug', 'basico')
                ->where('plans.0.is_featured', true)
                ->where('plans.0.price', 29.9)
                ->where('plans.1.slug', 'pro')
                ->where('plans.2.slug', 'enterprise'));

        Plan::where('slug', 'basico')->update(['price_monthly' => 499.90]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('featuredPlan.slug', 'pro')
                ->where('plans.0.slug', 'pro')
                ->where('plans.0.is_featured', true));
    }

    public function test_inactive_plans_are_not_shown_on_landing(): void
    {
        $this->seed(PlatformSeeder::class);

        Plan::where('slug', 'enterprise')->update(['is_active' => false]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page->has('plans', 2));
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
