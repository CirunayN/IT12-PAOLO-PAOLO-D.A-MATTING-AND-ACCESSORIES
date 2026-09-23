@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-truck-ramp-box text-red-500"></i>
                Receive Stock Shipment
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Record new inventory batches, update wholesale costs, and log receiving staff.
            </p>
        </div>
        <a href="{{ route('stock-in.index') }}" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-300 dark:hover:bg-dark-700 transition-colors">
            &larr; Back to Logs
        </a>
    </div>

    @if ($errors->any())
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-sm space-y-1">
        <div class="font-bold flex items-center gap-2">
            <i class="fas fa-circle-exclamation"></i>
            <span>Please correct the errors below:</span>
        </div>
        <ul class="list-disc pl-5 text-xs space-y-0.5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('stock-in.store') }}" class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-6">
        @csrf

        <!-- Hidden Product ID Input -->
        <input type="hidden" name="Product_ID" id="selectedProductId" value="{{ old('Product_ID') }}" required>

        <!-- MODERN SEARCHABLE PRODUCT PICKER -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Target Product <span class="text-rose-500">*</span>
                </label>
                <span class="text-xs text-slate-400" id="pickerHelpText">Search or click to pick product</span>
            </div>

            <!-- Selected Product Preview Card (Hidden when none selected) -->
            <div id="selectedProductCard" class="hidden p-4 rounded-2xl bg-slate-50 dark:bg-dark-900/90 border-2 border-red-500/40 relative group transition-all">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-16 h-16 rounded-xl bg-slate-200 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center">
                            <img id="selectedProductImg" src="" alt="Product" class="w-full h-full object-cover hidden">
                            <div id="selectedProductImgPlaceholder" class="text-slate-400 text-xl">
                                <i class="fas fa-boxes-stacked"></i>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span id="selectedProductCategory" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-red-500/10 text-red-500 border border-red-500/20"></span>
                                <span id="selectedProductStockBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-md"></span>
                            </div>
                            <h3 id="selectedProductName" class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate"></h3>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-3">
                                <span>Current Cost: <strong id="selectedProductCostText" class="text-slate-700 dark:text-slate-300 font-mono">₱0.00</strong></span>
                                <span>&bull;</span>
                                <span>Current Retail: <strong id="selectedProductRetailText" class="text-emerald-500 font-mono">₱0.00</strong></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="showProductDropdown()" class="px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1.5 flex-shrink-0">
                        <i class="fas fa-arrows-rotate"></i>
                        <span>Change</span>
                    </button>
                </div>
            </div>

            <!-- Searchable Dropdown Container -->
            <div id="productDropdownContainer" class="relative">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="productSearchInput" placeholder="Type product name or category to search..."
                        class="w-full pl-11 pr-10 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500"
                        onfocus="openProductDropdown()" oninput="filterProductList()">
                    <button type="button" onclick="toggleProductDropdown()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200" id="dropdownChevron"></i>
                    </button>
                </div>

                <!-- Dropdown List Results -->
                <div id="productDropdownList" class="absolute z-30 left-0 right-0 mt-2 max-h-80 overflow-y-auto rounded-2xl bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800 hidden">
                    @foreach($products as $prod)
                    @php
                        $stock = (float) $prod->stock_quantity;
                        $cost = (float) $prod->cost_price;
                        $retail = (float) $prod->retail_price;
                        $img = $prod->image_url;
                        $categoryName = $prod->category->Name ?? 'General';
                    @endphp
                    <div class="product-option-row p-3 hover:bg-slate-50 dark:hover:bg-dark-800/80 cursor-pointer transition-colors flex items-center justify-between gap-3"
                        data-id="{{ $prod->ID }}"
                        data-name="{{ $prod->Name }}"
                        data-category="{{ $categoryName }}"
                        data-stock="{{ $stock }}"
                        data-cost="{{ $cost }}"
                        data-retail="{{ $retail }}"
                        data-img="{{ $img ?? '' }}"
                        onclick="selectProductFromList(this)">
                        
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-dark-800 border border-slate-200 dark:border-slate-700 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                @if($img)
                                <img src="{{ $img }}" alt="{{ $prod->Name }}" class="w-full h-full object-cover">
                                @else
                                <i class="fas fa-boxes-stacked text-slate-400 text-sm"></i>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-300">
                                        {{ $categoryName }}
                                    </span>
                                    @if($stock <= 0)
                                    <span class="text-[10px] font-bold text-rose-500 bg-rose-500/10 px-1.5 py-0.2 rounded">Out of stock</span>
                                    @elseif($stock <= 5)
                                    <span class="text-[10px] font-bold text-amber-500 bg-amber-500/10 px-1.5 py-0.2 rounded">{{ $stock }} in stock</span>
                                    @else
                                    <span class="text-[10px] font-semibold text-emerald-500 bg-emerald-500/10 px-1.5 py-0.2 rounded">{{ $stock }} in stock</span>
                                    @endif
                                </div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                    {{ $prod->Name }}
                                </div>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0 font-mono text-xs">
                            <div class="font-bold text-slate-900 dark:text-white">₱{{ number_format($retail, 2) }}</div>
                            <div class="text-[11px] text-slate-400">Cost: ₱{{ number_format($cost, 2) }}</div>
                        </div>
                    </div>
                    @endforeach
                    <div id="noMatchMessage" class="p-6 text-center text-xs text-slate-400 hidden">
                        <i class="fas fa-box-open text-2xl mb-2 opacity-50"></i>
                        <p>No products match your search.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUANTITY & PRICING GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 pt-2">
            <!-- Quantity Received with Stepper Buttons -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Quantity Received <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="Quantity" id="qtyInput" value="{{ old('Quantity', 1) }}" min="1" step="1" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                        oninput="recalculateSummary()">
                </div>
                <!-- Quick Stepper Pills -->
                <div class="flex items-center gap-1.5 pt-1">
                    <span class="text-[11px] text-slate-400 font-semibold mr-1">Quick:</span>
                    <button type="button" onclick="addQty(5)" class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-500 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">+5</button>
                    <button type="button" onclick="addQty(10)" class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-500 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">+10</button>
                    <button type="button" onclick="addQty(25)" class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-500 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">+25</button>
                    <button type="button" onclick="addQty(50)" class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-500 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">+50</button>
                </div>
            </div>

            <!-- Cost Price -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Unit Cost Price (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Cost_Price" id="costPriceInput" value="{{ old('Cost_Price', 0) }}" min="0" step="0.01" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                    oninput="recalculateSummary()">
                <p class="text-[11px] text-slate-400">Wholesale cost per piece from supplier</p>
            </div>

            <!-- Retail Selling Price -->
            <div class="space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Retail Selling Price (₱) <span class="text-rose-500">*</span>
                </label>
                <input type="number" name="Retail_Price" id="retailPriceInput" value="{{ old('Retail_Price', 0) }}" min="0" step="0.01" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-emerald-600 dark:text-emerald-400 focus:ring-2 focus:ring-red-500"
                    oninput="recalculateSummary()">
                <p class="text-[11px] text-slate-400">POS checkout selling price</p>
            </div>
        </div>

        <!-- LIVE SUMMARY & PROFIT MARGIN BADGE -->
        <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-100 to-slate-50 dark:from-dark-900/90 dark:to-dark-850 border border-slate-200 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-lg">
                    <i class="fas fa-calculator"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Batch Summary</div>
                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-3 mt-0.5">
                        <span>Total Cost: <strong id="totalBatchCostDisplay" class="font-mono text-red-500 font-black">₱0.00</strong></span>
                        <span>&bull;</span>
                        <span>Expected Revenue: <strong id="totalRevenueDisplay" class="font-mono text-emerald-500 font-black">₱0.00</strong></span>
                    </div>
                </div>
            </div>

            <div class="text-right flex items-center sm:block gap-2">
                <span class="text-xs text-slate-400 font-bold uppercase tracking-wider sm:block">Profit Margin:</span>
                <span id="marginBadge" class="px-2.5 py-1 rounded-xl text-xs font-black bg-emerald-500/15 text-emerald-500 border border-emerald-500/30">
                    ₱0.00 (0%)
                </span>
            </div>
        </div>

        <!-- PROCESSED BY (STAFF / ADMIN SELECTOR) -->
        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-700/60 space-y-3">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    <i class="fas fa-user-shield text-red-500 mr-1.5"></i> Processed / Received By
                </label>
                <span class="text-xs text-slate-400">Audit trail &amp; activity log</span>
            </div>

            <select name="User_ID" id="processedBySelect" class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-dark-850 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ (old('User_ID', auth()->id()) == $user->id) ? 'selected' : '' }}>
                    {{ $user->name }} ({{ $user->role ?? 'Staff' }}) &mdash; {{ $user->username }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('stock-in.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-bold text-sm transition-colors">
                Cancel
            </a>
            <button type="submit" id="submitBtn" class="px-7 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-sm shadow-lg shadow-red-600/25 transition-all flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>Record Stock-In Batch</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
const selectedIdInput = document.getElementById('selectedProductId');
const searchInput = document.getElementById('productSearchInput');
const dropdownList = document.getElementById('productDropdownList');
const chevron = document.getElementById('dropdownChevron');
const selectedCard = document.getElementById('selectedProductCard');
const dropdownContainer = document.getElementById('productDropdownContainer');

const qtyInput = document.getElementById('qtyInput');
const costInput = document.getElementById('costPriceInput');
const retailInput = document.getElementById('retailPriceInput');

const totalBatchCostDisplay = document.getElementById('totalBatchCostDisplay');
const totalRevenueDisplay = document.getElementById('totalRevenueDisplay');
const marginBadge = document.getElementById('marginBadge');

function toggleProductDropdown() {
    if (dropdownList.classList.contains('hidden')) {
        openProductDropdown();
    } else {
        closeProductDropdown();
    }
}

function openProductDropdown() {
    dropdownList.classList.remove('hidden');
    chevron.classList.add('rotate-180');
}

function closeProductDropdown() {
    dropdownList.classList.add('hidden');
    chevron.classList.remove('rotate-180');
}

function filterProductList() {
    const q = searchInput.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.product-option-row');
    let matches = 0;

    rows.forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const cat = (row.dataset.category || '').toLowerCase();
        if (name.includes(q) || cat.includes(q)) {
            row.classList.remove('hidden');
            matches++;
        } else {
            row.classList.add('hidden');
        }
    });

    const noMatch = document.getElementById('noMatchMessage');
    if (matches === 0) {
        noMatch.classList.remove('hidden');
    } else {
        noMatch.classList.add('hidden');
    }

    openProductDropdown();
}

function selectProductFromList(row) {
    const id = row.dataset.id;
    const name = row.dataset.name;
    const category = row.dataset.category;
    const stock = parseFloat(row.dataset.stock) || 0;
    const cost = parseFloat(row.dataset.cost) || 0;
    const retail = parseFloat(row.dataset.retail) || 0;
    const img = row.dataset.img;

    selectedIdInput.value = id;

    // Fill Selected Card
    document.getElementById('selectedProductName').textContent = name;
    document.getElementById('selectedProductCategory').textContent = category;

    const stockBadge = document.getElementById('selectedProductStockBadge');
    if (stock <= 0) {
        stockBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-500 border border-rose-500/20';
        stockBadge.textContent = 'Out of Stock (0)';
    } else if (stock <= 5) {
        stockBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-500 border border-amber-500/20';
        stockBadge.textContent = stock + ' units remaining';
    } else {
        stockBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-500 border border-emerald-500/20';
        stockBadge.textContent = stock + ' in stock';
    }

    document.getElementById('selectedProductCostText').textContent = '₱' + cost.toFixed(2);
    document.getElementById('selectedProductRetailText').textContent = '₱' + retail.toFixed(2);

    const imgEl = document.getElementById('selectedProductImg');
    const imgPlaceholder = document.getElementById('selectedProductImgPlaceholder');
    if (img) {
        imgEl.src = img;
        imgEl.classList.remove('hidden');
        imgPlaceholder.classList.add('hidden');
    } else {
        imgEl.classList.add('hidden');
        imgPlaceholder.classList.remove('hidden');
    }

    // Auto-fill cost & retail prices if empty or 0
    costInput.value = cost;
    retailInput.value = retail;

    // Switch view
    selectedCard.classList.remove('hidden');
    dropdownContainer.classList.add('hidden');
    closeProductDropdown();

    recalculateSummary();
}

function showProductDropdown() {
    selectedCard.classList.add('hidden');
    dropdownContainer.classList.remove('hidden');
    searchInput.value = '';
    filterProductList();
    setTimeout(() => searchInput.focus(), 50);
}

function addQty(amount) {
    const current = parseInt(qtyInput.value) || 0;
    qtyInput.value = Math.max(1, current + amount);
    recalculateSummary();
}

function recalculateSummary() {
    const qty = parseFloat(qtyInput.value) || 0;
    const cost = parseFloat(costInput.value) || 0;
    const retail = parseFloat(retailInput.value) || 0;

    const totalCost = qty * cost;
    const totalRevenue = qty * retail;
    const profit = totalRevenue - totalCost;
    const markupPct = cost > 0 ? ((retail - cost) / cost) * 100 : 0;

    totalBatchCostDisplay.textContent = '₱' + totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    totalRevenueDisplay.textContent = '₱' + totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    if (profit >= 0) {
        marginBadge.className = 'px-2.5 py-1 rounded-xl text-xs font-black bg-emerald-500/15 text-emerald-500 border border-emerald-500/30';
        marginBadge.textContent = '+₱' + profit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + markupPct.toFixed(1) + '% markup)';
    } else {
        marginBadge.className = 'px-2.5 py-1 rounded-xl text-xs font-black bg-rose-500/15 text-rose-500 border border-rose-500/30';
        marginBadge.textContent = '-₱' + Math.abs(profit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (Loss)';
    }
}

// Close dropdown on outside click
document.addEventListener('click', (e) => {
    if (!dropdownContainer.contains(e.target)) {
        closeProductDropdown();
    }
});

// If initial old Product_ID exists, pre-select it
document.addEventListener('DOMContentLoaded', () => {
    const initId = selectedIdInput.value;
    if (initId) {
        const row = document.querySelector(`.product-option-row[data-id="${initId}"]`);
        if (row) selectProductFromList(row);
    }
    recalculateSummary();
});
</script>
@endpush
@endsection