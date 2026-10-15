<?php

namespace App\Services;

use App\Models\StockIn;

class ExpiryAlerts
{
    public const DAYS = 30;

    public function summary(): array
    {
        return app(FilterCache::class)->remember('expiry-alerts', [], fn () => $this->currentSummary());
    }

    private function currentSummary(): array
    {
        $today = app(DeviceClock::class)->now()->startOfDay();
        $through = $today->copy()->addDays(self::DAYS);
        $query = StockIn::query()->with('product')
            ->where('Has_Expiration', true)
            ->where('Condition', 'Good')
            ->where('Remaining_Quantity', '>', 0)
            ->whereDate('Expiration_Date', '>=', $today->toDateString())
            ->whereDate('Expiration_Date', '<=', $through->toDateString())
            ->whereHas('product.status', fn ($query) => $query->where('Name', '!=', 'Archived'));

        return [
            'count' => (clone $query)->count(),
            'as_of' => $today->toDateString(),
            'through' => $through->toDateString(),
            'items' => $query->orderBy('Expiration_Date')->orderBy('ID')->limit(20)->get()
                ->map(fn (StockIn $batch) => [
                    'product' => $batch->product->Name,
                    'batch' => 'SI-'.$batch->ID,
                    'quantity' => (float) $batch->Remaining_Quantity,
                    'expires_on' => $batch->Expiration_Date->format('Y-m-d'),
                    'days_remaining' => (int) $today->diffInDays($today->copy()->setDateFrom($batch->Expiration_Date), false),
                    'url' => route('products.edit', $batch->Product_ID),
                ])->all(),
        ];
    }
}
