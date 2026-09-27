<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingEmployeeRegistration extends Model
{
    protected $table = 'pending_employee_registrations';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password_hash',
        'role',
        'code_hash',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
