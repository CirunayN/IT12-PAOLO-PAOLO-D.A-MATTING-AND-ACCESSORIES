<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRecoveryCode extends Model
{
    protected $fillable = ['user_id', 'code_hash', 'attempts', 'locked_until', 'verified_token_hash', 'verified_until'];

    protected $hidden = ['code_hash', 'verified_token_hash'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'locked_until' => 'datetime', 'verified_until' => 'datetime'];
    }
}
