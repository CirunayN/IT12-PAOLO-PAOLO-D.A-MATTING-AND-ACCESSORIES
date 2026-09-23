@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-truck-ramp-box text-red-500"></i>
                Receive Stock Shipment
            </h1>
        </div>
        <a href="{{ route('stock-in.index') }}" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-300">
            &larr; Back
        </a>
    </div>

    <form method="POST" action="{{ route('stock-in.store') }}" class="glass-card rounded-2xl p-6 border shadow-lg space-y-5">
        @csrf

        <!-- Product Selector -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                Select Product <span class="text-rose-500">*</span>
            </label>
            <select name="Product_ID" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">
                <option value="">-- Choose Product --</option>
                @foreach($products as $prod)
                <option value="{{ $prod->ID }}" {{ old('Product_ID') == $prod->ID ? 'selected' : '' }}>
                    {{ $prod->Name }} (Current Stock: {{ $prod->stock_quantity }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Quantity -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Quantity Received <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Quantity" value="{{ old('Quantity', 1) }}" min="1" step="1" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-slate-900 dark:text-white">
            </div>

            <!-- Cost Price -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Cost Price (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Cost_Price" value="{{ old('Cost_Price', 0) }}" min="0" step="0.01" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-slate-900 dark:text-white">
            </div>

            <!-- Retail Selling Price -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Retail Price (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Retail_Price" value="{{ old('Retail_Price', 0) }}" min="0" step="0.01" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-slate-900 dark:text-white">
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('stock-in.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 font-bold text-sm">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25">
                Record Stock-In
            </button>
        </div>
    </form>
</div>
@endsection