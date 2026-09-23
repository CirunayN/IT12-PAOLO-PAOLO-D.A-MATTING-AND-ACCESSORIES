@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <!-- Unified Inventory Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-boxes-stacked text-red-500"></i>
                Inventory
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('products.create') }}" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 font-bold text-sm border border-slate-300 dark:border-slate-700 flex items-center gap-2 transition-all">
                <i class="fas fa-plus"></i>
                <span>Add Product</span>
            </a>
            <a href="{{ route('stock-in.create') }}" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2 transition-all">
                <i class="fas fa-truck-ramp-box"></i>
                <span>Receive New Shipment</span>
            </a>
        </div>
    </div>

    <!-- Unified Inventory Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <a href="{{ route('products.index', ['tab' => 'active']) }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700">
            <i class="fas fa-box-check"></i>
            <span>Active Catalog</span>
        </a>

        <a href="{{ route('products.index', ['tab' => 'archived']) }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700">
            <i class="fas fa-box-archive"></i>
            <span>Archived / Disabled</span>
        </a>

        <a href="{{ route('stock-in.index') }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-red-600 text-white shadow-sm">
            <i class="fas fa-truck-ramp-box"></i>
            <span>Stock-In Receiving Logs</span>
        </a>

        <a href="{{ route('products.index', ['tab' => 'all']) }}" 
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700">
            <span>All Items</span>
        </a>
    </div>

    <!-- Filter -->
    <div class="glass-card rounded-2xl p-4 border shadow-sm">
        <form method="GET" action="{{ route('stock-in.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <select name="product_id" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="">Filter by Product</option>
                    @foreach($products as $prod)
                    <option value="{{ $prod->ID }}" {{ request('product_id') == $prod->ID ? 'selected' : '' }}>{{ $prod->Name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 dark:bg-dark-700 text-white font-bold text-sm">
                Filter
            </button>
            <a href="{{ route('stock-in.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-300 font-bold text-sm">
                Reset
            </a>
        </form>
    </div>

    <!-- Table -->
    <div class="glass-card rounded-2xl border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="p-4">Batch ID</th>
                        <th class="p-4">Date Received</th>
                        <th class="p-4">Product Name</th>
                        <th class="p-4 text-right">Quantity Received</th>
                        <th class="p-4 text-right">Cost Price</th>
                        <th class="p-4 text-right">Retail Price</th>
                        <th class="p-4 text-right">Total Batch Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($stockIns as $si)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                        <td class="p-4 font-mono font-bold text-red-500">#SI-{{ $si->ID }}</td>
                        <td class="p-4 text-slate-600 dark:text-slate-300">
                            {{ $si->created_at ? $si->created_at->format('M d, Y h:i A') : '-' }}
                        </td>
                        <td class="p-4 font-bold text-slate-900 dark:text-white">
                            {{ $si->product->Name ?? 'N/A' }}
                        </td>
                        <td class="p-4 text-right font-black text-emerald-600 dark:text-emerald-400 text-base">
                            +{{ number_format($si->Quantity, 0) }}
                        </td>
                        <td class="p-4 text-right text-slate-500 dark:text-slate-400">
                            ₱{{ number_format($si->Cost_Price, 2) }}
                        </td>
                        <td class="p-4 text-right font-bold text-slate-900 dark:text-white">
                            ₱{{ number_format($si->Retail_Price, 2) }}
                        </td>
                        <td class="p-4 text-right font-black font-display text-slate-900 dark:text-white">
                            ₱{{ number_format($si->Quantity * $si->Cost_Price, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">No stock-in records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stockIns->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $stockIns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection