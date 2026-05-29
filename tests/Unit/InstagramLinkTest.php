<?php

namespace Tests\Unit;

use App\Support\InstagramLink;
use PHPUnit\Framework\TestCase;

class InstagramLinkTest extends TestCase
{
    public function test_prefers_branch_instagram_over_tenant(): void
    {
        $resolved = InstagramLink::resolve('@filialcentro', 'restaurante');

        $this->assertSame('filialcentro', $resolved['handle']);
        $this->assertSame('https://www.instagram.com/filialcentro', $resolved['url']);
    }

    public function test_falls_back_to_tenant_when_branch_empty(): void
    {
        $resolved = InstagramLink::resolve(null, '@acmelanches');

        $this->assertSame('acmelanches', $resolved['handle']);
        $this->assertSame('@acmelanches', $resolved['label']);
    }

    public function test_returns_null_when_both_empty(): void
    {
        $this->assertNull(InstagramLink::resolve('', null));
    }
}
