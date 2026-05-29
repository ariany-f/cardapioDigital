<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use CanResetPassword, Notifiable;
    protected $fillable = ['name', 'email', 'phone', 'password', 'cpf'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
