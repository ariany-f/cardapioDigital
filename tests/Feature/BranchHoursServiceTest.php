<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Services\BranchHoursService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchHoursServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_branch_is_open_at_nine_pm_when_closing_at_2359_in_tenant_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 21:00:00', 'America/Sao_Paulo'));

        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'opening_hours' => [
                'thu' => ['09:00', '23:59'],
            ],
        ]);

        $service = app(BranchHoursService::class);

        $this->assertTrue($service->isOpen($branch));
    }

    public function test_branch_is_closed_after_hours_in_tenant_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 23:59:00', 'America/Sao_Paulo'));

        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'opening_hours' => [
                'thu' => ['09:00', '22:00'],
            ],
        ]);

        $this->assertFalse(app(BranchHoursService::class)->isOpen($branch));
    }

    public function test_time_with_seconds_from_form_is_handled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 21:00:00', 'America/Sao_Paulo'));

        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'opening_hours' => [
                'thu' => ['09:00:00', '23:59:00'],
            ],
        ]);

        $this->assertTrue(app(BranchHoursService::class)->isOpen($branch));
    }

    public function test_manual_override_open_outside_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 21:00:00', 'America/Sao_Paulo'));

        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'opening_hours' => [
                'thu' => ['09:00', '18:00'],
            ],
            'orders_status_override' => 'open',
        ]);

        $service = app(BranchHoursService::class);

        $this->assertFalse($service->isOpenBySchedule($branch));
        $this->assertTrue($service->isOpen($branch));
    }

    public function test_manual_override_closed_during_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-28 12:00:00', 'America/Sao_Paulo'));

        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'opening_hours' => [
                'thu' => ['09:00', '23:59'],
            ],
            'orders_status_override' => 'closed',
        ]);

        $service = app(BranchHoursService::class);

        $this->assertTrue($service->isOpenBySchedule($branch));
        $this->assertFalse($service->isOpen($branch));
    }
}
