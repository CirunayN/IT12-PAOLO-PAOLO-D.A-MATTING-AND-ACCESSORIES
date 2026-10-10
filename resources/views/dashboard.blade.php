@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Top Dashboard Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-chart-pie text-red-500"></i>
                Dashboard &amp; Reports
            </h1>
            @include('reports.tabs')
        </div>

    </div>

    <!-- Filter Toolbar: Quick Presets & Custom Date Range -->
    <div class="glass-card rounded-2xl p-4 sm:p-5 border shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Quick Period Presets -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-1 flex items-center gap-1.5">
                    <i class="fas fa-clock-rotate-left text-red-500"></i>
                    <span>Period:</span>
                </span>
                <a href="{{ route('dashboard', ['period' => 'today']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($activePeriod ?? '') === 'today' ? 'bg-red-600 text-white shadow-sm shadow-red-600/30' : 'bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-750 text-slate-700 dark:text-slate-300' }}">
                    Today
                </a>
                <a href="{{ route('dashboard', ['period' => 'month']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($activePeriod ?? '') === 'month' ? 'bg-red-600 text-white shadow-sm shadow-red-600/30' : 'bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-750 text-slate-700 dark:text-slate-300' }}">
                    This Month
                </a>
                <a href="{{ route('dashboard', ['period' => 'year']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($activePeriod ?? '') === 'year' ? 'bg-red-600 text-white shadow-sm shadow-red-600/30' : 'bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-750 text-slate-700 dark:text-slate-300' }}">
                    This Year
                </a>
                <a href="{{ route('dashboard', ['period' => 'all']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ ($activePeriod ?? '') === 'all' ? 'bg-red-600 text-white shadow-sm shadow-red-600/30' : 'bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-750 text-slate-700 dark:text-slate-300' }}">
                    All Time
                </a>
            </div>

            <!-- Custom Date Range Form (No data-auto-filter to avoid premature submits) -->
            <form id="dashboardDateFilterForm" method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                <div class="flex-1 sm:w-44">
                    <label for="dashboardStart" class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        From Date
                    </label>
                    <input id="dashboardStart" type="date" name="start_date" value="{{ $startDate }}" required
                        class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div class="flex-1 sm:w-44">
                    <label for="dashboardEnd" class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        To Date
                    </label>
                    <input id="dashboardEnd" type="date" name="end_date" value="{{ $endDate }}" min="{{ $startDate }}" required
                        class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" title="Reset to current month"
                        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-600 dark:text-slate-400 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors shadow-sm">
                        <i class="fas fa-rotate-left"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const startInput = document.getElementById('dashboardStart');
            const endInput = document.getElementById('dashboardEnd');
            const filterForm = document.getElementById('dashboardDateFilterForm');
            if (!startInput || !endInput || !filterForm) return;

            startInput.addEventListener('change', function() {
                endInput.min = this.value;
                if (endInput.value && endInput.value < this.value) {
                    endInput.value = this.value;
                }
                if (this.value && endInput.value) {
                    filterForm.submit();
                }
            });

            endInput.addEventListener('change', function() {
                if (this.value && this.value < startInput.value) {
                    startInput.value = this.value;
                }
                endInput.min = startInput.value;
                if (this.value && startInput.value) {
                    filterForm.submit();
                }
            });
        })();
    </script>

    <!-- 5-Column Stats Grid with Featured Main "Sales in Selected Period" Hero Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-5 items-stretch">

        <!-- MAIN FEATURED HERO CARD: Sales in Selected Period (Spans 2 columns, larger typography & live badge) -->
        <div class="md:col-span-2 lg:col-span-2 glass-card rounded-3xl p-6 sm:p-7 border-2 border-emerald-500/30 dark:border-emerald-500/20 bg-gradient-to-br from-emerald-500/10 via-slate-50 to-slate-100 dark:from-emerald-950/40 dark:via-dark-900 dark:to-dark-850 shadow-lg shadow-emerald-500/5 relative overflow-hidden flex flex-col justify-between">
            <!-- Background Glow Accent -->
            <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sales in Selected Period
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
                    <span><strong>{{ $todaySalesCount }}</strong> completed {{ Str::plural('transaction', $todaySalesCount) }}</span>
                </div>
                <span class="text-slate-400 font-semibold">{{ $periodLabel }}</span>
            </div>
        </div>

        <!-- STAT CARD 2: Total Sales Revenue -->
        <a href="{{ route('transactions.index', ['period' => 'overall']) }}"
           title="View all sales transactions"
           class="dashboard-sales-card glass-card group rounded-3xl p-5 sm:p-6 border shadow-sm transition-all hover:shadow-md hover:border-blue-500/40 flex flex-col justify-between cursor-pointer min-w-0">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <span class="min-w-0 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">All-Time Sales</span>
                    <div class="w-10 h-10 shrink-0 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-base">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="dashboard-sales-amount font-black font-display text-slate-900 dark:text-white group-hover:text-blue-500 transition-colors">
                    ₱{{ number_format($totalSalesAllTime, 2) }}
                </div>
            </div>
            <div class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex flex-wrap items-center justify-between gap-2">
                <span class="flex items-center gap-1.5"><i class="fas fa-file-invoice text-blue-500"></i><strong>{{ $totalTransactions }}</strong> sales</span>
                <span class="text-blue-500 font-bold flex items-center gap-1 text-[11px] group-hover:translate-x-0.5 transition-transform">View &rarr;</span>
            </div>
        </a>

        <!-- STAT CARD 3: Inventory Units & Value -->
        <a href="{{ route('products.index') }}"
           title="View inventory catalog"
           class="glass-card group rounded-3xl p-5 sm:p-6 border shadow-sm transition-all hover:shadow-md hover:border-red-500/40 flex flex-col justify-between cursor-pointer">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 group-hover:text-red-500 transition-colors">Inventory Stock</span>
                    <div class="w-10 h-10 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center text-base group-hover:scale-110 transition-transform">
                        <i class="fas fa-cubes-stacked"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white group-hover:text-red-500 transition-colors">
                    {{ number_format($totalStockUnits, 0) }} <span class="text-sm font-normal text-slate-400">Units</span>
                </div>
            </div>
            <div class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between gap-2 min-w-0">
                <span class="truncate min-w-0 flex-1 flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-semibold text-[11px]" title="Inventory Value: ₱{{ number_format($inventoryValue, 2) }}">
                    <i class="fas fa-tag text-red-500 flex-shrink-0 text-[10px]"></i>
                    <span class="truncate">₱{{ number_format($inventoryValue, 2) }}</span>
                </span>
                <span class="text-red-500 font-bold flex items-center gap-1 text-[11px] whitespace-nowrap shrink-0 group-hover:translate-x-0.5 transition-transform">Manage &rarr;</span>
            </div>
        </a>

        <!-- STAT CARD 4: Products & Alerts -->
        <div class="glass-card rounded-3xl p-5 sm:p-6 border shadow-sm flex flex-col justify-between">
            <a id="totalItemsCard" href="{{ route('products.index') }}" class="block group" title="View all inventory items">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Items</span>
                    <div class="w-10 h-10 rounded-2xl bg-purple-500/10 text-purple-500 flex items-center justify-center"><i class="fas fa-boxes-packing"></i></div>
                </div>
                <div class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white group-hover:text-purple-500">
                    {{ $totalProductsCount }} <span class="text-sm font-normal text-slate-400">Products</span>
                </div>
            </a>
            <div class="pt-3 mt-3 border-t border-slate-200 dark:border-slate-800 text-xs flex flex-wrap items-center gap-2">
                <a href="{{ route('products.index', ['stock_level' => 'low']) }}" class="flex-1 rounded-xl px-2 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold hover:underline">
                    <i class="fas fa-triangle-exclamation"></i> {{ $lowStockCount }} Low on Stock
                </a>
                <a href="{{ route('products.index', ['stock_level' => 'out']) }}" class="flex-1 rounded-xl px-2 py-1 bg-rose-500/10 text-rose-600 dark:text-rose-400 font-bold hover:underline">
                    <i class="fas fa-circle-xmark"></i> {{ $outOfStockCount }} Out of Stock
                </a>
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
                        <div>
                            <h3 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white">Recent Sales</h3>
                            @if(!empty($isFallbackRecentSales))
                                <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                    <i class="fas fa-clock-rotate-left text-[10px]"></i> Latest recorded transactions
                                </span>
                            @else
                                <span class="text-[11px] font-semibold text-slate-400">
                                    {{ $periodLabel }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors">
                            Transactions &rarr;
                        </a>
                        <a href="{{ route('pos.index') }}" class="text-xs font-bold text-blue-500 hover:text-blue-400 transition-colors">
                            New Sale &rarr;
                        </a>
                    </div>
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

        @include('dashboard.stock-alerts')
    </div>

    @include('dashboard.product-sales')

</div>
@endsection
