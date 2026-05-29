<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiningTable extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'branch_id', 'name', 'qr_token'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
