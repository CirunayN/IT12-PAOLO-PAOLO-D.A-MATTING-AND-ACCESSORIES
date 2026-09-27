<?php

namespace App\Models;

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
        'email_verified_at',
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
        return in_array(
            strtolower($this->role ?? ''),
            ['admin', 'owner'],
            true
        );
    }

    public function isCashier(): bool
    {
        return in_array(
            strtolower($this->role ?? ''),
            [
                'employee',
                'cashier',
                'admin',
                'owner',
                'packer',
                'accessory installer',
                'accessory_installer',
                'production worker',
                'production_worker',
            ],
            true
        );
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'User_ID', 'id');
    }
}
