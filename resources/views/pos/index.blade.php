@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <!-- Top POS Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-cash-register text-red-500"></i>
                POS Cashier Terminal
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-slate-200/90 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 shadow-sm">
                <div class="w-6 h-6 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-user-tag"></i>
                </div>
                <label for="posCashierSelect" class="text-xs font-bold text-slate-500 dark:text-slate-400 select-none">Cashier:</label>
                <select id="posCashierSelect" onchange="syncCashierSelection(this.value)" class="bg-transparent text-xs font-black text-slate-900 dark:text-white border-none focus:ring-0 cursor-pointer pr-1">
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" class="bg-white dark:bg-[#0e1422] text-slate-900 dark:text-white py-1.5 font-medium" {{ auth()->id() == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->role ?? 'Staff' }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Main POS Grid: Left Catalog, Right Cart -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- LEFT: Products Catalog (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- Search & Filters -->
            <div class="glass-card rounded-2xl p-3 sm:p-4 border flex flex-col sm:flex-row items-center gap-3">
                <div class="relative w-full">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="posSearch" placeholder="Search product name..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                    <button type="button" onclick="filterCategory('ALL')" class="cat-filter-btn active px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-red-600 text-white shadow-sm cursor-pointer">
                        All
                    </button>
                    @foreach($categories as $c)
                    <button type="button" onclick="filterCategory('{{ $c->ID }}')" class="cat-filter-btn px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 cursor-pointer">
                        {{ $c->Name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 max-h-[680px] overflow-y-auto pr-1" id="productGrid">
                @foreach($products as $p)
                @php
                    $qty = $p->stock_quantity;
                    $price = $p->retail_price;
                    $allImages = $p->all_image_urls;
                    $imagesCount = count($allImages);
                    $imagesJson = json_encode($allImages);
                @endphp
                <div class="product-item glass-card rounded-2xl p-3 border flex flex-col justify-between cursor-pointer transition-all hover:scale-[1.02] hover:border-red-500/50 relative overflow-hidden group {{ $qty <= 0 ? 'opacity-60 pointer-events-none' : '' }}"
                    data-id="{{ $p->ID }}"
                    data-name="{{ $p->Name }}"
                    data-price="{{ $price }}"
                    data-stock="{{ $qty }}"
                    data-category="{{ $p->Category_ID }}"
                    onclick="addToCart({{ $p->ID }}, '{{ addslashes($p->Name) }}', {{ $price }}, {{ $qty }})">

                    <div>
                        <!-- Product Image Thumbnail with Expand Photo Button -->
                        <div class="w-full h-28 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 overflow-hidden mb-2.5 relative flex items-center justify-center">
                            @if($p->image_url)
                                <img src="{{ $p->image_url }}" alt="{{ $p->Name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full bg-gradient-to-tr from-slate-200 to-slate-100 dark:from-dark-850 dark:to-dark-800 flex items-center justify-center text-slate-400">
                                    <i class="fas fa-boxes-stacked text-2xl opacity-40"></i>
                                </div>
                            @endif

                            <!-- Stock Badge floating on image -->
                            <div class="absolute top-2 right-2">
                                @if($qty <= 0)
                                <span class="text-[10px] font-black uppercase text-rose-500 bg-rose-950/80 backdrop-blur-sm px-1.5 py-0.5 rounded border border-rose-500/30">Out of stock</span>
                                @elseif($qty <= 5)
                                <span class="text-[10px] font-bold text-amber-400 bg-amber-950/80 backdrop-blur-sm px-1.5 py-0.5 rounded border border-amber-500/30">{{ $qty }} left</span>
                                @else
                                <span class="text-[10px] font-semibold text-emerald-400 bg-emerald-950/80 backdrop-blur-sm px-1.5 py-0.5 rounded border border-emerald-500/30">{{ $qty }} stock</span>
                                @endif
                            </div>

                            <!-- Expand Gallery Button on card -->
                            @if($imagesCount > 0)
                            <button type="button" onclick="openPosGallery({{ $imagesJson }}, '{{ addslashes($p->Name) }}', event)" 
                                class="absolute bottom-2 left-2 px-2 py-1 rounded-lg bg-black/70 hover:bg-red-700 text-white text-[10px] font-bold backdrop-blur-sm transition-all flex items-center gap-1 shadow-sm"
                                title="Expand and view all photos">
                                <i class="fas fa-expand text-[9px]"></i>
                                <span>{{ $imagesCount }}</span>
                            </button>
                            @endif
                        </div>

                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-500/10 text-red-500 border border-red-500/20">
                                {{ $p->category->Name ?? 'General' }}
                            </span>
                        </div>
                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white leading-snug line-clamp-2">
                            {{ $p->Name }}
                        </h4>
                    </div>

                    <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                        <div class="font-black font-display text-sm sm:text-base text-emerald-600 dark:text-emerald-400">
                            ₱{{ number_format($price, 2) }}
                        </div>
                        <button type="button" class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-500 text-white flex items-center justify-center text-xs shadow-sm transition-transform active:scale-95">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- RIGHT: Live Cart & Checkout (5 cols) -->
        <div class="lg:col-span-5">
            <div class="glass-card rounded-2xl p-5 border shadow-lg sticky top-24 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-basket-shopping text-red-500 text-lg"></i>
                        <h3 class="font-display font-black text-lg text-slate-900 dark:text-white">Customer Cart</h3>
                    </div>
                    <button type="button" onclick="promptClearCart()" class="text-xs font-bold text-rose-500 hover:text-rose-400 cursor-pointer">
                        <i class="fas fa-trash-can mr-1"></i> Clear
                    </button>
                </div>

                <!-- Items list -->
                <div class="space-y-2.5 max-h-[300px] overflow-y-auto pr-1" id="cartContainer">
                    <div id="cartEmptyMessage" class="text-center py-10 text-slate-400 text-sm">
                        <i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i>
                        <p>No items in cart yet.<br>Click any product from catalog to add.</p>
                    </div>
                </div>

                <!-- Summary & Payment -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Total Items</span>
                        <strong id="cartTotalItems" class="text-slate-800 dark:text-slate-200">0</strong>
                    </div>
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Grand Total</span>
                        <strong id="cartGrandTotal" class="font-display font-black text-2xl text-emerald-600 dark:text-emerald-400">₱0.00</strong>
                    </div>

                    <!-- Cashier / Processed By -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Cashier / Processed By
                            </label>
                            <span class="text-[10px] text-slate-400">Sales audit log</span>
                        </div>
                        <select id="checkoutCashierSelect" onchange="syncCashierSelection(this.value)" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-red-500">
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" class="bg-white dark:bg-[#0e1422] text-slate-900 dark:text-white py-1.5" {{ auth()->id() == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->role ?? 'Staff' }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Payment Method
                        </label>
                        <select id="paymentMethodSelect" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-red-500">
                            @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->ID }}" class="bg-white dark:bg-[#0e1422] text-slate-900 dark:text-white py-1.5">{{ $pm->Name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Amount Tendered -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Cash / Amount Tendered (₱)
                        </label>
                        <input type="number" id="amountTendered" min="0" step="0.01" placeholder="0.00"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-red-500"
                            oninput="calculateChange()">
                    </div>

                    <!-- Change calculation -->
                    <div class="flex items-center justify-between text-sm p-3 rounded-xl bg-slate-100 dark:bg-dark-900/60 border border-slate-200 dark:border-slate-700">
                        <span class="font-medium text-slate-500 dark:text-slate-400">Change Due:</span>
                        <strong id="changeDue" class="font-bold text-base text-red-500">₱0.00</strong>
                    </div>

                    <!-- Checkout Submit Button -->
                    <button type="button" id="checkoutBtn" onclick="processCheckout()" disabled
                        class="w-full py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-black font-display text-base tracking-wide shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>Charge &amp; Save Sale</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- EXPANDED LIGHTBOX GALLERY MODAL FOR POS -->
<div id="posGalleryModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md hidden items-center justify-center p-4">
    <div class="w-full max-w-2xl bg-white dark:bg-[#0c1222] border border-slate-200 dark:border-slate-700 rounded-3xl p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <h3 id="posGalleryTitle" class="text-base font-bold font-display text-slate-900 dark:text-white truncate"></h3>
                <span id="posGalleryCounter" class="text-xs px-2 py-0.5 rounded-full bg-red-500/15 text-red-500 font-bold"></span>
            </div>
            <button type="button" onclick="closePosGallery()" class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl flex items-center justify-center cursor-pointer">&times;</button>
        </div>

        <div class="relative bg-slate-100 dark:bg-dark-900 rounded-2xl h-80 flex items-center justify-center p-4 overflow-hidden">
            <img id="posGalleryMainImg" src="" class="max-h-full max-w-full object-contain drop-shadow-xl transition-all duration-300">

            <button type="button" id="posPrevGalleryBtn" onclick="prevPosGalleryImage()" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-110 cursor-pointer">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" id="posNextGalleryBtn" onclick="nextPosGalleryImage()" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-110 cursor-pointer">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div class="flex items-center justify-center gap-2.5 pt-1 overflow-x-auto" id="posGalleryThumbnailsStrip"></div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-md w-full p-6 border shadow-2xl space-y-4">
        <div class="text-center pb-3 border-b border-slate-200 dark:border-slate-800">
            <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-500 mx-auto flex items-center justify-center text-xl mb-2">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="font-display font-black text-xl text-slate-900 dark:text-white">Transaction Complete!</h3>
            <p class="text-xs text-slate-400 mt-0.5" id="receiptSaleIdText">Transaction recorded successfully</p>
        </div>

        <div class="space-y-2 text-sm bg-slate-50 dark:bg-dark-900/60 p-4 rounded-xl border border-slate-200 dark:border-slate-800 font-mono text-xs" id="receiptContent">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="button" onclick="printReceipt()" class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-print"></i>
                <span>Print Thermal Receipt</span>
            </button>
            <button type="button" onclick="closeReceiptModal()" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 font-bold text-sm">
                Done
            </button>
        </div>
    </div>
</div>

<!-- Cart Removal Confirmation Modal -->
<div id="cartConfirmModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 transform transition-all">
        <div class="flex items-start gap-3.5">
            <div id="cartConfirmIconWrapper" class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-500 flex items-center justify-center text-xl flex-shrink-0">
                <i id="cartConfirmIcon" class="fas fa-trash-can"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="cartConfirmTitle" class="font-display font-black text-lg text-slate-900 dark:text-white leading-tight">Remove Item?</h3>
                <p id="cartConfirmSubtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-1">Are you sure you want to remove this item from the active cart?</p>
            </div>
            <button type="button" onclick="closeCartConfirmModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl -mr-1 -mt-1 cursor-pointer">
                &times;
            </button>
        </div>

        <!-- Item Detail Card -->
        <div id="cartConfirmItemBox" class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
            <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 dark:text-white text-sm" id="cartConfirmItemName">-</span>
                <span class="font-bold text-rose-500 text-sm" id="cartConfirmItemTotal">₱0.00</span>
            </div>
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px]">
                <span id="cartConfirmItemQty">Qty: 1</span>
                <span id="cartConfirmItemPrice">₱0.00 each</span>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="button" onclick="closeCartConfirmModal()" class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm transition-colors cursor-pointer">
                Cancel
            </button>
            <button type="button" id="cartConfirmAcceptBtn" onclick="executeCartRemoval()" class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer">
                <i class="fas fa-trash-can"></i>
                <span id="cartConfirmAcceptText">Yes, Remove</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let cart = {};
let lastCompletedSaleId = null;

function addToCart(id, name, price, maxStock) {
    if (cart[id]) {
        if (cart[id].qty + 1 > maxStock) {
            alert('Cannot exceed available inventory (' + maxStock + ' units).');
            return;
        }
        cart[id].qty += 1;
    } else {
        cart[id] = { id: id, name: name, price: price, maxStock: maxStock, qty: 1 };
    }
    renderCart();
}

let pendingCartRemoval = null;

function promptRemoveCartItem(id) {
    const item = cart[id];
    if (!item) return;

    pendingCartRemoval = { type: 'item', id: id };
    
    document.getElementById('cartConfirmIcon').className = 'fas fa-trash-can';
    document.getElementById('cartConfirmTitle').innerText = 'Remove Item from Cart?';
    document.getElementById('cartConfirmSubtitle').innerText = 'Are you sure you want to remove this item from the active cart?';
    document.getElementById('cartConfirmItemName').innerText = item.name;
    document.getElementById('cartConfirmItemQty').innerText = 'Quantity: ' + item.qty;
    document.getElementById('cartConfirmItemPrice').innerText = 'Unit Price: ₱' + Number(item.price).toFixed(2);
    document.getElementById('cartConfirmItemTotal').innerText = '₱' + (item.price * item.qty).toFixed(2);
    document.getElementById('cartConfirmItemBox').classList.remove('hidden');
    document.getElementById('cartConfirmAcceptText').innerText = 'Yes, Remove';

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function promptClearCart() {
    const keys = Object.keys(cart);
    if (keys.length === 0) return;

    pendingCartRemoval = { type: 'clear' };

    let totalItems = 0;
    let grandTotal = 0;
    keys.forEach(k => {
        totalItems += cart[k].qty;
        grandTotal += (cart[k].price * cart[k].qty);
    });

    document.getElementById('cartConfirmIcon').className = 'fas fa-triangle-exclamation';
    document.getElementById('cartConfirmTitle').innerText = 'Clear Entire Cart?';
    document.getElementById('cartConfirmSubtitle').innerText = 'Are you sure you want to remove all items from the current cart?';
    document.getElementById('cartConfirmItemName').innerText = `${keys.length} product(s) (${totalItems} total units)`;
    document.getElementById('cartConfirmItemQty').innerText = 'Items: ' + totalItems;
    document.getElementById('cartConfirmItemPrice').innerText = 'All items will be discarded';
    document.getElementById('cartConfirmItemTotal').innerText = '₱' + grandTotal.toFixed(2);
    document.getElementById('cartConfirmItemBox').classList.remove('hidden');
    document.getElementById('cartConfirmAcceptText').innerText = 'Yes, Clear All';

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCartConfirmModal() {
    pendingCartRemoval = null;
    const modal = document.getElementById('cartConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function executeCartRemoval() {
    if (!pendingCartRemoval) return;

    if (pendingCartRemoval.type === 'item') {
        delete cart[pendingCartRemoval.id];
    } else if (pendingCartRemoval.type === 'clear') {
        cart = {};
    }

    closeCartConfirmModal();
    renderCart();
}

// Global cart count getter for layout/logout validation
window.getPosCartItemCount = function() {
    return Object.keys(cart).length;
};

function updateCartQty(id, delta) {
    if (!cart[id]) return;
    const newQty = cart[id].qty + delta;
    if (newQty <= 0) {
        promptRemoveCartItem(id);
        return;
    } else if (newQty > cart[id].maxStock) {
        alert('Cannot exceed available inventory (' + cart[id].maxStock + ' units).');
        return;
    } else {
        cart[id].qty = newQty;
    }
    renderCart();
}

function removeCartItem(id) {
    promptRemoveCartItem(id);
}

function clearCart() {
    promptClearCart();
}

function renderCart() {
    const container = document.getElementById('cartContainer');
    const itemsCountEl = document.getElementById('cartTotalItems');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    const keys = Object.keys(cart);
    if (keys.length === 0) {
        container.innerHTML = `<div id="cartEmptyMessage" class="text-center py-10 text-slate-400 text-sm"><i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i><p>No items in cart yet.<br>Click any product from catalog to add.</p></div>`;
        itemsCountEl.textContent = '0';
        grandTotalEl.textContent = '₱0.00';
        checkoutBtn.disabled = true;
        calculateChange();
        return;
    }

    let totalQty = 0;
    let grandTotal = 0;
    let html = '';

    keys.forEach(k => {
        const item = cart[k];
        const lineTotal = item.price * item.qty;
        totalQty += item.qty;
        grandTotal += lineTotal;

        html += `
        <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-800/80 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between gap-2">
            <div class="flex-1 min-w-0">
                <div class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">${item.name}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">₱${item.price.toFixed(2)} &times; ${item.qty} = <strong class="text-emerald-500">₱${lineTotal.toFixed(2)}</strong></div>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" onclick="updateCartQty(${item.id}, -1)" class="w-6 h-6 rounded bg-slate-200 dark:bg-dark-700 hover:bg-slate-300 text-slate-700 dark:text-slate-200 text-xs font-bold">&minus;</button>
                <span class="w-6 text-center font-bold text-xs text-slate-900 dark:text-white">${item.qty}</span>
                <button type="button" onclick="updateCartQty(${item.id}, 1)" class="w-6 h-6 rounded bg-slate-200 dark:bg-dark-700 hover:bg-slate-300 text-slate-700 dark:text-slate-200 text-xs font-bold">&plus;</button>
                <button type="button" onclick="promptRemoveCartItem(${item.id})" class="ml-1 text-slate-400 hover:text-rose-500 text-xs p-1 cursor-pointer" title="Remove item"><i class="fas fa-times"></i></button>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    itemsCountEl.textContent = totalQty;
    grandTotalEl.textContent = '₱' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    checkoutBtn.disabled = false;
    calculateChange();
}

function calculateChange() {
    const tenderedInput = document.getElementById('amountTendered');
    const changeEl = document.getElementById('changeDue');
    
    let grandTotal = 0;
    Object.values(cart).forEach(i => grandTotal += (i.price * i.qty));

    const tendered = parseFloat(tenderedInput.value) || 0;
    const change = Math.max(0, tendered - grandTotal);

    changeEl.textContent = '₱' + change.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function syncCashierSelection(val) {
    const topSelect = document.getElementById('posCashierSelect');
    const cartSelect = document.getElementById('checkoutCashierSelect');
    if (topSelect) topSelect.value = val;
    if (cartSelect) cartSelect.value = val;
}

function processCheckout() {
    const keys = Object.keys(cart);
    if (keys.length === 0) return;

    let grandTotal = 0;
    const itemsPayload = keys.map(k => {
        grandTotal += (cart[k].price * cart[k].qty);
        return {
            product_id: cart[k].id,
            quantity: cart[k].qty
        };
    });

    const paymentMethodId = document.getElementById('paymentMethodSelect').value;
    const cashierId = document.getElementById('checkoutCashierSelect')?.value || document.getElementById('posCashierSelect')?.value;
    const tendered = parseFloat(document.getElementById('amountTendered').value) || grandTotal;

    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Processing...`;

    fetch("{{ route('pos.checkout') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            payment_method_id: paymentMethodId,
            user_id: cashierId,
            amount_tendered: tendered,
            items: itemsPayload
        })
    })
    .then(r => r.json())
    .then(data => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = `<i class="fas fa-check-circle"></i> Charge &amp; Save Sale`;

        if (data.success) {
            lastCompletedSaleId = data.sale_id;
            document.getElementById('receiptSaleIdText').textContent = `Sale #` + data.sale_id + ` recorded successfully`;
            
            let receiptHtml = `
                <div class="text-center font-bold text-sm border-b pb-2 mb-2">PAOLO PAOLO MATTING & ACCESSORIES</div>
                <div>Date: ${data.date}</div>
                <div>Cashier: <strong>${data.cashier}</strong> (${data.cashier_role || 'Staff'})</div>
                <div>Payment Method: ${data.payment_method}</div>
                <div class="border-t my-2"></div>
            `;
            data.items.forEach(it => {
                receiptHtml += `<div class="flex justify-between"><span>${it.name} (x${it.quantity})</span><span>₱${it.total.toFixed(2)}</span></div>`;
            });
            receiptHtml += `
                <div class="border-t my-2"></div>
                <div class="flex justify-between font-bold text-sm"><span>TOTAL:</span><span>₱${data.total.toFixed(2)}</span></div>
                <div class="flex justify-between"><span>Amount Tendered:</span><span>₱${data.tendered.toFixed(2)}</span></div>
                <div class="flex justify-between font-bold text-emerald-500"><span>Change:</span><span>₱${data.change.toFixed(2)}</span></div>
            `;

            document.getElementById('receiptContent').innerHTML = receiptHtml;
            document.getElementById('receiptModal').classList.remove('hidden');

            clearCart();
            document.getElementById('amountTendered').value = '';
        } else {
            alert(data.message || 'Error completing sale.');
        }
    })
    .catch(err => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = `<i class="fas fa-check-circle"></i> Charge &amp; Save Sale`;
        alert('Network or server error processing sale.');
    });
}

function printReceipt() {
    if (lastCompletedSaleId) {
        window.open("{{ url('pos/receipt') }}/" + lastCompletedSaleId, '_blank');
    }
}

function closeReceiptModal() {
    document.getElementById('receiptModal').classList.add('hidden');
    window.location.reload();
}

document.getElementById('posSearch').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(el => {
        const name = el.getAttribute('data-name').toLowerCase();
        if (name.includes(q)) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
});

function filterCategory(catId) {
    document.querySelectorAll('.cat-filter-btn').forEach(b => {
        b.classList.remove('bg-red-600', 'text-white');
        b.classList.add('bg-slate-200', 'dark:bg-dark-800', 'text-slate-700', 'dark:text-slate-300');
    });
    event.currentTarget.classList.remove('bg-slate-200', 'dark:bg-dark-800', 'text-slate-700', 'dark:text-slate-300');
    event.currentTarget.classList.add('bg-red-600', 'text-white');

    document.querySelectorAll('.product-item').forEach(el => {
        if (catId === 'ALL' || el.getAttribute('data-category') === catId) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
}

// Lightbox Gallery logic for POS
let currentPosImages = [];
let currentPosIndex = 0;

function openPosGallery(images, title, event) {
    if (event) event.stopPropagation();
    if (!images || images.length === 0) return;

    currentPosImages = images;
    currentPosIndex = 0;
    document.getElementById('posGalleryTitle').innerText = title;
    updatePosGalleryDisplay();

    const modal = document.getElementById('posGalleryModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closePosGallery() {
    const modal = document.getElementById('posGalleryModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function updatePosGalleryDisplay() {
    if (!currentPosImages || currentPosImages.length === 0) return;
    document.getElementById('posGalleryMainImg').src = currentPosImages[currentPosIndex];
    document.getElementById('posGalleryCounter').innerText = `${currentPosIndex + 1} / ${currentPosImages.length}`;

    const prevBtn = document.getElementById('posPrevGalleryBtn');
    const nextBtn = document.getElementById('posNextGalleryBtn');
    if (currentPosImages.length <= 1) {
        prevBtn.classList.add('hidden');
        nextBtn.classList.add('hidden');
    } else {
        prevBtn.classList.remove('hidden');
        nextBtn.classList.remove('hidden');
    }

    const strip = document.getElementById('posGalleryThumbnailsStrip');
    strip.innerHTML = '';

    currentPosImages.forEach((url, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `w-12 h-12 rounded-xl border-2 overflow-hidden transition-all flex-shrink-0 cursor-pointer ${i === currentPosIndex ? 'border-red-500 scale-105 shadow-md shadow-red-600/30' : 'border-slate-300 dark:border-slate-700 opacity-60 hover:opacity-100'}`;
        btn.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
        btn.onclick = (e) => {
            e.stopPropagation();
            currentPosIndex = i;
            updatePosGalleryDisplay();
        };
        strip.appendChild(btn);
    });
}

function prevPosGalleryImage() {
    if (currentPosIndex > 0) currentPosIndex--;
    else currentPosIndex = currentPosImages.length - 1;
    updatePosGalleryDisplay();
}

function nextPosGalleryImage() {
    if (currentPosIndex < currentPosImages.length - 1) currentPosIndex++;
    else currentPosIndex = 0;
    updatePosGalleryDisplay();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePosGallery();
        closeCartConfirmModal();
    }
});
</script>
@endpush