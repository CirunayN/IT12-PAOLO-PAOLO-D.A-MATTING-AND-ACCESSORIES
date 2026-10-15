<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'Low_Stock_Threshold',
    ];

    protected $casts = [
        'Images' => 'array',
        'Low_Stock_Threshold' => 'integer',
    ];

    protected $attributes = ['Low_Stock_Threshold' => 5];

    public function getLowStockThresholdAttribute(): int
    {
        return (int) ($this->attributes['Low_Stock_Threshold'] ?? 5);
    }

    public static function inventoryBatchesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return StockIn::query()
            ->whereColumn('Product_ID', 'tbl_product.ID')
            ->where('Remaining_Quantity', '>', 0)
            ->where('Condition', 'Good')
            ->where(fn ($query) => $query->where('Has_Expiration', false)
                ->orWhereNull('Expiration_Date')->orWhereDate('Expiration_Date', '>=', today()->toDateString()));
    }

    public static function inventoryStockQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::inventoryBatchesQuery()->selectRaw('CAST(COALESCE(SUM(Remaining_Quantity), 0) AS DECIMAL(18, 2))');
    }

    public static function inventoryRetailPriceQuery(): \Illuminate\Database\Query\Builder
    {
        $sellable = static::inventoryBatchesQuery()->select('Retail_Price')->orderByDesc('ID')->limit(1);
        $fallback = StockIn::query()->whereColumn('Product_ID', 'tbl_product.ID')
            ->select('Retail_Price')->orderByDesc('ID')->limit(1);

        return DB::query()->selectRaw('CAST(COALESCE(('.$sellable->toSql().'), ('.$fallback->toSql().'), 0) AS DECIMAL(18, 2))',
            [...$sellable->getBindings(), ...$fallback->getBindings()]);
    }

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
        if ($this->relationLoaded('stockIns')) {
            return (float) $this->loadedSellableBatches()->sum('Remaining_Quantity');
        }
        return (float) $this->sellableStockIns()->sum('Remaining_Quantity');
    }

    public function getRetailPriceAttribute(): float
    {
        if ($this->relationLoaded('stockIns')) {
            return (float) ($this->latestLoadedBatch()?->Retail_Price ?? 0);
        }
        $latest = $this->sellableStockIns()->orderBy('ID', 'desc')->first()
            ?? $this->stockIns()->orderBy('ID', 'desc')->first();
        return $latest ? (float) $latest->Retail_Price : 0.00;
    }

    public function getCostPriceAttribute(): float
    {
        if ($this->relationLoaded('stockIns')) {
            return (float) ($this->latestLoadedBatch()?->Cost_Price ?? 0);
        }
        $latest = $this->sellableStockIns()->orderBy('ID', 'desc')->first()
            ?? $this->stockIns()->orderBy('ID', 'desc')->first();
        return $latest ? (float) $latest->Cost_Price : 0.00;
    }

    private function loadedSellableBatches(): \Illuminate\Support\Collection
    {
        $today = today()->toDateString();
        return $this->stockIns->filter(fn (StockIn $batch) => $batch->Remaining_Quantity > 0
            && $batch->Condition === 'Good'
            && (! $batch->Has_Expiration || ! $batch->Expiration_Date || $batch->Expiration_Date->toDateString() >= $today));
    }

    private function latestLoadedBatch(): ?StockIn
    {
        return $this->loadedSellableBatches()->sortByDesc('ID')->first()
            ?? $this->stockIns->sortByDesc('ID')->first();
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
