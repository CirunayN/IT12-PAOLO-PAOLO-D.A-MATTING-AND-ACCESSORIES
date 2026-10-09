@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-file-lines text-red-500"></i>
                Dashboard &amp; Reports
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Generate, review, and print business, inventory, or cashier performance reports in tamper-evident PDF format.
            </p>
            @include('reports.tabs')
        </div>

        <button
            type="button"
            onclick="printCurrentAdminReport()"
            class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer"
        >
            <i class="fas fa-print"></i>
            <span>Print Report (PDF)</span>
        </button>
    </div>

    <!-- REPORT FILTER CARD -->
    <form
        id="adminReportFilterForm"
        method="GET"
        action="{{ route('reports.index') }}"
        class="glass-card rounded-2xl p-5 border shadow-sm"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            <!-- REPORT SCOPE -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Report Type
                </label>
                <select
                    name="scope"
                    id="adminReportScope"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    <option value="business" {{ $scope === 'business' ? 'selected' : '' }}>
                        Business Overview (All)
                    </option>
                    <option value="inventory" {{ $scope === 'inventory' ? 'selected' : '' }}>
                        Inventory &amp; Stock Report
                    </option>
                    <option value="employee" {{ $scope === 'employee' ? 'selected' : '' }}>
                        Employee Sales Report
                    </option>
                </select>
            </div>

            <!-- EMPLOYEE FILTER (FOR EMPLOYEE SCOPE) -->
            <div id="adminEmployeeContainer" class="{{ $scope === 'employee' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Employee
                </label>
                <select
                    name="employee_id"
                    id="adminEmployeeSelect"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                    <option
                        value="{{ $employee->id }}"
                        {{ ($selectedEmployee && $selectedEmployee->id == $employee->id) ? 'selected' : '' }}
                    >
                        {{ $employee->name }} ({{ $employee->role ?? 'Employee' }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- CATEGORY FILTER (FOR INVENTORY SCOPE) -->
            <div id="adminCategoryContainer" class="{{ $scope === 'inventory' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Category
                </label>
                <select
                    name="category_id"
                    id="adminCategorySelect"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                    <option
                        value="{{ $category->ID }}"
                        {{ ($selectedCategoryId == $category->ID) ? 'selected' : '' }}
                    >
                        {{ $category->Name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- STOCK STATUS FILTER (FOR INVENTORY SCOPE) -->
            <div id="adminStockStatusContainer" class="{{ $scope === 'inventory' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Stock Level
                </label>
                <select
                    name="stock_status"
                    id="adminStockStatusSelect"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    <option value="">All Stock Levels</option>
                    <option value="available" {{ $selectedStockStatus === 'available' ? 'selected' : '' }}>Available (6+ units)</option>
                    <option value="low" {{ $selectedStockStatus === 'low' ? 'selected' : '' }}>Low Stock (1-5 units)</option>
                    <option value="out" {{ $selectedStockStatus === 'out' ? 'selected' : '' }}>Out of Stock (0 units)</option>
                </select>
            </div>

            <!-- PERIOD -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Period
                </label>
                <select
                    name="period"
                    id="adminReportPeriod"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
                    <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    <option value="overall" {{ $period === 'overall' ? 'selected' : '' }}>Overall</option>
                </select>
            </div>

            <!-- WEEK (WHEN PERIOD IS WEEKLY) -->
            <div id="adminWeekContainer" class="{{ $period === 'weekly' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Select Week
                </label>
                <input
                    type="week"
                    name="week"
                    id="adminReportWeek"
                    value="{{ $week }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
            </div>

            <!-- MONTH (WHEN PERIOD IS MONTHLY) -->
            <div id="adminMonthContainer" class="{{ $period === 'monthly' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Select Month
                </label>
                <input
                    type="month"
                    name="month"
                    id="adminReportMonth"
                    value="{{ $month }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
            </div>

            <!-- YEAR (WHEN PERIOD IS YEARLY) -->
            <div id="adminYearContainer" class="{{ $period === 'yearly' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Select Year
                </label>
                <input
                    type="number"
                    name="year"
                    id="adminReportYear"
                    value="{{ $year }}"
                    min="2000"
                    max="2100"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-100"
                >
            </div>

        </div>
    </form>

    <!-- CURRENT REPORT ACTIVE BANNER -->
    <div class="glass-card rounded-2xl p-4 border flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs">
            <div>
                <span class="uppercase font-bold text-slate-400">Report:</span>
                <strong class="ml-1 text-slate-900 dark:text-white font-black text-sm">{{ $reportTitle }}</strong>
            </div>

            <div>
                <span class="uppercase font-bold text-slate-400">Period:</span>
                <strong class="ml-1 text-blue-600 dark:text-blue-400 font-bold">{{ $periodLabel }}</strong>
            </div>

            @if($scope === 'inventory' && $selectedCategoryId)
                @php $cat = $categories->firstWhere('ID', $selectedCategoryId); @endphp
                <div>
                    <span class="uppercase font-bold text-slate-400">Category:</span>
                    <strong class="ml-1 text-emerald-600 font-bold">{{ $cat->Name ?? 'N/A' }}</strong>
                </div>
            @endif

            @if($scope === 'inventory' && $selectedStockStatus)
                <div>
                    <span class="uppercase font-bold text-slate-400">Stock Filter:</span>
                    <strong class="ml-1 uppercase font-bold">{{ $selectedStockStatus }}</strong>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                <i class="fas fa-shield-check"></i>
                Official PDF Printable
            </span>
        </div>
    </div>

    @if($scope !== 'inventory')
    <!-- SALES SUMMARY (BUSINESS & EMPLOYEE) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-5 border">
            <div class="text-xs uppercase font-bold text-slate-400">Total Sales</div>
            <div class="text-2xl font-black text-emerald-500 mt-2">
                ₱{{ number_format($totalSales, 2) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Period total revenue</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border">
            <div class="text-xs uppercase font-bold text-slate-400">Transactions</div>
            <div class="text-2xl font-black mt-2 text-slate-900 dark:text-white">
                {{ number_format($totalTransactions) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Completed orders</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border">
            <div class="text-xs uppercase font-bold text-slate-400">Cash Sales</div>
            <div class="text-2xl font-black mt-2 text-slate-900 dark:text-white">
                ₱{{ number_format($cashSales, 2) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Cash in register</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border">
            <div class="text-xs uppercase font-bold text-slate-400">GCash Sales</div>
            <div class="text-2xl font-black mt-2 text-blue-500">
                ₱{{ number_format($gcashSales, 2) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Digital payments</div>
        </div>
    </div>
    @endif

    @if($scope === 'inventory')
    <!-- INVENTORY REPORT SUMMARY KPIS -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-card rounded-2xl p-4 border">
            <div class="text-[11px] uppercase font-bold text-slate-400">Products</div>
            <div class="text-xl sm:text-2xl font-black mt-1.5 text-slate-900 dark:text-white">
                {{ number_format($totalProducts) }}
            </div>
            <div class="text-[10px] text-slate-400">Catalog items</div>
        </div>

        <div class="glass-card rounded-2xl p-4 border">
            <div class="text-[11px] uppercase font-bold text-slate-400">Total Units</div>
            <div class="text-xl sm:text-2xl font-black mt-1.5 text-blue-600 dark:text-blue-400">
                {{ number_format($totalStockUnits, 0) }}
            </div>
            <div class="text-[10px] text-slate-400">On-hand stock</div>
        </div>

        <div class="glass-card rounded-2xl p-4 border">
            <div class="text-[11px] uppercase font-bold text-slate-400">Cost Valuation</div>
            <div class="text-xl sm:text-2xl font-black mt-1.5 text-slate-900 dark:text-white">
                ₱{{ number_format($inventoryCostValue, 2) }}
            </div>
            <div class="text-[10px] text-slate-400">Invested cost</div>
        </div>

        <div class="glass-card rounded-2xl p-4 border">
            <div class="text-[11px] uppercase font-bold text-slate-400">Retail Valuation</div>
            <div class="text-xl sm:text-2xl font-black mt-1.5 text-emerald-600 dark:text-emerald-400">
                ₱{{ number_format($inventoryRetailValue, 2) }}
            </div>
            <div class="text-[10px] text-slate-400">Market value</div>
        </div>

        <div class="glass-card rounded-2xl p-4 border">
            <div class="text-[11px] uppercase font-bold text-slate-400">Est. Profit Margin</div>
            <div class="text-xl sm:text-2xl font-black mt-1.5 text-emerald-500">
                ₱{{ number_format($potentialProfit, 2) }}
            </div>
            <div class="text-[10px] text-slate-400">Potential profit</div>
        </div>

        <div class="glass-card rounded-2xl p-4 border">
            <div class="text-[11px] uppercase font-bold text-slate-400">Alerts</div>
            <div class="text-xl sm:text-2xl font-black mt-1.5 flex items-center gap-2">
                <span class="text-amber-500 text-sm font-bold">{{ $lowStockCount }} Low</span>
                <span class="text-rose-500 text-sm font-bold">{{ $outOfStockCount }} Out</span>
            </div>
            <div class="text-[10px] text-slate-400">Attention needed</div>
        </div>
    </div>
    @endif

    @if($scope === 'business')
    <!-- STOCK-IN SUMMARY (BUSINESS SCOPE) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card rounded-2xl p-5 border">
            <div class="text-xs uppercase font-bold text-slate-400">Stock-In Batches</div>
            <div class="text-2xl font-black mt-2 text-slate-900 dark:text-white">
                {{ number_format($stockInBatches) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Deliveries received in period</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border">
            <div class="text-xs uppercase font-bold text-slate-400">Units Received</div>
            <div class="text-2xl font-black mt-2 text-slate-900 dark:text-white">
                {{ number_format($unitsReceived, 0) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">New inventory added</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border">
            <div class="text-xs uppercase font-bold text-slate-400">Stock-In Cost</div>
            <div class="text-2xl font-black mt-2 text-slate-900 dark:text-white">
                ₱{{ number_format($stockInCost, 2) }}
            </div>
            <div class="text-xs text-slate-400 mt-1">Total shipment investment</div>
        </div>
    </div>
    @endif

    @if($scope !== 'inventory')
    <!-- SALES TRANSACTIONS TABLE -->
    <div class="glass-card rounded-2xl border overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-display font-black text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-receipt text-blue-500"></i>
                <span>Sales Transactions</span>
            </h3>
            <span class="text-xs font-bold text-slate-400">{{ $sales->count() }} records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-4 py-3 text-left">Transaction</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Employee</th>
                        <th class="px-4 py-3 text-center">Items</th>
                        <th class="px-4 py-3 text-left">Payment</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                        <td class="px-4 py-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                            INV-{{ $sale->ID }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            {{ $sale->Date ? $sale->Date->format('M d, Y h:i A') : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $sale->user->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            {{ $sale->soldItems->sum('Quantity') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-[11px] font-bold px-2 py-0.5 rounded-lg {{ $sale->paymentMethod?->Name === 'Cash' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-blue-500/10 text-blue-600' }}">
                                {{ $sale->paymentMethod->Name ?? 'Cash' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-black text-slate-900 dark:text-white">
                            ₱{{ number_format($sale->Total, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">
                            No sales transactions recorded for this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($sales, 'hasPages') && $sales->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
    @endif

    @if($scope === 'inventory' || $scope === 'business')
    <!-- INVENTORY VALUATION & STOCK TABLE -->
    <div class="glass-card rounded-2xl border overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-display font-black text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-boxes-stacked text-blue-500"></i>
                <span>Current Stock Levels &amp; Valuation</span>
            </h3>
            <span class="text-xs font-bold text-slate-400">{{ $products->count() }} items listed</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-4 py-3 text-left">Product</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-right">In-Stock Units</th>
                        <th class="px-4 py-3 text-right">Unit Cost</th>
                        <th class="px-4 py-3 text-right">Unit Retail</th>
                        <th class="px-4 py-3 text-right">Cost Valuation</th>
                        <th class="px-4 py-3 text-right">Retail Valuation</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($products as $product)
                    @php
                        $qty = (float) $product->stock_quantity;
                        $cost = (float) $product->cost_price;
                        $retail = (float) $product->retail_price;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                        <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                            {{ $product->Name }}
                        </td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">
                            {{ $product->category->Name ?? 'Uncategorized' }}
                        </td>
                        <td class="px-4 py-3 text-right font-black {{ $qty <= 0 ? 'text-rose-500' : ($qty <= 5 ? 'text-amber-500' : 'text-slate-900 dark:text-white') }}">
                            {{ number_format($qty, 0) }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">
                            ₱{{ number_format($cost, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800 dark:text-slate-200">
                            ₱{{ number_format($retail, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">
                            ₱{{ number_format($qty * $cost, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            ₱{{ number_format($qty * $retail, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($qty <= 0)
                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg bg-rose-500/10 text-rose-500 border border-rose-500/20">
                                Out of Stock
                            </span>
                            @elseif($qty <= 5)
                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-600 border border-amber-500/20">
                                Low Stock
                            </span>
                            @else
                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                                In Stock
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400">
                            No inventory products match the selected criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- STOCK-IN DELIVERIES TABLE -->
    <div class="glass-card rounded-2xl border overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-display font-black text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-truck-ramp-box text-blue-500"></i>
                <span>Stock-In Deliveries Received ({{ $periodLabel }})</span>
            </h3>
            <span class="text-xs font-bold text-slate-400">{{ $stockIns->count() }} batches</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-4 py-3 text-left">Batch ID</th>
                        <th class="px-4 py-3 text-left">Product</th>
                        <th class="px-4 py-3 text-left">Date Received</th>
                        <th class="px-4 py-3 text-left">Received By</th>
                        <th class="px-4 py-3 text-right">Qty Received</th>
                        <th class="px-4 py-3 text-right">Unit Cost</th>
                        <th class="px-4 py-3 text-right">Batch Total</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($stockIns as $stock)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                        <td class="px-4 py-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                            #SI-{{ $stock->ID }}
                        </td>
                        <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">
                            {{ $stock->product->Name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            {{ $stock->created_at ? $stock->created_at->format('M d, Y h:i A') : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $stock->user->name ?? 'System' }}</span>
                            @if($stock->user && $stock->user->isAdmin())
                                <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-600 border border-blue-500/20">Admin</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-black text-emerald-600">
                            +{{ number_format($stock->Quantity, 0) }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-500">
                            ₱{{ number_format($stock->Cost_Price, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-black text-slate-900 dark:text-white">
                            ₱{{ number_format($stock->Quantity * $stock->Cost_Price, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            No stock-in records found for this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($stockIns, 'hasPages') && $stockIns->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $stockIns->links() }}
        </div>
        @endif
    </div>
    @endif

</div>

<script>
const adminReportScope = document.getElementById('adminReportScope');
const adminEmployeeContainer = document.getElementById('adminEmployeeContainer');
const adminCategoryContainer = document.getElementById('adminCategoryContainer');
const adminStockStatusContainer = document.getElementById('adminStockStatusContainer');
const adminReportPeriod = document.getElementById('adminReportPeriod');
const adminWeekContainer = document.getElementById('adminWeekContainer');
const adminMonthContainer = document.getElementById('adminMonthContainer');
const adminYearContainer = document.getElementById('adminYearContainer');

function printCurrentAdminReport() {
    const scope = document.getElementById('adminReportScope').value;
    const period = document.getElementById('adminReportPeriod').value;
    const employeeSelect = document.getElementById('adminEmployeeSelect');
    const categorySelect = document.getElementById('adminCategorySelect');
    const stockStatusSelect = document.getElementById('adminStockStatusSelect');
    const weekInput = document.getElementById('adminReportWeek');
    const monthInput = document.getElementById('adminReportMonth');
    const yearInput = document.getElementById('adminReportYear');

    if (scope === 'employee' && (!employeeSelect || !employeeSelect.value)) {
        alert('Please select an employee before printing an employee report.');
        if (employeeSelect) employeeSelect.focus();
        return;
    }

    const params = new URLSearchParams();
    params.set('scope', scope);
    params.set('period', period);

    if (scope === 'employee' && employeeSelect && employeeSelect.value) {
        params.set('employee_id', employeeSelect.value);
    }

    if (scope === 'inventory') {
        if (categorySelect && categorySelect.value) {
            params.set('category_id', categorySelect.value);
        }
        if (stockStatusSelect && stockStatusSelect.value) {
            params.set('stock_status', stockStatusSelect.value);
        }
    }

    if (period === 'weekly' && weekInput && weekInput.value) {
        params.set('week', weekInput.value);
    } else if (period === 'monthly' && monthInput && monthInput.value) {
        params.set('month', monthInput.value);
    } else if (period === 'yearly' && yearInput && yearInput.value) {
        params.set('year', yearInput.value);
    }

    const printUrl = "{{ route('reports.print') }}" + '?' + params.toString();
    window.open(printUrl, '_blank', 'noopener');
}

function updateAdminScope() {
    const scope = adminReportScope.value;
    adminEmployeeContainer.classList.toggle('hidden', scope !== 'employee');
    adminCategoryContainer.classList.toggle('hidden', scope !== 'inventory');
    adminStockStatusContainer.classList.toggle('hidden', scope !== 'inventory');
}

function updateAdminPeriod() {
    const period = adminReportPeriod.value;
    adminWeekContainer.classList.toggle('hidden', period !== 'weekly');
    adminMonthContainer.classList.toggle('hidden', period !== 'monthly');
    adminYearContainer.classList.toggle('hidden', period !== 'yearly');
}

adminReportScope.addEventListener('change', updateAdminScope);
adminReportPeriod.addEventListener('change', updateAdminPeriod);

updateAdminScope();
updateAdminPeriod();
</script>
@endsection
