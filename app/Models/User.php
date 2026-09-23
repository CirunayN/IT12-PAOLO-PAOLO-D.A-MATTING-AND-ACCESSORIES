<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array(strtolower($this->role ?? ''), ['admin', 'owner']);
    }

    public function isCashier(): bool
    {
        return in_array(strtolower($this->role ?? ''), [
            'cashier', 'admin', 'owner', 'packer', 'accessory installer', 'accessory_installer', 'production worker', 'production_worker'
        ]);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'User_ID', 'id');
    }
}
