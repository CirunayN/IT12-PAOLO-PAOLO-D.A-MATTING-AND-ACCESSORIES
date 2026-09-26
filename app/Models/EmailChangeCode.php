<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailChangeCode extends Model
{
    protected $table = 'email_change_codes';

    protected $fillable = [
        'user_id',
        'new_email',
        'code_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
