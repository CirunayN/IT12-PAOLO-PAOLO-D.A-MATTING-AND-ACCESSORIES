@php
    [$inventorySort, $inventoryDirection] = \App\Support\InventoryFilters::order(request());
    $filterInputClass = 'w-full min-w-0 px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-red-500';
    $filterDateInputClass = str_replace(['px-3', 'text-sm'], ['px-2', 'text-xs'], $filterInputClass);
    $filterLabelClass = 'block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5';
@endphp
@if($errors->any())
    <ul role="alert" class="mb-4 space-y-1 text-sm text-rose-600 dark:text-rose-400">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
@endif
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <div>
        <label for="inventorySearch" class="{{ $filterLabelClass }}">Name</label>
        <input id="inventorySearch" type="search" name="search" value="{{ request('search') }}" placeholder="Product name or description" class="{{ $filterInputClass }}">
    </div>
    <div>
        <span class="{{ $filterLabelClass }}">Category</span>
        @include('partials.category-search-filter', ['categories' => $categories])
    </div>
    <div>
        <label for="inventoryStockLevel" class="{{ $filterLabelClass }}">Stock Status</label>
        <select id="inventoryStockLevel" name="stock_level" class="{{ $filterInputClass }}">
            <option value="">All Stock Levels</option>
            <option value="available" @selected(request('stock_level') === 'available')>Above item threshold</option>
            <option value="attention" @selected(request('stock_level') === 'attention')>Low / Out of Stock</option>
            <option value="low" @selected(request('stock_level') === 'low')>Low Stock (item threshold)</option>
            <option value="out" @selected(request('stock_level') === 'out')>Out of Stock (0)</option>
        </select>
    </div>
    <div>
        <label for="inventorySort" class="{{ $filterLabelClass }}">Sort By</label>
        <select id="inventorySort" name="sort" class="{{ $filterInputClass }}">
            @foreach(['date' => 'Date', 'price' => 'Price', 'stock' => 'Stock', 'name' => 'Name'] as $value => $label)
                <option value="{{ $value }}" @selected($inventorySort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <fieldset>
        <legend class="{{ $filterLabelClass }}">{{ $dateLabel ?? 'Date Added' }}</legend>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="inventoryStartDate" class="block text-[11px] text-slate-500 mb-1">From</label>
                <input id="inventoryStartDate" type="date" name="start_date" value="{{ request('start_date') }}" class="{{ $filterDateInputClass }}">
            </div>
            <div>
                <label for="inventoryEndDate" class="block text-[11px] text-slate-500 mb-1">To</label>
                <input id="inventoryEndDate" type="date" name="end_date" value="{{ request('end_date') }}" min="{{ request('start_date') }}" class="{{ $filterDateInputClass }}">
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend class="{{ $filterLabelClass }}">Retail Price (₱)</legend>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="inventoryMinPrice" class="block text-[11px] text-slate-500 mb-1">Minimum</label>
                <input id="inventoryMinPrice" type="number" name="min_price" min="0" step="0.01" value="{{ request('min_price') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="inventoryMaxPrice" class="block text-[11px] text-slate-500 mb-1">Maximum</label>
                <input id="inventoryMaxPrice" type="number" name="max_price" min="{{ request('min_price', 0) ?: 0 }}" step="0.01" value="{{ request('max_price') }}" class="{{ $filterInputClass }}">
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend class="{{ $filterLabelClass }}">Stock Count</legend>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="inventoryMinStock" class="block text-[11px] text-slate-500 mb-1">Minimum</label>
                <input id="inventoryMinStock" type="number" name="min_stock" min="0" step="0.01" value="{{ request('min_stock') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="inventoryMaxStock" class="block text-[11px] text-slate-500 mb-1">Maximum</label>
                <input id="inventoryMaxStock" type="number" name="max_stock" min="{{ request('min_stock', 0) ?: 0 }}" step="0.01" value="{{ request('max_stock') }}" class="{{ $filterInputClass }}">
            </div>
        </div>
    </fieldset>
    <div class="space-y-3">
        <fieldset>
            <legend class="{{ $filterLabelClass }}">Order</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach(['asc' => 'Ascending', 'desc' => 'Descending'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="direction" value="{{ $value }}" @checked($inventoryDirection === $value) class="sr-only peer">
                        <span class="block rounded-xl border border-slate-300 dark:border-slate-700 px-2 py-2.5 text-center text-xs font-bold text-slate-600 dark:text-slate-300 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 peer-focus-visible:ring-2 peer-focus-visible:ring-red-400">
                            <i class="fas {{ $value === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} mr-1" aria-hidden="true"></i>{{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="flex items-center gap-2">
            <button type="submit" class="flex-1 py-2 rounded-xl bg-slate-800 dark:bg-dark-700 hover:bg-slate-700 text-white font-bold text-xs">Apply Filters</button>
            <a href="{{ $resetUrl }}" class="px-3 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 font-bold text-xs">Reset</a>
        </div>
    </div>
</div>
