<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldItem extends Model
{
    use HasFactory;

    protected $table = 'tbl_sold_item';
    protected $primaryKey = 'ID';
    protected $fillable = ['Product_ID', 'Quantity', 'Total', 'Sale_ID'];

    protected $casts = [
        'Quantity' => 'decimal:2',
        'Total' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'Product_ID', 'ID');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'Sale_ID', 'ID');
    }
}
