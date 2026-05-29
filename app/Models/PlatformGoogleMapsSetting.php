<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformGoogleMapsSetting extends Model
{
    protected $fillable = [
        'enabled',
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'api_key' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled' => false,
        ]);
    }

    public function hasApiKey(): bool
    {
        return filled($this->api_key);
    }

    public function isConfigured(): bool
    {
        return $this->enabled && $this->hasApiKey();
    }

    /**
     * @return array{enabled: bool, api_key: ?string, api_key_configured: bool}
     */
    public function toAdminArray(): array
    {
        return [
            'enabled' => (bool) $this->enabled,
            'api_key_configured' => $this->hasApiKey(),
        ];
    }

    /**
     * Chave para o navegador (Maps JavaScript API). Restrinja por HTTP referrer no Google Cloud.
     *
     * @return array{enabled: bool, api_key: ?string}|null
     */
    public function toPublicArray(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'api_key' => $this->api_key,
        ];
    }
}
