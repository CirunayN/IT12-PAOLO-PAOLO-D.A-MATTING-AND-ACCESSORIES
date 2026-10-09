@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-receipt text-red-500"></i>
                <span>Transactions &amp; Sales</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Audit history &bull; Active Report: <strong class="text-slate-900 dark:text-white">{{ $periodLabel }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Print PDF Report -->
            <button type="button" onclick="printCurrentTransactionReport()"
                class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-red-600/25 flex items-center justify-center gap-2 transition-all">
                <i class="fas fa-print"></i>
                <span>Print / Download Report</span>
            </button>
        </div>
    </div>

    <!-- Filters & Quick Presets -->
    <div class="glass-card rounded-2xl p-4 sm:p-5 border shadow-sm space-y-4">
        <!-- Quick Preset Pills -->
        <div class="flex items-center gap-2 flex-wrap pb-2 border-b border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 mr-1">Quick Presets:</span>
            <button type="button" onclick="setPreset('today')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('period') === 'today' ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-dark-700' }}">
                Today
            </button>
            <button type="button" onclick="setPreset('monthly')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ (!request('period') || request('period') === 'monthly') ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-dark-700' }}">
                This Month
            </button>
            <button type="button" onclick="setPreset('yearly')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('period') === 'yearly' ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-dark-700' }}">
                This Year
            </button>
            <button type="button" onclick="setPreset('overall')"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('period') === 'overall' ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-dark-700' }}">
                All Time (Overall)
            </button>
        </div>

        <!-- Filter Form -->
        <form data-auto-filter method="GET" action="{{ route('transactions.index') }}" id="transactionFilterForm" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <!-- Search by Invoice / GCash Ref -->
            <div class="sm:col-span-3">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Search Invoice / Ref #
                </label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. 104 or Ref..."
                        class="w-full pl-8 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>

            <!-- Report Period -->
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Period Scope
                </label>
                <select name="period" id="personalReportPeriod" onchange="handlePeriodChange(false)"
                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white cursor-pointer">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    <option value="overall" {{ $period === 'overall' ? 'selected' : '' }}>All Time (Overall)</option>
                </select>
            </div>

            <!-- Month Input -->
            <div class="sm:col-span-2 {{ $period === 'monthly' ? '' : 'hidden' }}" id="personalMonthContainer">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Month
                </label>
                <input type="month" name="month" id="personalReportMonth" value="{{ $month }}"
                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white">
            </div>

            <!-- Year Input -->
            <div class="sm:col-span-2 {{ $period === 'yearly' ? '' : 'hidden' }}" id="personalYearContainer">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Year
                </label>
                <input type="number" name="year" id="personalReportYear" value="{{ $year }}" min="2000" max="2100"
                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white">
            </div>

            <!-- Payment Method Filter -->
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Payment Method
                </label>
                <select name="payment_method"
                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white cursor-pointer">
                    <option value="">All Payments</option>
                    <option value="Cash" {{ request('payment_method') === 'Cash' ? 'selected' : '' }}>Cash Only</option>
                    <option value="GCash" {{ request('payment_method') === 'GCash' ? 'selected' : '' }}>GCash Only</option>
                </select>
            </div>

            <!-- Admin Cashier Filter -->
            @if(auth()->user() && auth()->user()->isAdmin())
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                    Cashier (Admin)
                </label>
                <select name="cashier_id"
                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-white cursor-pointer">
                    <option value="all">All Cashiers</option>
                    @foreach($cashiers as $c)
                    <option value="{{ $c->id }}" {{ request('cashier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Reset Button -->
            <div class="sm:col-span-1 flex items-center">
                @if(request()->filled('search') || request()->filled('payment_method') || (auth()->user()->isAdmin() && request()->filled('cashier_id') && request('cashier_id') !== 'all') || request('period') === 'today' || request('period') === 'overall')
                <a href="{{ route('transactions.index') }}" title="Clear all filters"
                    class="w-full py-2 px-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white text-xs font-bold flex items-center justify-center gap-1 transition-colors text-center">
                    <i class="fas fa-rotate-left"></i>
                    <span>Reset</span>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
        <div class="glass-card rounded-2xl p-5 border shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                <span>Transactions</span>
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center"><i class="fas fa-receipt"></i></div>
            </div>
            <div class="text-3xl font-black font-display text-slate-900 dark:text-white">
                {{ number_format($totalTransactions) }}
            </div>
            <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400">Completed records</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                <span>Cash Sales</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center"><i class="fas fa-money-bill-wave"></i></div>
            </div>
            <div class="text-3xl font-black font-display text-slate-900 dark:text-white">
                ₱{{ number_format($cashSales, 2) }}
            </div>
            <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-emerald-500 font-bold">Physical Cash Received</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                <span>GCash Sales</span>
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center"><i class="fas fa-mobile-screen"></i></div>
            </div>
            <div class="text-3xl font-black font-display text-slate-900 dark:text-white">
                ₱{{ number_format($gcashSales, 2) }}
            </div>
            <div class="pt-2 mt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-blue-500 font-bold">E-Wallet Payments</div>
        </div>

        <div class="glass-card rounded-2xl p-5 border shadow-sm flex flex-col justify-between bg-emerald-500/[0.04]">
            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-2">
                <span>Total Revenue</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-500 flex items-center justify-center"><i class="fas fa-chart-line"></i></div>
            </div>
            <div class="text-3xl font-black font-display text-emerald-600 dark:text-emerald-400">
                ₱{{ number_format($totalSales, 2) }}
            </div>
            <div class="pt-2 mt-2 border-t border-emerald-500/20 text-[11px] text-slate-400">{{ $periodLabel }}</div>
        </div>
    </div>

    <!-- Transaction History Table -->
    <div class="glass-card rounded-2xl border shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-display font-black text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-list-check text-red-500"></i>
                <span>Sales Transaction History</span>
            </h3>
            <span class="text-xs text-slate-400">Total: <strong>{{ $sales->total() }}</strong> records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/60 dark:bg-dark-850/60 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-4 py-3 font-bold">Invoice #</th>
                        <th class="px-4 py-3 font-bold">Date &amp; Time</th>
                        <th class="px-4 py-3 font-bold">Cashier</th>
                        <th class="px-4 py-3 text-center font-bold">Items</th>
                        <th class="px-4 py-3 font-bold">Payment</th>
                        <th class="px-4 py-3 text-right font-bold">Total Amount</th>
                        <th class="px-4 py-3 text-center font-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($sales as $sale)
                    @php
                        $isGcash = strtolower($sale->paymentMethod->Name ?? '') === 'gcash';
                        $itemsData = $sale->soldItems->map(fn($item) => [
                            'name' => $item->product->Name ?? 'Item',
                            'qty' => (float) $item->Quantity,
                            'total' => (float) $item->Total,
                            'unit_price' => $item->Quantity > 0 ? ((float) $item->Total / (float) $item->Quantity) : 0
                        ]);
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                        <td class="px-4 py-3 font-bold font-mono text-red-600 dark:text-red-400 whitespace-nowrap">
                            INV-{{ $sale->ID }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                            {{ $sale->Date ? $sale->Date->format('M d, Y h:i A') : '-' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-xs font-bold text-slate-900 dark:text-white">{{ $sale->user->name ?? 'Staff' }}</div>
                            <div class="text-[10px] text-slate-400">{{ $sale->user->role ?? 'Employee' }}</div>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300">
                                {{ number_format($sale->soldItems->sum('Quantity'), 0) }}
                            </span>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($isGcash)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                    <i class="fas fa-mobile-screen"></i> GCash
                                </span>
                                @if($sale->GCash_Reference_Number)
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5">Ref: {{ $sale->GCash_Reference_Number }}</div>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <i class="fas fa-money-bill-wave"></i> Cash
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-right font-display font-black text-sm text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                            ₱{{ number_format($sale->Total, 2) }}
                        </td>

                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <button type="button"
                                onclick="openSaleModal({{ json_encode([
                                    'id' => $sale->ID,
                                    'date' => $sale->Date ? $sale->Date->format('M d, Y h:i A') : '-',
                                    'cashier' => $sale->user->name ?? 'Staff',
                                    'cashier_role' => $sale->user->role ?? 'Employee',
                                    'payment' => $sale->paymentMethod->Name ?? 'N/A',
                                    'gcash_ref' => $sale->GCash_Reference_Number,
                                    'tendered' => (float) $sale->Amount_Received,
                                    'change' => (float) $sale->Change_Amount,
                                    'total' => (float) $sale->Total,
                                    'items' => $itemsData,
                                    'receipt_url' => route('pos.receipt', $sale->ID)
                                ]) }})"
                                class="px-2.5 py-1 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-600 hover:text-white dark:hover:bg-red-600 text-slate-700 dark:text-slate-300 text-xs font-bold transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                                title="View itemized sale details">
                                <i class="fas fa-eye"></i>
                                <span>View</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-14 text-center text-slate-400 text-sm">
                            <i class="fas fa-receipt text-3xl mb-2 opacity-50"></i>
                            <p>No transactions found matching the selected filters.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $sales->links() }}
        </div>
    </div>

</div>

<!-- Itemized Sale Details Modal -->
<div id="saleDetailsModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-lg w-full rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 sm:p-6 space-y-4">
        <!-- Modal Header -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <div>
                <div class="text-[10px] uppercase font-bold text-slate-400">Transaction Details</div>
                <h3 id="modalSaleId" class="font-display font-black text-xl text-slate-900 dark:text-white">INV-0</h3>
            </div>
            <button type="button" onclick="closeSaleModal()" class="text-2xl text-slate-400 hover:text-slate-600 dark:hover:text-white leading-none cursor-pointer">&times;</button>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-dark-900/80 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
                <span class="text-slate-400">Date:</span>
                <strong id="modalDate" class="block text-slate-900 dark:text-white">-</strong>
            </div>
            <div>
                <span class="text-slate-400">Cashier:</span>
                <strong id="modalCashier" class="block text-slate-900 dark:text-white">-</strong>
            </div>
            <div>
                <span class="text-slate-400">Payment:</span>
                <strong id="modalPayment" class="block text-slate-900 dark:text-white">-</strong>
            </div>
            <div id="modalGcashRefWrap" class="hidden">
                <span class="text-slate-400">GCash Ref:</span>
                <strong id="modalGcashRef" class="block font-mono text-blue-500">-</strong>
            </div>
        </div>

        <!-- Items Breakdown -->
        <div class="space-y-2">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Purchased Items</div>
            <div id="modalItemsList" class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 pr-1 text-xs">
                <!-- Injected via JavaScript -->
            </div>
        </div>

        <!-- Totals & Payment Breakdown -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-1.5 text-xs">
            <div class="flex justify-between text-base font-bold text-slate-900 dark:text-white">
                <span>Grand Total:</span>
                <strong id="modalTotal" class="font-display font-black text-lg text-emerald-500">₱0.00</strong>
            </div>
            <div id="modalCashBreakdown" class="space-y-1 text-slate-500 dark:text-slate-400">
                <div class="flex justify-between">
                    <span>Amount Received:</span>
                    <span id="modalTendered">₱0.00</span>
                </div>
                <div class="flex justify-between font-semibold text-emerald-500">
                    <span>Change:</span>
                    <span id="modalChange">₱0.00</span>
                </div>
            </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex items-center gap-3 pt-2">
            <button type="button" onclick="closeSaleModal()"
                class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors cursor-pointer">
                Close
            </button>
            <a id="modalReprintBtn" href="#" target="_blank"
                class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Reprint Receipt</span>
            </a>
        </div>
    </div>
</div>

<script>
function handlePeriodChange(submit = true) {
    const period = document.getElementById('personalReportPeriod').value;
    const monthBox = document.getElementById('personalMonthContainer');
    const yearBox = document.getElementById('personalYearContainer');

    monthBox.classList.toggle('hidden', period !== 'monthly');
    yearBox.classList.toggle('hidden', period !== 'yearly');

    document.getElementById('personalReportMonth').disabled = period !== 'monthly';
    document.getElementById('personalReportYear').disabled = period !== 'yearly';
    if (submit) document.getElementById('transactionFilterForm').requestSubmit();
}

function setPreset(p) {
    document.getElementById('personalReportPeriod').value = p;
    handlePeriodChange();
}

function printCurrentTransactionReport() {
    const params = new URLSearchParams(window.location.search);
    params.delete('page');
    openReportOutput("{{ route('transactions.print') }}?" + params.toString());
}

document.addEventListener('DOMContentLoaded', () => handlePeriodChange(false));

function escapeSaleText(value) {
    const span = document.createElement('span');
    span.textContent = String(value ?? '');
    return span.innerHTML;
}

function openSaleModal(sale) {
    document.getElementById('modalSaleId').textContent = 'INV-' + sale.id;
    document.getElementById('modalDate').textContent = sale.date;
    document.getElementById('modalCashier').textContent = `${sale.cashier} (${sale.cashier_role})`;
    document.getElementById('modalPayment').textContent = sale.payment;

    const gcashWrap = document.getElementById('modalGcashRefWrap');
    if (sale.gcash_ref) {
        gcashWrap.classList.remove('hidden');
        document.getElementById('modalGcashRef').textContent = sale.gcash_ref;
    } else {
        gcashWrap.classList.add('hidden');
    }

    let itemsHtml = '';
    (sale.items || []).forEach(item => {
        itemsHtml += `
            <div class="py-2 flex items-center justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <strong class="text-slate-900 dark:text-white truncate block">${escapeSaleText(item.name)}</strong>
                    <span class="text-[10px] text-slate-400">₱${Number(item.unit_price).toFixed(2)} × ${item.qty}</span>
                </div>
                <strong class="text-slate-900 dark:text-white whitespace-nowrap">₱${Number(item.total).toFixed(2)}</strong>
            </div>
        `;
    });
    document.getElementById('modalItemsList').innerHTML = itemsHtml;

    document.getElementById('modalTotal').textContent = '₱' + Number(sale.total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('modalTendered').textContent = '₱' + Number(sale.tendered).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('modalChange').textContent = '₱' + Number(sale.change).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const cashBox = document.getElementById('modalCashBreakdown');
    cashBox.classList.toggle('hidden', String(sale.payment).toLowerCase() !== 'cash');

    document.getElementById('modalReprintBtn').href = sale.receipt_url;

    const modal = document.getElementById('saleDetailsModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSaleModal() {
    const modal = document.getElementById('saleDetailsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSaleModal();
});
</script>
@endsection
