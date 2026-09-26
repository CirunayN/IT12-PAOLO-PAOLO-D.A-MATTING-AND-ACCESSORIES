@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Top Dashboard Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-chart-pie text-red-500"></i>
                Executive Dashboard
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Real-time overview of daily sales, inventory levels, catalog metrics, and recent activities.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.index') }}" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2 transition-all">
                <i class="fas fa-cash-register"></i>
                <span>Open POS</span>
            </a>
            <a href="{{ route('stock-in.create') }}" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 font-bold text-sm border border-slate-300 dark:border-slate-700 flex items-center gap-2 transition-all">
                <i class="fas fa-truck-ramp-box text-red-500"></i>
                <span>Receive Stock</span>
            </a>
        </div>
    </div>

    <!-- 5-Column Stats Grid with Featured Main "Today's Sales" Hero Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 items-stretch">

        <!-- MAIN FEATURED HERO CARD: Today's Sales (Spans 2 columns, larger typography & live badge) -->
        <div class="md:col-span-2 lg:col-span-2 glass-card rounded-3xl p-6 sm:p-7 border-2 border-emerald-500/30 dark:border-emerald-500/20 bg-gradient-to-br from-emerald-500/10 via-slate-50 to-slate-100 dark:from-emerald-950/40 dark:via-dark-900 dark:to-dark-850 shadow-lg shadow-emerald-500/5 relative overflow-hidden flex flex-col justify-between">
            <!-- Background Glow Accent -->
            <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Today's Sales
                    </span>
                    <a href="{{ route('pos.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
                        <span>Terminal</span>
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="text-3xl sm:text-4xl lg:text-5xl font-black font-display text-emerald-600 dark:text-emerald-400 tracking-tight">
                    ₱{{ number_format($todaySalesTotal, 2) }}
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-emerald-500/20 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600 dark:text-slate-300">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-500 flex items-center justify-center text-xs">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span><strong>{{ $todaySalesCount }}</strong> completed {{ Str::plural('transaction', $todaySalesCount) }} today</span>
                </div>
                <span class="text-slate-400 font-semibold">{{ date('F d, Y') }}</span>
            </div>
        </div>

        <!-- STAT CARD 2: Total Sales Revenue -->
        <div class="glass-card rounded-3xl p-5 sm:p-6 border shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Sales</span>
                    <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-base">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">
                    ₱{{ number_format($totalSalesAllTime, 2) }}
                </div>
            </div>
            <div class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i class="fas fa-file-invoice text-blue-500"></i>
                <span><strong>{{ $totalTransactions }}</strong> total sales records</span>
            </div>
        </div>

        <!-- STAT CARD 3: Inventory Units & Value -->
        <div class="glass-card rounded-3xl p-5 sm:p-6 border shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Inventory Stock</span>
                    <div class="w-10 h-10 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-base">
                        <i class="fas fa-cubes-stacked"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">
                    {{ number_format($totalStockUnits, 0) }} <span class="text-sm font-normal text-slate-400">Units</span>
                </div>
            </div>
            <div class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i class="fas fa-tag text-red-500"></i>
                <span class="truncate">Valued at ₱{{ number_format($inventoryValue, 2) }}</span>
            </div>
        </div>

        <!-- STAT CARD 4: Products & Alerts -->
        <div class="glass-card rounded-3xl p-5 sm:p-6 border shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Catalog Items</span>
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/10 text-purple-500 flex items-center justify-center text-base">
                        <i class="fas fa-boxes-packing"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white">
                    {{ $totalProductsCount }} <span class="text-sm font-normal text-slate-400">Products</span>
                </div>
            </div>
            <div class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-800 text-xs flex items-center gap-3">
                <span class="text-amber-500 font-bold flex items-center gap-1">
                    <i class="fas fa-triangle-exclamation text-[10px]"></i> {{ $lowStockCount }} Low
                </span>
                <span class="text-rose-500 font-bold flex items-center gap-1">
                    <i class="fas fa-circle-xmark text-[10px]"></i> {{ $outOfStockCount }} Empty
                </span>
            </div>
        </div>

    </div>

    <!-- Two-Column Grid: Recent Sales & Stock Deliveries with Perfect Alignment -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">

        <!-- Recent Sales (5 cols) -->
        <div class="lg:col-span-5 glass-card rounded-3xl p-5 sm:p-6 border shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-500/15 text-blue-500 flex items-center justify-center">
                            <i class="fas fa-receipt text-xs"></i>
                        </div>
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">Recent Sales</h3>
                    </div>
                    <a href="{{ route('pos.index') }}" class="text-xs font-bold text-blue-500 hover:text-blue-400 transition-colors">
                        New Sale &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-3 py-2.5 font-bold whitespace-nowrap">ID</th>
                                <th class="px-3 py-2.5 font-bold whitespace-nowrap">Date / Time</th>
                                <th class="px-3 py-2.5 font-bold whitespace-nowrap">Cashier</th>
                                <th class="px-3 py-2.5 font-bold text-center whitespace-nowrap">Method</th>
                                <th class="px-3 py-2.5 font-bold text-right whitespace-nowrap">Total</th>
                                <th class="px-2 py-2.5 font-bold text-center whitespace-nowrap">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($recentSales as $sale)
                            <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                                <td class="px-3 py-3 font-mono font-bold text-blue-500 whitespace-nowrap">
                                    #{{ $sale->ID }}
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                    {{ $sale->Date ? $sale->Date->format('M d, Y h:i A') : $sale->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">{{ $sale->user->name ?? 'Staff' }}</div>
                                    <span class="inline-block text-[10px] font-semibold text-slate-400">{{ $sale->user->role ?? 'Staff' }}</span>
                                </td>
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ $sale->paymentMethod->Name ?? 'Cash' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right font-black text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    ₱{{ number_format($sale->Total, 2) }}
                                </td>
                                <td class="px-2 py-3 text-center whitespace-nowrap">
                                    <a href="{{ route('pos.receipt', $sale->ID) }}" target="_blank" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-dark-800 hover:bg-red-500 hover:text-white text-slate-400 inline-flex items-center justify-center transition-colors" title="View Thermal Receipt">
                                        <i class="fas fa-print text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-slate-400 text-xs">No sales recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Stock Deliveries (7 cols) -->
        <div class="lg:col-span-7 glass-card rounded-3xl p-5 sm:p-6 border shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-500/15 text-blue-500 flex items-center justify-center">
                            <i class="fas fa-truck-ramp-box text-xs"></i>
                        </div>
                        <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">Recent Stock Deliveries</h3>
                    </div>
                    <a href="{{ route('stock-in.index') }}" class="text-xs font-bold text-blue-500 hover:text-blue-400 transition-colors">
                        View All &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-3 py-2.5 font-bold whitespace-nowrap">Batch ID</th>
                                <th class="px-3 py-2.5 font-bold min-w-[170px]">Product Name</th>
                                <th class="px-3 py-2.5 font-bold whitespace-nowrap">Received By</th>
                                <th class="px-3 py-2.5 font-bold text-right whitespace-nowrap">Qty</th>
                                <th class="px-3 py-2.5 font-bold text-right whitespace-nowrap">Unit Cost</th>
                                <th class="px-3 py-2.5 font-bold text-right whitespace-nowrap">Retail</th>
                                <th class="px-3 py-2.5 font-bold text-right whitespace-nowrap">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($recentStockIns as $stockIn)
                            <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                                <td class="px-3 py-3 font-mono font-bold text-blue-500 whitespace-nowrap">
                                    #SI-{{ $stockIn->ID }}
                                </td>
                                <td class="px-3 py-3 font-medium text-slate-800 dark:text-slate-200 max-w-[200px]">
                                    <div class="truncate text-xs font-bold" title="{{ $stockIn->product->Name ?? 'Product' }}">
                                        {{ $stockIn->product->Name ?? 'Product' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">{{ $stockIn->product->category->Name ?? 'Accessory' }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">{{ $stockIn->user->name ?? 'Admin' }}</div>
                                    <span class="inline-block text-[10px] font-semibold text-slate-400">{{ $stockIn->user->role ?? 'Staff' }}</span>
                                </td>
                                <td class="px-3 py-3 text-right font-black text-green-500 whitespace-nowrap">
                                    +{{ number_format($stockIn->Quantity, 0) }}
                                </td>
                                <td class="px-3 py-3 text-right text-xs text-slate-500 dark:text-slate-400 font-mono whitespace-nowrap">
                                    ₱{{ number_format($stockIn->Cost_Price, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right text-xs font-bold text-slate-900 dark:text-white font-mono whitespace-nowrap">
                                    ₱{{ number_format($stockIn->Retail_Price, 2) }}
                                </td>
                                <td class="px-3 py-3 text-right text-xs text-slate-400 whitespace-nowrap">
                                    {{ $stockIn->created_at ? $stockIn->created_at->format('M d') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-slate-400 text-xs">No stock-in records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Category Overview Cards -->
    <div class="glass-card rounded-3xl p-5 sm:p-6 border shadow-sm">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-200 dark:border-slate-800">
            <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-tags text-purple-500"></i>
                Product Categories
            </h3>
            <a href="{{ route('products.index') }}" class="text-xs font-bold text-purple-500 hover:text-red-400 transition-colors">
                Browse Catalog &rarr;
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5">
            @foreach($categories as $cat)
            <a href="{{ route('products.index', ['category_id' => $cat->ID]) }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-dark-800/60 border border-slate-200 dark:border-slate-700/60 hover:border-purple-500/50 hover:shadow-md transition-all text-left group">
                <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center text-xs mb-2 group-hover:bg-purple-500 group-hover:text-white transition-colors">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-purple-500 transition-colors line-clamp-1">
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
