<?php

namespace App\Support;

class DeliveryAddressFormatter
{
    /**
     * @param  array<string, mixed>|string|null  $address
     */
    public static function format(mixed $address): ?string
    {
        if ($address === null || $address === '') {
            return null;
        }

        if (is_string($address)) {
            $trimmed = trim($address);

            return $trimmed !== '' ? $trimmed : null;
        }

        if (! is_array($address)) {
            return null;
        }

        foreach (['formatted', 'full', 'label'] as $key) {
            $preset = trim((string) ($address[$key] ?? ''));
            if ($preset !== '') {
                return $preset;
            }
        }

        $street = trim((string) ($address['street'] ?? $address['logradouro'] ?? ''));
        $number = trim((string) ($address['number'] ?? $address['numero'] ?? ''));
        $complement = trim((string) ($address['complement'] ?? $address['complemento'] ?? ''));
        $neighborhood = trim((string) ($address['neighborhood'] ?? $address['bairro'] ?? ''));
        $city = trim((string) ($address['city'] ?? $address['cidade'] ?? $address['localidade'] ?? ''));
        $state = trim((string) ($address['state'] ?? $address['uf'] ?? ''));
        $postalCode = trim((string) ($address['postal_code'] ?? $address['cep'] ?? ''));

        $line1 = trim(implode(', ', array_filter([$street, $number])));

        $parts = array_filter([
            $line1 !== '' ? $line1 : null,
            $complement !== '' ? $complement : null,
            $neighborhood !== '' ? $neighborhood : null,
            trim(implode(' / ', array_filter([$city, $state]))) ?: null,
            $postalCode !== '' ? 'CEP '.$postalCode : null,
        ]);

        return $parts ? implode(' — ', $parts) : null;
    }
}
