<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    use HasFactory;

    protected $table = 'tbl_stock_in';
    protected $primaryKey = 'ID';

    protected $fillable = [
        'Product_ID',
        'User_ID',
        'Quantity',
        'Remaining_Quantity',
        'Cost_Price',
        'Retail_Price',
        'Has_Expiration',
        'Expiration_Date',
        'Condition',
    ];

    protected $casts = [
        'Quantity' => 'decimal:2',
        'Remaining_Quantity' => 'decimal:2',
        'Cost_Price' => 'decimal:2',
        'Retail_Price' => 'decimal:2',
        'Has_Expiration' => 'boolean',
        'Expiration_Date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockIn $stockIn) {
            if ($stockIn->Remaining_Quantity === null) {
                $stockIn->Remaining_Quantity = $stockIn->Quantity;
            }

            if (!$stockIn->Has_Expiration) {
                $stockIn->Expiration_Date = null;
            }

            if (!$stockIn->Condition) {
                $stockIn->Condition = 'Good';
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'Product_ID', 'ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID', 'id');
    }
}
