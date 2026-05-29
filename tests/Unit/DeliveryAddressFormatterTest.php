<?php

namespace Tests\Unit;

use App\Support\DeliveryAddressFormatter;
use PHPUnit\Framework\TestCase;

class DeliveryAddressFormatterTest extends TestCase
{
    public function test_formats_standard_address(): void
    {
        $formatted = DeliveryAddressFormatter::format([
            'street' => 'Rua das Flores',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'postal_code' => '01000-000',
        ]);

        $this->assertStringContainsString('Rua das Flores', $formatted);
        $this->assertStringContainsString('Centro', $formatted);
        $this->assertStringContainsString('CEP', $formatted);
    }

    public function test_formats_legacy_keys(): void
    {
        $formatted = DeliveryAddressFormatter::format([
            'logradouro' => 'Av. Paulista',
            'numero' => '500',
            'bairro' => 'Bela Vista',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'cep' => '01310-100',
        ]);

        $this->assertStringContainsString('Av. Paulista', $formatted);
        $this->assertStringContainsString('Bela Vista', $formatted);
    }

    public function test_uses_preset_formatted_field(): void
    {
        $this->assertSame(
            'Endereço completo em uma linha',
            DeliveryAddressFormatter::format(['formatted' => 'Endereço completo em uma linha']),
        );
    }
}
