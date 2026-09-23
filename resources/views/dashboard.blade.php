@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-chart-pie text-red-500"></i>
                Executive Dashboard
            </h1>
        </div>
            <a href="{{ route('stock-in.create') }}" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 font-bold text-sm border border-slate-300 dark:border-slate-700 flex items-center gap-2 transition-all">
                <i class="fas fa-plus text-red-500"></i>
                <span>Receive Stock</span>
            </a>
        </div>
    </div>

    <!-- 4 Key Metric Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Today Sales Card -->
        <div class="glass-card rounded-2xl p-5 border shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Today's Sales</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-lg">
                    <i class="fas fa-peso-sign"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-display text-emerald-600 dark:text-emerald-400">
                ₱{{ number_format($todaySalesTotal, 2) }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <i class="fas fa-receipt text-red-500"></i>
                <span><strong>{{ $todaySalesCount }}</strong> sales transactions today</span>
            </div>
        </div>

        <!-- Total All-Time Revenue Card -->
        <div class="glass-card rounded-2xl p-5 border shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Sales Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-lg">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">
                ₱{{ number_format($totalSalesAllTime, 2) }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <i class="fas fa-file-invoice text-blue-500"></i>
                <span><strong>{{ $totalTransactions }}</strong> total sales records</span>
            </div>
        </div>

        <!-- Inventory Units & Value -->
        <div class="glass-card rounded-2xl p-5 border shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Inventory Stock</span>
                <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center text-lg">
                    <i class="fas fa-cubes-stacked"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">
                {{ number_format($totalStockUnits, 0) }} <span class="text-sm font-normal text-slate-400">Units</span>
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-1.5">
                <i class="fas fa-tag text-red-500"></i>
                <span>Valued at ₱{{ number_format($inventoryValue, 2) }}</span>
            </div>
        </div>

        <!-- Catalog & Stock Alerts -->
        <div class="glass-card rounded-2xl p-5 border shadow-sm transition-all hover:shadow-md">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Products</span>
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center text-lg">
                    <i class="fas fa-boxes-packing"></i>
                </div>
            </div>
            <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">
                {{ $totalProductsCount }} <span class="text-sm font-normal text-slate-400">Products</span>
            </div>
            <div class="text-xs mt-2 flex items-center gap-3">
                <span class="text-amber-500 font-bold flex items-center gap-1">
                    <i class="fas fa-triangle-exclamation"></i> {{ $lowStockCount }} Low
                </span>
                <span class="text-rose-500 font-bold flex items-center gap-1">
                    <i class="fas fa-circle-xmark"></i> {{ $outOfStockCount }} Empty
                </span>
            </div>
        </div>
    </div>

    <!-- Two Columns: Recent Sales & Stock-In History -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Sales -->
        <div class="glass-card rounded-2xl p-5 sm:p-6 border shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center">
                        <i class="fas fa-receipt text-sm"></i>
                    </div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Recent Sales</h3>
                </div>
                <a href="{{ route('pos.index') }}" class="text-xs font-bold text-red-500 hover:text-red-400">New Sale &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="pb-2">ID</th>
                            <th class="pb-2">Date / Time</th>
                            <th class="pb-2">Cashier</th>
                            <th class="pb-2">Method</th>
                            <th class="pb-2 text-right">Total</th>
                            <th class="pb-2 text-center">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($recentSales as $sale)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                            <td class="py-3 font-mono font-bold text-red-500">#{{ $sale->ID }}</td>
                            <td class="py-3 text-slate-600 dark:text-slate-300">
                                {{ $sale->Date ? $sale->Date->format('M d, Y h:i A') : $sale->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3 font-medium text-slate-800 dark:text-slate-200">
                                {{ $sale->user->name ?? 'Staff' }}
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $sale->paymentMethod->Name ?? 'Cash' }}
                                </span>
                            </td>
                            <td class="py-3 text-right font-black text-emerald-600 dark:text-emerald-400">
                                ₱{{ number_format($sale->Total, 2) }}
                            </td>
                            <td class="py-3 text-center">
                                <a href="{{ route('pos.receipt', $sale->ID) }}" target="_blank" class="text-slate-400 hover:text-red-500 transition-colors p-1" title="View Thermal Receipt">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No sales recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Stock-Ins -->
        <div class="glass-card rounded-2xl p-5 sm:p-6 border shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/15 text-blue-500 flex items-center justify-center">
                        <i class="fas fa-truck-ramp-box text-sm"></i>
                    </div>
                    <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Recent Stock Deliveries</h3>
                </div>
                <a href="{{ route('stock-in.index') }}" class="text-xs font-bold text-red-500 hover:text-red-400">View All &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="pb-2">Batch ID</th>
                            <th class="pb-2">Product Name</th>
                            <th class="pb-2 text-right">Quantity</th>
                            <th class="pb-2 text-right">Cost Price</th>
                            <th class="pb-2 text-right">Retail Price</th>
                            <th class="pb-2 text-center">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($recentStockIns as $stockIn)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                            <td class="py-3 font-mono font-bold text-blue-500">#SI-{{ $stockIn->ID }}</td>
                            <td class="py-3 font-medium text-slate-800 dark:text-slate-200">
                                {{ $stockIn->product->Name ?? 'Product' }}
                            </td>
                            <td class="py-3 text-right font-black text-red-500">
                                +{{ number_format($stockIn->Quantity, 0) }}
                            </td>
                            <td class="py-3 text-right text-slate-500 dark:text-slate-400">
                                ₱{{ number_format($stockIn->Cost_Price, 2) }}
                            </td>
                            <td class="py-3 text-right font-bold text-slate-900 dark:text-white">
                                ₱{{ number_format($stockIn->Retail_Price, 2) }}
                            </td>
                            <td class="py-3 text-center text-xs text-slate-400">
                                {{ $stockIn->created_at ? $stockIn->created_at->format('M d') : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">No stock-in records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Category Overview Cards -->
    <div class="glass-card rounded-2xl p-5 sm:p-6 border shadow-sm">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-200 dark:border-slate-800">
            <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-tags text-purple-500"></i>
                Product Categories
            </h3>
            <a href="{{ route('products.index') }}" class="text-xs font-bold text-red-500 hover:text-red-400">Browse Catalog &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            @foreach($categories as $cat)
            <a href="{{ route('products.index', ['category_id' => $cat->ID]) }}" class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-800/60 border border-slate-200 dark:border-slate-700/60 hover:border-red-500/50 transition-all text-left group">
                <div class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-red-500 transition-colors">
                    {{ $cat->Name }}
                </div>
                <div class="text-xs text-slate-400 mt-1">
                    {{ $cat->products_count }} Products
                </div>
            </a>
            @endforeach
        </div>
    </div>

</div>
@endsection