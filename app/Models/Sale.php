<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $table = 'tbl_sale';
    protected $primaryKey = 'ID';
    protected $fillable = ['Date', 'Total', 'User_ID', 'Payment_Method_ID'];

    protected $casts = [
        'Date' => 'datetime',
        'Total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'Payment_Method_ID', 'ID');
    }

    public function soldItems()
    {
        return $this->hasMany(SoldItem::class, 'Sale_ID', 'ID');
    }
}
