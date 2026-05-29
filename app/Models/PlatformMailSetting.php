<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformMailSetting extends Model
{
    protected $fillable = [
        'enabled',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled' => false,
            'port' => 587,
        ]);
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function toPublicArray(): array
    {
        return [
            'enabled' => (bool) $this->enabled,
            'host' => $this->host ?? '',
            'port' => $this->port ?? 587,
            'username' => $this->username ?? '',
            'encryption' => $this->encryption ?? '',
            'from_address' => $this->from_address ?? '',
            'from_name' => $this->from_name ?? '',
            'password_configured' => $this->hasPassword(),
        ];
    }
}
