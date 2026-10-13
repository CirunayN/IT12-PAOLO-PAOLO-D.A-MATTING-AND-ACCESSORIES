<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryFilters
{
    public static function validate(Request $request): array
    {
        $request->validate([
            'sort' => ['nullable', Rule::in(['date', 'price', 'stock', 'name', 'newest', 'oldest', 'name_asc', 'name_desc', 'stock_asc', 'stock_desc', 'price_asc', 'price_desc'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'stock_level' => ['nullable', Rule::in(['available', 'attention', 'low', 'out'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('start_date'), 'after_or_equal:start_date')],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', Rule::when($request->filled('min_price'), 'gte:min_price')],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'max_stock' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', Rule::when($request->filled('min_stock'), 'gte:min_stock')],
        ]);

        return self::order($request);
    }

    public static function order(Request $request): array
    {
        $sort = $request->input('sort') ?: 'stock';
        $legacy = match ($sort) {
            'newest' => ['date', 'desc'],
            'oldest' => ['date', 'asc'],
            default => explode('_', $sort) + [1 => 'asc'],
        };

        return [$legacy[0], $request->input('direction') ?: $legacy[1]];
    }
}
