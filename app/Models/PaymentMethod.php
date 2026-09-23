<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'tbl_payment_method';
    protected $primaryKey = 'ID';
    protected $fillable = ['Name'];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'Payment_Method_ID', 'ID');
    }
}
