<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'tbl_category';

    protected $primaryKey = 'ID';

    protected $fillable = ['Name', 'Is_Archived'];

    protected $casts = ['Is_Archived' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('Is_Archived', false);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'Category_ID', 'ID');
    }
}
