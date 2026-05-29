<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantWebhookToken extends Model
{
    protected $fillable = ['tenant_id', 'name', 'token', 'type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
