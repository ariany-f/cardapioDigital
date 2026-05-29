<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformStorageSetting extends Model
{
    protected $fillable = [
        'enabled',
        'key',
        'secret',
        'region',
        'bucket',
        'url',
        'endpoint',
        'use_path_style_endpoint',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'secret' => 'encrypted',
            'use_path_style_endpoint' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled' => false,
            'region' => 'us-east-1',
        ]);
    }

    public function hasSecret(): bool
    {
        return filled($this->secret);
    }

    public function isConfigured(): bool
    {
        return $this->enabled
            && filled($this->key)
            && $this->hasSecret()
            && filled($this->bucket)
            && filled($this->region);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'enabled' => (bool) $this->enabled,
            'key' => $this->key ?? '',
            'region' => $this->region ?? 'us-east-1',
            'bucket' => $this->bucket ?? '',
            'url' => $this->url ?? '',
            'endpoint' => $this->endpoint ?? '',
            'use_path_style_endpoint' => (bool) $this->use_path_style_endpoint,
            'secret_configured' => $this->hasSecret(),
        ];
    }
}
