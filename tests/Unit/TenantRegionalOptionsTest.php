<?php

namespace Tests\Unit;

use App\Support\TenantRegionalOptions;
use PHPUnit\Framework\TestCase;

class TenantRegionalOptionsTest extends TestCase
{
    public function test_includes_brl_and_sao_paulo(): void
    {
        $this->assertContains('BRL', TenantRegionalOptions::currencyCodes());
        $this->assertContains('America/Sao_Paulo', TenantRegionalOptions::timezoneIds());
    }

    public function test_currency_and_timezone_entries_have_labels(): void
    {
        $this->assertNotEmpty(TenantRegionalOptions::currencies()[0]['name']);
        $this->assertNotEmpty(TenantRegionalOptions::timezones()[0]['label']);
    }
}
