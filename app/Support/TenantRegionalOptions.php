<?php

namespace App\Support;

class TenantRegionalOptions
{
    /**
     * @return list<array{code: string, name: string}>
     */
    public static function currencies(): array
    {
        return [
            ['code' => 'BRL', 'name' => 'Real brasileiro (BRL)'],
            ['code' => 'USD', 'name' => 'Dólar americano (USD)'],
            ['code' => 'EUR', 'name' => 'Euro (EUR)'],
            ['code' => 'ARS', 'name' => 'Peso argentino (ARS)'],
            ['code' => 'PYG', 'name' => 'Guarani paraguaio (PYG)'],
            ['code' => 'UYU', 'name' => 'Peso uruguaio (UYU)'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function currencyCodes(): array
    {
        return array_column(self::currencies(), 'code');
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function timezones(): array
    {
        return [
            ['id' => 'America/Sao_Paulo', 'label' => 'Brasília (UTC−3) — SP, RJ, MG, PR, RS…'],
            ['id' => 'America/Manaus', 'label' => 'Manaus (UTC−4) — AM, RR, RO'],
            ['id' => 'America/Cuiaba', 'label' => 'Cuiabá (UTC−4) — MT, MS (parte)'],
            ['id' => 'America/Campo_Grande', 'label' => 'Campo Grande (UTC−4) — MS, MT (parte)'],
            ['id' => 'America/Belem', 'label' => 'Belém (UTC−3) — PA, AP'],
            ['id' => 'America/Fortaleza', 'label' => 'Fortaleza (UTC−3) — CE, MA, PI…'],
            ['id' => 'America/Recife', 'label' => 'Recife (UTC−3) — PE, AL, PB, RN…'],
            ['id' => 'America/Bahia', 'label' => 'Bahia (UTC−3) — Salvador'],
            ['id' => 'America/Maceio', 'label' => 'Maceió (UTC−3) — SE'],
            ['id' => 'America/Noronha', 'label' => 'Fernando de Noronha (UTC−2)'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function timezoneIds(): array
    {
        return array_column(self::timezones(), 'id');
    }
}
