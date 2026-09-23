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

    public function getStockQuantityAttribute(): float
    {
        $totalIn = (float) $this->stockIns()->sum('Quantity');
        $totalSold = (float) $this->soldItems()->sum('Quantity');
        return max(0, $totalIn - $totalSold);
    }

    public function getRetailPriceAttribute(): float
    {
        $latest = $this->stockIns()->orderBy('ID', 'desc')->first();
        return $latest ? (float) $latest->Retail_Price : 0.00;
    }

    public function getCostPriceAttribute(): float
    {
        $latest = $this->stockIns()->orderBy('ID', 'desc')->first();
        return $latest ? (float) $latest->Cost_Price : 0.00;
    }

    /**
     * Primary Cover Image URL
     */
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

    /**
     * Array of full URLs for all gallery photos
     */
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