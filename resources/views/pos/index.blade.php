@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-cash-register text-red-500"></i>
                POS Cashier Terminal
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Click the information icon on a product to view its description and inventory details.
            </p>
        </div>

        <div class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-slate-200/90 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 shadow-sm">
            <div class="w-6 h-6 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center text-xs">
                <i class="fas fa-user-tag"></i>
            </div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Cashier:</span>
            <span class="text-xs font-black text-slate-900 dark:text-white">
                {{ auth()->user()->name }}
                ({{ auth()->user()->role ?? 'Staff' }})
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <div class="lg:col-span-7 space-y-4">
            <div class="glass-card rounded-2xl p-3 sm:p-4 border space-y-3">
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative w-full">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text"
                            id="posSearch"
                            placeholder="Search name, description or category..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div class="flex items-center gap-1 p-1 rounded-xl bg-slate-200 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 flex-shrink-0">
                        <button type="button"
                            id="gridViewBtn"
                            onclick="setProductView('grid')"
                            class="px-3 py-2 rounded-lg bg-red-600 text-white text-xs font-bold transition-all"
                            title="Grid View">
                            <i class="fas fa-grip"></i>
                        </button>

                        <button type="button"
                            id="listViewBtn"
                            onclick="setProductView('list')"
                            class="px-3 py-2 rounded-lg text-slate-600 dark:text-slate-300 text-xs font-bold transition-all"
                            title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full overflow-x-auto pb-1">
                    <button type="button"
                        onclick="filterCategory('ALL', this)"
                        class="cat-filter-btn px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-red-600 text-white shadow-sm">
                        All
                    </button>

                    @foreach($categories as $c)
                    <button type="button"
                        onclick="filterCategory('{{ $c->ID }}', this)"
                        class="cat-filter-btn px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300">
                        {{ $c->Name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div id="productGrid"
                data-view="grid"
                class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 max-h-[680px] overflow-y-auto pr-1 transition-all">

                @foreach($products as $p)
                @php
                    $qty = (float) $p->stock_quantity;
                    $price = (float) $p->retail_price;
                    $allImages = $p->all_image_urls;
                    $imagesCount = count($allImages);
                    $imagesJson = json_encode($allImages);

                    $sellableBatches = $p->stockIns
                        ->filter(function ($batch) {
                            $remaining = (float) ($batch->Remaining_Quantity ?? 0);
                            $condition = $batch->Condition ?? 'Good';

                            $expirationOkay =
                                !$batch->Has_Expiration ||
                                !$batch->Expiration_Date ||
                                $batch->Expiration_Date->gte(today());

                            return $remaining > 0 &&
                                $condition === 'Good' &&
                                $expirationOkay;
                        })
                        ->sortBy('ID')
                        ->values();

                    $nextExpiry = $sellableBatches
                        ->filter(function ($batch) {
                            return $batch->Has_Expiration &&
                                $batch->Expiration_Date;
                        })
                        ->sortBy('Expiration_Date')
                        ->first();

                    $oldestBatch = $sellableBatches->first();
                @endphp

                <div class="product-item product-card glass-card rounded-2xl p-3 border flex flex-col justify-between cursor-pointer transition-all hover:scale-[1.02] hover:border-red-500/50 relative overflow-hidden group {{ $qty <= 0 ? 'opacity-60' : '' }}"
                    data-id="{{ $p->ID }}"
                    data-name="{{ $p->Name }}"
                    data-description="{{ $p->Description ?? '' }}"
                    data-price="{{ $price }}"
                    data-stock="{{ $qty }}"
                    data-category="{{ $p->Category_ID }}"
                    data-category-name="{{ $p->category->Name ?? 'General' }}"
                    data-next-expiration="{{ $nextExpiry?->Expiration_Date?->format('M d, Y') ?? 'No expiration' }}"
                    data-batch-count="{{ $sellableBatches->count() }}"
                    data-oldest-batch="{{ $oldestBatch?->created_at?->format('M d, Y') ?? 'N/A' }}"
                    onclick='addToCart({{ $p->ID }}, @json($p->Name), {{ $price }}, {{ $qty }})'>

                    <div>
                        <div class="product-image w-full h-28 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 overflow-hidden mb-2.5 relative flex items-center justify-center">
                            @if($p->image_url)
                            <img src="{{ $p->image_url }}"
                                alt="{{ $p->Name }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                            <div class="w-full h-full bg-gradient-to-tr from-slate-200 to-slate-100 dark:from-dark-850 dark:to-dark-800 flex items-center justify-center text-slate-400">
                                <i class="fas fa-boxes-stacked text-2xl opacity-40"></i>
                            </div>
                            @endif

                            @if($imagesCount > 0)
                            <button type="button"
                                onclick='openPosGallery({!! $imagesJson !!}, @js($p->Name), event)'
                                class="absolute bottom-2 left-2 px-2 py-1 rounded-lg bg-black/70 hover:bg-red-700 text-white text-[10px] font-bold backdrop-blur-sm transition-all flex items-center gap-1 shadow-sm"
                                title="View all photos">
                                <i class="fas fa-expand text-[9px]"></i>
                                <span>{{ $imagesCount }}</span>
                            </button>
                            @endif

                            <button type="button"
                                onclick="openProductInfo(this.closest('.product-item'), event)"
                                class="absolute top-2 right-2 w-8 h-8 rounded-lg bg-black/65 hover:bg-red-600 text-white flex items-center justify-center text-xs backdrop-blur-sm transition-all shadow-sm"
                                title="View product details">
                                <i class="fas fa-circle-info"></i>
                            </button>
                        </div>

                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-500/10 text-red-500 border border-red-500/20">
                                {{ $p->category->Name ?? 'General' }}
                            </span>

                            @if($nextExpiry)
                            <span class="text-[9px] font-bold text-amber-500" title="Nearest expiration">
                                <i class="fas fa-calendar"></i>
                                {{ $nextExpiry->Expiration_Date->format('M d') }}
                            </span>
                            @endif
                        </div>

                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white leading-snug line-clamp-2">
                            {{ $p->Name }}
                        </h4>

                        @if($p->Description)
                        <p class="product-description-preview text-[11px] text-slate-400 line-clamp-2 mt-1">
                            {{ $p->Description }}
                        </p>
                        @endif
                    </div>

                    <div class="mt-2">
                        @if($qty <= 0)
                        <span class="inline-block text-[10px] font-black uppercase text-rose-500 bg-rose-500/10 px-1.5 py-0.5 rounded border border-rose-500/30">
                            Out of stock
                        </span>
                        @elseif($qty <= 5)
                        <span class="inline-block text-[10px] font-bold text-amber-500 bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-500/30">
                            {{ number_format($qty, 0) }} left
                        </span>
                        @else
                        <span class="inline-block text-[10px] font-semibold text-emerald-500 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/30">
                            {{ number_format($qty, 0) }} stock
                        </span>
                        @endif
                    </div>

                    <div class="product-footer mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                        <div class="font-black font-display text-sm sm:text-base text-emerald-600 dark:text-emerald-400">
                            ₱{{ number_format($price, 2) }}
                        </div>

                        <button type="button"
                            {{ $qty <= 0 ? 'disabled' : '' }}
                            class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-500 disabled:bg-slate-400 text-white flex items-center justify-center text-xs shadow-sm transition-transform active:scale-95">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="glass-card rounded-2xl p-5 border shadow-lg sticky top-24 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-basket-shopping text-red-500 text-lg"></i>
                        <h3 class="font-display font-black text-lg text-slate-900 dark:text-white">
                            Customer Cart
                        </h3>
                    </div>

                    <button type="button" onclick="promptClearCart()"
                        class="text-xs font-bold text-rose-500 hover:text-rose-400">
                        <i class="fas fa-trash-can mr-1"></i>
                        Clear
                    </button>
                </div>

                <div id="cartContainer"
                    class="space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
                    <div class="text-center py-10 text-slate-400 text-sm">
                        <i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i>
                        <p>
                            No items in cart yet.<br>
                            Click any in-stock product to add.
                        </p>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Total Items</span>
                        <strong id="cartTotalItems" class="text-slate-800 dark:text-slate-200">0</strong>
                    </div>

                    <div class="flex items-center justify-between text-lg">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Grand Total</span>
                        <strong id="cartGrandTotal"
                            class="font-display font-black text-2xl text-emerald-600 dark:text-emerald-400">
                            ₱0.00
                        </strong>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Payment Method
                        </label>
                        <select id="paymentMethodSelect"
                            class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->ID }}">{{ $method->Name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                            Amount Received (₱)
                        </label>
                        <input type="number" id="amountTendered"
                            min="0" step="0.01" inputmode="decimal"
                            oninput="calculateChange()"
                            class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-lg font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                            placeholder="0.00">
                    </div>

                    <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Change Due</span>
                        <strong id="changeDue" class="text-lg font-black text-emerald-500">₱0.00</strong>
                    </div>

                    <button type="button" id="checkoutBtn"
                        onclick="processCheckout()" disabled
                        class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-black text-sm shadow-lg shadow-red-600/20 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        Charge &amp; Save Sale
                    </button>

                    <p class="text-[10px] text-center text-slate-400">
                        Inventory is deducted from the oldest sellable stock batch first (FIFO).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="productInfoModal"
    class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-lg w-full rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-2xl space-y-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div id="productInfoCategory" class="text-xs font-bold text-red-500 mb-1">General</div>
                <h3 id="productInfoName" class="text-xl font-black text-slate-900 dark:text-white">Product</h3>
            </div>
            <button type="button" onclick="closeProductInfo()"
                class="text-2xl text-slate-400 hover:text-slate-700 dark:hover:text-white leading-none">&times;</button>
        </div>

        <div>
            <div class="text-xs uppercase font-bold text-slate-400 mb-1.5">
                Description / Other Details
            </div>
            <p id="productInfoDescription"
                class="text-sm leading-relaxed text-slate-700 dark:text-slate-300 whitespace-pre-line max-h-44 overflow-y-auto">
                No description provided.
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Price</div>
                <div id="productInfoPrice" class="font-black text-emerald-500 mt-1">₱0.00</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Available</div>
                <div id="productInfoStock" class="font-black text-slate-900 dark:text-white mt-1">0 units</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Next Expiry</div>
                <div id="productInfoExpiry" class="font-bold text-xs text-amber-500 mt-1">No expiration</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Sellable Batches</div>
                <div id="productInfoBatchCount" class="font-black text-slate-900 dark:text-white mt-1">0</div>
            </div>

            <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 sm:col-span-2">
                <div class="text-[10px] uppercase text-slate-400 font-bold">Oldest Sellable Batch</div>
                <div id="productInfoOldestBatch" class="font-bold text-slate-700 dark:text-slate-300 mt-1">N/A</div>
            </div>
        </div>

        <div class="p-3 rounded-xl bg-red-500/5 border border-red-500/20 text-xs text-slate-600 dark:text-slate-300">
            <i class="fas fa-layer-group text-red-500 mr-1.5"></i>
            FIFO is active: checkout consumes the oldest available good/non-expired batch first.
        </div>
    </div>
</div>

<div id="posGalleryModal"
    class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-3xl w-full rounded-2xl border border-slate-700 p-5 shadow-2xl">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h3 id="posGalleryTitle" class="font-black text-lg text-slate-900 dark:text-white">Product Photos</h3>
                <div id="posGalleryCounter" class="text-xs text-slate-400 mt-0.5">1 / 1</div>
            </div>
            <button type="button" onclick="closePosGallery()"
                class="text-2xl text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="relative rounded-2xl overflow-hidden bg-black/30 min-h-[320px] flex items-center justify-center">
            <img id="posGalleryMainImg" src="" alt="Product"
                class="max-h-[520px] w-full object-contain">

            <button type="button" id="posPrevGalleryBtn"
                onclick="prevPosGalleryImage()"
                class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 text-white">
                <i class="fas fa-chevron-left"></i>
            </button>

            <button type="button" id="posNextGalleryBtn"
                onclick="nextPosGalleryImage()"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 text-white">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

        <div id="posGalleryThumbnailsStrip"
            class="flex items-center gap-2 overflow-x-auto pt-4"></div>
    </div>
</div>

<div id="receiptModal"
    class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 shadow-2xl">
        <div class="text-center mb-4">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/15 text-emerald-500 flex items-center justify-center text-xl">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="font-black text-xl text-slate-900 dark:text-white mt-2">Sale Completed</h3>
            <p id="receiptSaleIdText" class="text-xs text-slate-400"></p>
        </div>

        <div id="receiptContent"
            class="p-4 rounded-xl bg-white dark:bg-dark-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 font-mono"></div>

        <div class="flex items-center gap-3 pt-4">
            <button type="button" onclick="printReceipt()"
                class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-print"></i>
                Print Thermal Receipt
            </button>
            <button type="button" onclick="closeReceiptModal()"
                class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 font-bold text-sm">
                Done
            </button>
        </div>
    </div>
</div>

<div id="cartConfirmModal"
    class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-500 flex items-center justify-center text-xl flex-shrink-0">
                <i id="cartConfirmIcon" class="fas fa-trash-can"></i>
            </div>

            <div class="flex-1 min-w-0">
                <h3 id="cartConfirmTitle"
                    class="font-display font-black text-lg text-slate-900 dark:text-white">
                    Remove Item?
                </h3>
                <p id="cartConfirmSubtitle"
                    class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Are you sure?
                </p>
            </div>

            <button type="button" onclick="closeCartConfirmModal()"
                class="text-slate-400 hover:text-white text-xl">&times;</button>
        </div>

        <div id="cartConfirmItemBox"
            class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
            <div class="flex items-center justify-between gap-2">
                <span id="cartConfirmItemName"
                    class="font-bold text-slate-900 dark:text-white text-sm truncate">-</span>
                <span id="cartConfirmItemTotal"
                    class="font-bold text-rose-500 text-sm">₱0.00</span>
            </div>

            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-[11px]">
                <span id="cartConfirmItemQty">Qty: 1</span>
                <span id="cartConfirmItemPrice">₱0.00 each</span>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="button" onclick="closeCartConfirmModal()"
                class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm">
                Cancel
            </button>
            <button type="button" onclick="executeCartRemoval()"
                class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs sm:text-sm">
                <i class="fas fa-trash-can mr-1"></i>
                <span id="cartConfirmAcceptText">Yes, Remove</span>
            </button>
        </div>
    </div>
</div>

<style>
    #productGrid.list-view {
        grid-template-columns: 1fr !important;
    }

    #productGrid.list-view .product-card {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) auto;
        align-items: center;
        gap: 16px;
        min-height: 120px;
    }

    #productGrid.list-view .product-card > div:first-child {
        display: contents;
    }

    #productGrid.list-view .product-image {
        width: 120px;
        height: 100px;
        margin-bottom: 0;
        grid-column: 1;
        grid-row: 1 / span 3;
    }

    #productGrid.list-view .product-card > div:first-child > div:nth-child(2) {
        grid-column: 2;
        grid-row: 1;
        align-self: end;
        margin-bottom: 4px;
    }

    #productGrid.list-view .product-card > div:first-child > h4 {
        grid-column: 2;
        grid-row: 2;
        align-self: start;
        font-size: 1rem;
        line-height: 1.35rem;
    }

    #productGrid.list-view .product-description-preview {
        grid-column: 2;
        grid-row: 3;
    }

    #productGrid.list-view .product-footer {
        grid-column: 3;
        grid-row: 1 / span 3;
        border-top: 0;
        padding-top: 0;
        margin-top: 0;
        gap: 12px;
        min-width: 120px;
    }

    @media (max-width: 640px) {
        #productGrid.list-view .product-card {
            grid-template-columns: 90px minmax(0, 1fr);
            gap: 12px;
        }

        #productGrid.list-view .product-image {
            width: 90px;
            height: 90px;
        }

        #productGrid.list-view .product-footer {
            grid-column: 2;
            grid-row: 4;
            justify-content: space-between;
            min-width: 0;
            border-top: 1px solid rgb(226 232 240);
            padding-top: 8px;
            margin-top: 4px;
        }
    }
</style>
@endsection

@push('scripts')
<script>

let cart = {};
let lastCompletedSaleId = null;
let pendingCartRemoval = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function addToCart(id, name, price, maxStock) {
    maxStock = Number(maxStock);

    if (maxStock <= 0) {
        return;
    }

    if (cart[id]) {
        if (cart[id].qty + 1 > maxStock) {
            alert('Cannot exceed available inventory (' + maxStock + ' units).');
            return;
        }

        cart[id].qty += 1;
    } else {
        cart[id] = {
            id: id,
            name: name,
            price: Number(price),
            maxStock: maxStock,
            qty: 1
        };
    }

    renderCart();
}

function promptRemoveCartItem(id) {
    const item = cart[id];

    if (!item) {
        return;
    }

    pendingCartRemoval = {
        type: 'item',
        id: id
    };

    document.getElementById('cartConfirmIcon').className =
        'fas fa-trash-can';

    document.getElementById('cartConfirmTitle').innerText =
        'Remove Item from Cart?';

    document.getElementById('cartConfirmSubtitle').innerText =
        'Remove this item from the active cart?';

    document.getElementById('cartConfirmItemName').innerText =
        item.name;

    document.getElementById('cartConfirmItemQty').innerText =
        'Quantity: ' + item.qty;

    document.getElementById('cartConfirmItemPrice').innerText =
        'Unit Price: ₱' + Number(item.price).toFixed(2);

    document.getElementById('cartConfirmItemTotal').innerText =
        '₱' + (item.price * item.qty).toFixed(2);

    document.getElementById('cartConfirmAcceptText').innerText =
        'Yes, Remove';

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function promptClearCart() {
    const keys = Object.keys(cart);

    if (keys.length === 0) {
        return;
    }

    pendingCartRemoval = {
        type: 'clear'
    };

    let totalItems = 0;
    let grandTotal = 0;

    keys.forEach(k => {
        totalItems += cart[k].qty;
        grandTotal += cart[k].price * cart[k].qty;
    });

    document.getElementById('cartConfirmIcon').className =
        'fas fa-triangle-exclamation';

    document.getElementById('cartConfirmTitle').innerText =
        'Clear Entire Cart?';

    document.getElementById('cartConfirmSubtitle').innerText =
        'Remove all items from the current cart?';

    document.getElementById('cartConfirmItemName').innerText =
        `${keys.length} product(s)`;

    document.getElementById('cartConfirmItemQty').innerText =
        'Units: ' + totalItems;

    document.getElementById('cartConfirmItemPrice').innerText =
        'All items will be discarded';

    document.getElementById('cartConfirmItemTotal').innerText =
        '₱' + grandTotal.toFixed(2);

    document.getElementById('cartConfirmAcceptText').innerText =
        'Yes, Clear All';

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeCartConfirmModal() {
    pendingCartRemoval = null;

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function executeCartRemoval() {
    if (!pendingCartRemoval) {
        return;
    }

    if (pendingCartRemoval.type === 'item') {
        delete cart[pendingCartRemoval.id];
    } else if (pendingCartRemoval.type === 'clear') {
        cart = {};
    }

    closeCartConfirmModal();
    renderCart();
}

window.getPosCartItemCount = function () {
    return Object.keys(cart).length;
};

function updateCartQty(id, delta) {
    if (!cart[id]) {
        return;
    }

    const newQty = cart[id].qty + delta;

    if (newQty <= 0) {
        promptRemoveCartItem(id);
        return;
    }

    if (newQty > cart[id].maxStock) {
        alert(
            'Cannot exceed available inventory (' +
            cart[id].maxStock +
            ' units).'
        );

        return;
    }

    cart[id].qty = newQty;
    renderCart();
}

function setCartQuantity(id, value) {
    if (!cart[id]) {
        return;
    }

    let quantity = parseInt(value, 10);

    if (isNaN(quantity) || quantity < 1) {
        quantity = 1;
    }

    if (quantity > cart[id].maxStock) {
        alert(
            'Cannot exceed available inventory (' +
            cart[id].maxStock +
            ' units).'
        );

        quantity = cart[id].maxStock;
    }

    cart[id].qty = quantity;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartContainer');
    const itemsCountEl = document.getElementById('cartTotalItems');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    const keys = Object.keys(cart);

    if (keys.length === 0) {
        container.innerHTML = `
            <div class="text-center py-10 text-slate-400 text-sm">
                <i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i>
                <p>
                    No items in cart yet.<br>
                    Click any in-stock product to add.
                </p>
            </div>
        `;

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
                    <div class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white truncate">
                        ${escapeHtml(item.name)}
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        ₱${Number(item.price).toFixed(2)}
                        &times;
                        ${item.qty}
                        =
                        <strong class="text-emerald-500">
                            ₱${lineTotal.toFixed(2)}
                        </strong>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button"
                        onclick="updateCartQty(${item.id}, -1)"
                        class="w-6 h-6 rounded bg-slate-200 dark:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold">
                        &minus;
                    </button>

                    <input type="number"
                        min="1"
                        max="${item.maxStock}"
                        step="1"
                        value="${item.qty}"
                        onchange="setCartQuantity(${item.id}, this.value)"
                        class="w-14 h-7 px-1 rounded bg-slate-100 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-center font-bold text-xs text-slate-900 dark:text-white">

                    <button type="button"
                        onclick="updateCartQty(${item.id}, 1)"
                        class="w-6 h-6 rounded bg-slate-200 dark:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold">
                        &plus;
                    </button>

                    <button type="button"
                        onclick="promptRemoveCartItem(${item.id})"
                        class="ml-1 text-slate-400 hover:text-rose-500 text-xs p-1">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    itemsCountEl.textContent = totalQty;

    grandTotalEl.textContent =
        '₱' + grandTotal.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    checkoutBtn.disabled = false;
    calculateChange();
}

function calculateChange() {
    const tenderedInput = document.getElementById('amountTendered');
    const changeEl = document.getElementById('changeDue');

    let grandTotal = 0;

    Object.values(cart).forEach(item => {
        grandTotal += item.price * item.qty;
    });

    const tendered = parseFloat(tenderedInput.value) || 0;
    const change = tendered >= grandTotal ? tendered - grandTotal : 0;

    changeEl.textContent =
        '₱' + change.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
}

function processCheckout() {
    const keys = Object.keys(cart);

    if (keys.length === 0) {
        alert('Your cart is empty.');
        return;
    }

    let grandTotal = 0;

    const itemsPayload = keys.map(k => {
        grandTotal += cart[k].price * cart[k].qty;

        return {
            product_id: cart[k].id,
            quantity: cart[k].qty
        };
    });

    grandTotal = Math.round(grandTotal * 100) / 100;

    const paymentMethodId =
        document.getElementById('paymentMethodSelect').value;

    const tendered =
        parseFloat(document.getElementById('amountTendered').value);

    if (isNaN(tendered)) {
        alert('Please enter the amount received.');
        return;
    }

    if (tendered < grandTotal) {
        alert(
            `Insufficient payment.\n\n` +
            `Total: ₱${grandTotal.toFixed(2)}\n` +
            `Entered: ₱${tendered.toFixed(2)}`
        );
        return;
    }

    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Processing...';

    fetch("{{ route('pos.checkout') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            payment_method_id: paymentMethodId,
            amount_tendered: tendered,
            items: itemsPayload
        })
    })
    .then(async response => {
        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                throw new Error(
                    Object.values(data.errors).flat().join('\n')
                );
            }

            throw new Error(
                data.message ||
                'Unable to complete sale.'
            );
        }

        return data;
    })
    .then(data => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML =
            '<i class="fas fa-check-circle"></i> Charge &amp; Save Sale';

        if (!data.success) {
            alert(
                data.message ||
                'Error completing sale.'
            );
            return;
        }

        lastCompletedSaleId = data.sale_id;

        document.getElementById('receiptSaleIdText').textContent =
            `Sale #${data.sale_id} recorded successfully`;

        let receiptHtml = `
            <div class="text-center font-bold text-sm border-b pb-2 mb-2">
                PAOLO PAOLO MATTING & ACCESSORIES
            </div>
            <div>Date: ${escapeHtml(data.date)}</div>
            <div>
                Cashier:
                <strong>${escapeHtml(data.cashier)}</strong>
                (${escapeHtml(data.cashier_role || 'Staff')})
            </div>
            <div>
                Payment Method:
                ${escapeHtml(data.payment_method)}
            </div>
            <div class="border-t my-2"></div>
        `;

        data.items.forEach(item => {
            receiptHtml += `
                <div class="flex justify-between gap-2">
                    <span>
                        ${escapeHtml(item.name)}
                        (x${item.quantity})
                    </span>
                    <span>
                        ₱${Number(item.total).toFixed(2)}
                    </span>
                </div>
            `;
        });

        receiptHtml += `
            <div class="border-t my-2"></div>

            <div class="flex justify-between font-bold text-sm">
                <span>TOTAL:</span>
                <span>₱${Number(data.total).toFixed(2)}</span>
            </div>

            <div class="flex justify-between">
                <span>Amount Tendered:</span>
                <span>₱${Number(data.tendered).toFixed(2)}</span>
            </div>

            <div class="flex justify-between font-bold text-emerald-500">
                <span>Change:</span>
                <span>₱${Number(data.change).toFixed(2)}</span>
            </div>
        `;

        document.getElementById('receiptContent').innerHTML =
            receiptHtml;

        const receiptModal = document.getElementById('receiptModal');
        receiptModal.classList.remove('hidden');
        receiptModal.classList.add('flex');

        cart = {};
        renderCart();

        document.getElementById('amountTendered').value = '';
        calculateChange();
    })
    .catch(error => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML =
            '<i class="fas fa-check-circle"></i> Charge &amp; Save Sale';

        alert(
            error.message ||
            'Network or server error processing sale.'
        );
    });
}

function printReceipt() {
    if (lastCompletedSaleId) {
        window.open(
            "{{ url('pos/receipt') }}/" +
            lastCompletedSaleId,
            '_blank'
        );
    }
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    window.location.reload();
}

function openProductInfo(card, event) {
    if (event) {
        event.stopPropagation();
    }

    document.getElementById('productInfoName').textContent =
        card.dataset.name || '-';

    document.getElementById('productInfoCategory').textContent =
        card.dataset.categoryName || 'General';

    document.getElementById('productInfoDescription').textContent =
        card.dataset.description || 'No description provided.';

    document.getElementById('productInfoPrice').textContent =
        '₱' + Number(card.dataset.price || 0).toFixed(2);

    document.getElementById('productInfoStock').textContent =
        Number(card.dataset.stock || 0) + ' units';

    document.getElementById('productInfoExpiry').textContent =
        card.dataset.nextExpiration || 'No expiration';

    document.getElementById('productInfoBatchCount').textContent =
        card.dataset.batchCount || '0';

    document.getElementById('productInfoOldestBatch').textContent =
        card.dataset.oldestBatch || 'N/A';

    const modal = document.getElementById('productInfoModal');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeProductInfo() {
    const modal = document.getElementById('productInfoModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('posSearch').addEventListener('input', function (e) {
    const q = e.target.value.toLowerCase();

    document.querySelectorAll('.product-item').forEach(el => {
        const name = (el.dataset.name || '').toLowerCase();
        const description = (el.dataset.description || '').toLowerCase();
        const category = (el.dataset.categoryName || '').toLowerCase();

        const matches =
            name.includes(q) ||
            description.includes(q) ||
            category.includes(q);

        el.classList.toggle('hidden', !matches);
    });
});

function filterCategory(catId, button) {
    document.querySelectorAll('.cat-filter-btn').forEach(btn => {
        btn.classList.remove('bg-red-600', 'text-white');
        btn.classList.add(
            'bg-slate-200',
            'dark:bg-dark-800',
            'text-slate-700',
            'dark:text-slate-300'
        );
    });

    button.classList.remove(
        'bg-slate-200',
        'dark:bg-dark-800',
        'text-slate-700',
        'dark:text-slate-300'
    );

    button.classList.add('bg-red-600', 'text-white');

    document.querySelectorAll('.product-item').forEach(el => {
        const visible =
            catId === 'ALL' ||
            el.dataset.category === catId;

        el.classList.toggle('hidden', !visible);
    });

    document.getElementById('posSearch').value = '';
}

let currentPosImages = [];
let currentPosIndex = 0;

function openPosGallery(images, title, event) {
    if (event) {
        event.stopPropagation();
    }

    if (!images || images.length === 0) {
        return;
    }

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
    if (!currentPosImages || currentPosImages.length === 0) {
        return;
    }

    document.getElementById('posGalleryMainImg').src =
        currentPosImages[currentPosIndex];

    document.getElementById('posGalleryCounter').innerText =
        `${currentPosIndex + 1} / ${currentPosImages.length}`;

    const hideArrows = currentPosImages.length <= 1;

    document.getElementById('posPrevGalleryBtn')
        .classList.toggle('hidden', hideArrows);

    document.getElementById('posNextGalleryBtn')
        .classList.toggle('hidden', hideArrows);

    const strip = document.getElementById('posGalleryThumbnailsStrip');
    strip.innerHTML = '';

    currentPosImages.forEach((url, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';

        btn.className =
            `w-12 h-12 rounded-xl border-2 overflow-hidden flex-shrink-0 ${
                i === currentPosIndex
                    ? 'border-red-500'
                    : 'border-slate-300 dark:border-slate-700 opacity-60'
            }`;

        const img = document.createElement('img');
        img.src = url;
        img.className = 'w-full h-full object-cover';

        btn.appendChild(img);

        btn.onclick = event => {
            event.stopPropagation();
            currentPosIndex = i;
            updatePosGalleryDisplay();
        };

        strip.appendChild(btn);
    });
}

function prevPosGalleryImage() {
    currentPosIndex =
        currentPosIndex > 0
            ? currentPosIndex - 1
            : currentPosImages.length - 1;

    updatePosGalleryDisplay();
}

function nextPosGalleryImage() {
    currentPosIndex =
        currentPosIndex < currentPosImages.length - 1
            ? currentPosIndex + 1
            : 0;

    updatePosGalleryDisplay();
}

function setProductView(view) {
    const productGrid = document.getElementById('productGrid');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');

    if (!productGrid || !gridBtn || !listBtn) {
        return;
    }

    if (view === 'list') {
        productGrid.classList.add('list-view');
        productGrid.classList.remove('grid-cols-2', 'sm:grid-cols-3');

        listBtn.classList.add('bg-red-600', 'text-white');
        gridBtn.classList.remove('bg-red-600', 'text-white');

        localStorage.setItem('posProductView', 'list');
    } else {
        productGrid.classList.remove('list-view');
        productGrid.classList.add('grid-cols-2', 'sm:grid-cols-3');

        gridBtn.classList.add('bg-red-600', 'text-white');
        listBtn.classList.remove('bg-red-600', 'text-white');

        localStorage.setItem('posProductView', 'grid');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const savedView =
        localStorage.getItem('posProductView') || 'grid';

    setProductView(savedView);
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closePosGallery();
        closeProductInfo();
        closeCartConfirmModal();
    }
});
</script>
@endpush
