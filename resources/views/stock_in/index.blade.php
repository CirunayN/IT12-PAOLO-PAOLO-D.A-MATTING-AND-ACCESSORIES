@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-boxes-stacked text-red-500"></i>
                Inventory
            </h1>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" onclick="openCategoryManager()"
                class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 font-bold text-sm border border-slate-300 dark:border-slate-700 flex items-center gap-1.5 transition-all cursor-pointer">
                <i class="fas fa-tags text-red-500" aria-hidden="true"></i>
                <span>Manage Categories</span>
            </button>

            <a href="{{ route('stock-in.print', request()->except(['page', 'output'])) }}" onclick="event.preventDefault(); openReportOutput(this.href)"
                class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-800 dark:text-slate-100 font-bold text-sm border border-slate-300 dark:border-slate-700 flex items-center gap-2 transition-all">
                <i class="fas fa-print text-blue-500"></i>
                <span>Print / Download Receiving</span>
            </a>

            <a href="{{ route('stock-in.create') }}"
                class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2 transition-all">
                <i class="fas fa-truck-ramp-box"></i>
                <span>Receive New Shipment</span>
            </a>
        </div>
    </div>

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
    </div>

    <!-- Filter & Search Bar -->
    <div class="glass-card rounded-2xl p-4 border shadow-sm">
        <form data-auto-filter method="GET" action="{{ route('stock-in.index') }}" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-3 items-center">
            <div class="xl:col-span-3 relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product name or description..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div class="xl:col-span-2">
                <select name="category_id" data-category-select="filter" aria-label="Category" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->ID }}" {{ request('category_id') == $cat->ID ? 'selected' : '' }}>{{ $cat->Name }}{{ $cat->Is_Archived ? ' (Archived)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-3">
                <select name="stock_level" aria-label="Stock level" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="">All Stock Levels</option>
                    <option value="available" {{ request('stock_level') === 'available' ? 'selected' : '' }}>Available (6+ units)</option>
                    <option value="attention" {{ request('stock_level') === 'attention' ? 'selected' : '' }}>Low / Out of Stock</option>
                    <option value="low" {{ request('stock_level') === 'low' ? 'selected' : '' }}>Low Stock (1-5 units)</option>
                    <option value="out" {{ request('stock_level') === 'out' ? 'selected' : '' }}>Out of Stock (0)</option>
                </select>
            </div>
            <div class="xl:col-span-3">
                <select name="sort" aria-label="Order by" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100">
                    <option value="stock_asc" {{ request('sort', 'stock_asc') === 'stock_asc' ? 'selected' : '' }}>Stock: Low to High (0 - 100+)</option>
                    <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Stock: High to Low</option>
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest first</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                    <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Name: Z to A</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                </select>
            </div>
            <div class="xl:col-span-1 flex items-center gap-2">
                <noscript><button type="submit" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 dark:bg-dark-700 dark:hover:bg-dark-600 text-white font-bold text-sm">
                    Filter
                </button></noscript>
                <a href="{{ route('stock-in.index') }}" class="px-3 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-600 dark:text-slate-300 text-sm text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="glass-card rounded-2xl border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">Batch & Date</th>
                        <th class="py-3.5 px-4">Product</th>
                        <th class="py-3.5 px-4 text-right">Units (In / Left)</th>
                        <th class="py-3.5 px-4 text-right">Pricing (Retail / Cost)</th>
                        <th class="py-3.5 px-4 text-right">Total Investment</th>
                        <th class="py-3.5 px-4 text-center">Details</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($stockIns as $si)
                    @php
                        $isExpired =
                            $si->Has_Expiration &&
                            $si->Expiration_Date &&
                            $si->Expiration_Date->lt(today());

                        $remaining = (float) ($si->Remaining_Quantity ?? 0);
                        $categoryName = $si->product?->category->Name ?? 'General';
                        $img = $si->product?->image_url;
                        $condition = $si->Condition ?? 'Good';
                    @endphp

                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors {{ $isExpired ? 'bg-rose-500/5' : '' }}">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="font-mono font-bold text-xs text-slate-800 dark:text-slate-200">
                                #SI-{{ $si->ID }}
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $si->created_at ? $si->created_at->format('M d, Y') : '-' }}
                            </div>
                            @if($remaining <= 0)
                            <span class="inline-block mt-0.5 text-[9px] font-bold px-1.5 py-0.2 rounded bg-slate-200 dark:bg-dark-800 text-slate-500">
                                Depleted
                            </span>
                            @endif
                        </td>

                        <td class="py-3 px-4 min-w-0">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-dark-800 border border-slate-200 dark:border-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                    @if($img)
                                    <img src="{{ $img }}" alt="{{ $si->product->Name ?? '' }}" class="w-full h-full object-cover">
                                    @else
                                    <i class="fas fa-boxes-stacked text-slate-400 text-xs"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-900 dark:text-white truncate max-w-[200px]" title="{{ $si->product->Name ?? 'N/A' }}">
                                        {{ $si->product->Name ?? 'N/A' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $categoryName }}
                                    </div>
                                </div>
                            </div>
                        </td>


                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <div class="font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                +{{ number_format($si->Quantity, 0) }}
                            </div>
                            <div class="text-[10px] {{ $remaining > 0 ? 'text-slate-500 dark:text-slate-400 font-medium' : 'text-slate-400 opacity-60' }}">
                                {{ number_format($remaining, 0) }} left
                            </div>
                        </td>

                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <div class="text-xs font-black text-slate-900 dark:text-white">
                                ₱{{ number_format($si->Retail_Price, 2) }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                ₱{{ number_format($si->Cost_Price, 2) }} cost
                            </div>
                        </td>

                        <td class="py-3 px-4 text-right font-black font-display text-sm text-slate-900 dark:text-white whitespace-nowrap">
                            ₱{{ number_format($si->Quantity * $si->Cost_Price, 2) }}
                        </td>

                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($si->Has_Expiration && $si->Expiration_Date && $isExpired)
                                <span class="text-[10px] font-bold text-rose-500 bg-rose-500/10 px-1.5 py-0.5 rounded border border-rose-500/20" title="Expired {{ $si->Expiration_Date->format('M d, Y') }}">
                                    Expired
                                </span>
                                @endif
                                @if($condition !== 'Good')
                                <span class="text-[10px] font-bold text-rose-500 bg-rose-500/10 px-1.5 py-0.5 rounded border border-rose-500/20">
                                    {{ $condition }}
                                </span>
                                @endif
                                <button type="button"
                                    onclick="openStockInDetailModal(this)"
                                    data-id="{{ $si->ID }}"
                                    data-date="{{ $si->created_at ? $si->created_at->format('M d, Y h:i A') : '-' }}"
                                    data-product="{{ $si->product->Name ?? 'N/A' }}"
                                    data-category="{{ $categoryName }}"
                                    data-qty="{{ number_format($si->Quantity, 0) }}"
                                    data-remaining="{{ number_format($remaining, 0) }}"
                                    data-cost="₱{{ number_format($si->Cost_Price, 2) }}"
                                    data-retail="₱{{ number_format($si->Retail_Price, 2) }}"
                                    data-total="₱{{ number_format($si->Quantity * $si->Cost_Price, 2) }}"
                                    data-hasexp="{{ $si->Has_Expiration ? '1' : '0' }}"
                                    data-exp="{{ $si->Expiration_Date ? $si->Expiration_Date->format('M d, Y') : 'No expiration' }}"
                                    data-expired="{{ $isExpired ? '1' : '0' }}"
                                    data-condition="{{ $condition }}"
                                    class="w-7 h-7 rounded-full hover:bg-red-500/15 text-red-500 dark:text-red-400 inline-flex items-center justify-center transition-transform hover:scale-110 cursor-pointer"
                                    title="View shipment batch details">
                                    <i class="fas fa-circle-info text-base"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">
                            No stock-in records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stockIns->total() > 0)
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $stockIns->links() }}
        </div>
        @endif
    </div>
</div>

<!-- STOCK-IN BATCH DETAILS MODAL (POS STYLE) -->
<div id="stockInDetailModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-md w-full rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 sm:p-6 space-y-4">
        <!-- Header -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-500/15 text-red-500 flex items-center justify-center text-lg">
                    <i class="fas fa-truck-ramp-box"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold font-display text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Shipment Batch</span>
                        <span id="siModalBatchId" class="text-xs px-2 py-0.5 rounded-full bg-red-500/15 text-red-500 font-mono font-bold"></span>
                    </h3>
                    <p id="siModalDate" class="text-[11px] text-slate-400 mt-0.5"></p>
                </div>
            </div>
            <button type="button" onclick="closeStockInDetailModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl cursor-pointer">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
            <!-- Product & Category -->
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800">
                <span class="text-[10px] uppercase font-bold text-slate-400">Product:</span>
                <div id="siModalProduct" class="text-sm font-bold text-slate-900 dark:text-white mt-0.5"></div>
                <div class="mt-1.5 flex items-center gap-2">
                    <span id="siModalCategory" class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300"></span>
                    <span id="siModalCondition" class="text-[10px] font-bold px-2 py-0.5 rounded"></span>
                </div>
            </div>

            <!-- Units & Stock Status -->
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Units Received:</span>
                    <div id="siModalQty" class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5"></div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Remaining Units:</span>
                    <div id="siModalRemaining" class="text-base font-black text-slate-900 dark:text-white mt-0.5"></div>
                </div>
            </div>

            <!-- Financials -->
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Cost Price (Unit):</span>
                    <span id="siModalCost" class="font-bold text-slate-900 dark:text-white"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400">Retail Selling Price:</span>
                    <span id="siModalRetail" class="font-bold text-emerald-600 dark:text-emerald-400"></span>
                </div>
                <div class="flex items-center justify-between pt-1.5 border-t border-slate-200 dark:border-slate-800 font-bold">
                    <span class="text-slate-700 dark:text-slate-300">Total Batch Investment:</span>
                    <span id="siModalTotal" class="text-sm font-black text-slate-900 dark:text-white"></span>
                </div>
            </div>

            <!-- Expiration -->
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold text-slate-400">Expiration Date:</span>
                <div id="siModalExp" class="font-bold text-xs"></div>
            </div>
        </div>

        <div class="pt-2 flex justify-end border-t border-slate-200 dark:border-slate-800">
            <button type="button" onclick="closeStockInDetailModal()" class="px-5 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-bold text-xs cursor-pointer">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function openStockInDetailModal(btn) {
    const d = btn.dataset;
    document.getElementById('siModalBatchId').textContent = '#SI-' + d.id;
    document.getElementById('siModalDate').textContent = d.date;
    document.getElementById('siModalProduct').textContent = d.product;
    document.getElementById('siModalCategory').textContent = d.category;
    document.getElementById('siModalQty').textContent = '+' + d.qty;
    document.getElementById('siModalRemaining').textContent = d.remaining + ' left';
    document.getElementById('siModalCost').textContent = d.cost;
    document.getElementById('siModalRetail').textContent = d.retail;
    document.getElementById('siModalTotal').textContent = d.total;

    const condEl = document.getElementById('siModalCondition');
    condEl.textContent = d.condition;
    condEl.className = d.condition === 'Good'
        ? 'text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-500 border border-emerald-500/20'
        : 'text-[10px] font-bold px-2 py-0.5 rounded bg-rose-500/10 text-rose-500 border border-rose-500/20';

    const expEl = document.getElementById('siModalExp');
    if (d.hasexp === '1') {
        if (d.expired === '1') {
            expEl.innerHTML = `<span class="text-rose-500 flex items-center gap-1"><i class="fas fa-triangle-exclamation"></i> Expired (${d.exp})</span>`;
        } else {
            expEl.innerHTML = `<span class="text-amber-500 flex items-center gap-1"><i class="fas fa-calendar"></i> ${d.exp}</span>`;
        }
    } else {
        expEl.textContent = 'No expiration';
        expEl.className = 'font-bold text-xs mt-0.5 text-slate-400';
    }

    const modal = document.getElementById('stockInDetailModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeStockInDetailModal() {
    const modal = document.getElementById('stockInDetailModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeStockInDetailModal();
});
</script>
@endsection
