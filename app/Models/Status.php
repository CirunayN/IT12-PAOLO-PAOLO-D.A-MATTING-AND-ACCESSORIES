<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $table = 'tbl_status';
    protected $primaryKey = 'ID';
    protected $fillable = ['Name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'Status_ID', 'ID');
    }
}
