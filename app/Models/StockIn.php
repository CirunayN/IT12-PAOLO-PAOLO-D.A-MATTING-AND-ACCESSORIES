<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    use HasFactory;

    protected $table = 'tbl_stock_in';
    protected $primaryKey = 'ID';
    protected $fillable = ['Product_ID', 'Quantity', 'Cost_Price', 'Retail_Price'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'Product_ID', 'ID');
    }
}
