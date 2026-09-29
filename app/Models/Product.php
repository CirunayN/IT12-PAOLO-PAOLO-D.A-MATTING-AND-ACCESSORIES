<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'tbl_product';
    protected $primaryKey = 'ID';

    protected $fillable = [
        'Name',
        'Description',
        'Category_ID',
        'Status_ID',
        'Image',
        'Images',
    ];

    protected $casts = [
        'Images' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'Category_ID', 'ID');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'Status_ID', 'ID');
    }

    public function stockIns()
    {
        return $this->hasMany(StockIn::class, 'Product_ID', 'ID');
    }

    public function soldItems()
    {
        return $this->hasMany(SoldItem::class, 'Product_ID', 'ID');
    }

    public function sellableStockIns()
    {
        return $this->stockIns()
            ->where('Remaining_Quantity', '>', 0)
            ->where('Condition', 'Good')
            ->where(function ($query) {
                $query
                    ->where('Has_Expiration', false)
                    ->orWhereNull('Expiration_Date')
                    ->orWhereDate('Expiration_Date', '>=', today()->toDateString());
            });
    }

    public function getStockQuantityAttribute(): float
    {
        return (float) $this->sellableStockIns()->sum('Remaining_Quantity');
    }

    public function getRetailPriceAttribute(): float
    {
        $latest = $this->sellableStockIns()->orderBy('ID', 'desc')->first();
        return $latest ? (float) $latest->Retail_Price : 0.00;
    }

    public function getCostPriceAttribute(): float
    {
        $latest = $this->sellableStockIns()->orderBy('ID', 'desc')->first();
        return $latest ? (float) $latest->Cost_Price : 0.00;
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->Image && file_exists(public_path($this->Image))) {
            return asset($this->Image);
        }

        if (is_array($this->Images) && count($this->Images) > 0) {
            $first = $this->Images[0];

            if (file_exists(public_path($first))) {
                return asset($first);
            }
        }

        return null;
    }

    public function getAllImageUrlsAttribute(): array
    {
        $urls = [];

        if (is_array($this->Images) && count($this->Images) > 0) {
            foreach ($this->Images as $img) {
                if (file_exists(public_path($img))) {
                    $urls[] = asset($img);
                }
            }
        } elseif ($this->Image && file_exists(public_path($this->Image))) {
            $urls[] = asset($this->Image);
        }

        return $urls;
    }

    public function getImagesCountAttribute(): int
    {
        return count($this->all_image_urls);
    }
}
