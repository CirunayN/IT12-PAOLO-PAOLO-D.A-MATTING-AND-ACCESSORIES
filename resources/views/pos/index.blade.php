@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-cash-register text-red-500"></i>
                POS Cashier Terminal
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-slate-200/90 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 shadow-sm">
                <div class="w-6 h-6 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center text-xs">
                    <i class="fas fa-user-tag"></i>
                </div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Cashier:</span>
                <span class="text-xs font-black text-slate-900 dark:text-white">
                    {{ auth()->user()->name }}
                    ({{ auth()->user()->role ?? 'Staff' }})
                </span>
            </div>

            <button type="button" id="openCartBtn" onclick="showFloatingCart()"
                class="relative inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs shadow-lg shadow-red-600/20 transition-all">
                <i class="fas fa-basket-shopping"></i>
                Cart
                <span id="topCartCount"
                    class="min-w-5 h-5 px-1.5 rounded-full bg-white text-red-600 text-[10px] font-black flex items-center justify-center">
                    0
                </span>
                <span id="topCartTotal" class="hidden sm:inline text-[11px] opacity-90">₱0.00</span>
            </button>
        </div>
    </div>

    <div class="hidden lg:flex items-center gap-3 text-xs font-semibold text-slate-500">
        <label for="posLayoutRatio">Layout</label>
        <input id="posLayoutRatio" type="range" min="55" max="80" step="5" value="70" class="w-36 accent-red-600">
        <output id="posLayoutLabel" for="posLayoutRatio">Products 70% / Cart 30%</output>
    </div>
    <div id="posWorkspace">
    <section id="posProducts" class="glass-card rounded-2xl border overflow-hidden min-w-0">
        <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="relative flex-1 min-w-0">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text"
                        id="posSearch"
                        placeholder="Search product name, description or category..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold whitespace-nowrap">
                    <i class="fas fa-table-list mr-1 text-red-500"></i>
                    Compact Data Grid
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

        <div class="overflow-auto max-h-[680px]" id="productGridWrap">
            <table class="w-full min-w-[850px] border-collapse text-left">
                <thead class="sticky top-0 z-10 bg-slate-100/95 dark:bg-dark-900/95 backdrop-blur border-b border-slate-200 dark:border-slate-800">
                    <tr class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="w-[64px] px-3 py-2.5 font-black">Image</th>
                        <th class="px-3 py-2.5 font-black">Product</th>
                        <th class="w-[160px] px-3 py-2.5 font-black">Category</th>
                        <th class="w-[120px] px-3 py-2.5 font-black text-center">Stock</th>
                        <th class="w-[130px] px-3 py-2.5 font-black text-right">Price</th>
                        <th class="w-[84px] px-3 py-2.5 font-black text-center">Info</th>
                        <th class="w-[105px] px-3 py-2.5 font-black text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="productGrid" class="divide-y divide-slate-200 dark:divide-slate-800">
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

                    <tr class="product-item group hover:bg-red-500/[0.035] dark:hover:bg-red-500/[0.055] transition-colors {{ $qty <= 0 ? 'opacity-60' : '' }}"
                        data-id="{{ $p->ID }}"
                        data-name="{{ $p->Name }}"
                        data-description="{{ $p->Description ?? '' }}"
                        data-price="{{ $price }}"
                        data-stock="{{ $qty }}"
                        data-category="{{ $p->Category_ID }}"
                        data-category-name="{{ $p->category->Name ?? 'General' }}"
                        data-next-expiration="{{ $nextExpiry?->Expiration_Date?->format('M d, Y') ?? 'No expiration' }}"
                        data-batch-count="{{ $sellableBatches->count() }}"
                        data-oldest-batch="{{ $oldestBatch?->created_at?->format('M d, Y') ?? 'N/A' }}">

                        <td class="px-3 py-2">
                            <div class="relative w-11 h-11 rounded-lg overflow-hidden bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center">
                                @if($p->image_url)
                                <img src="{{ $p->image_url }}"
                                    alt="{{ $p->Name }}"
                                    class="w-full h-full object-cover">
                                @else
                                <i class="fas fa-box text-slate-400 opacity-60"></i>
                                @endif

                                @if($imagesCount > 0)
                                <button type="button"
                                    data-gallery-images="{{ json_encode($allImages) }}"
                                    data-gallery-title="{{ $p->Name }}"
                                    onclick="openPosGalleryFromButton(this, event)"
                                    class="absolute inset-0 opacity-0 group-hover:opacity-100 bg-black/55 text-white text-[10px] transition-opacity"
                                    title="View product photos">
                                    <i class="fas fa-images"></i>
                                </button>
                                @endif
                            </div>
                        </td>

                        <td class="px-3 py-2 min-w-0">
                            <div class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white leading-tight">
                                {{ $p->Name }}
                            </div>
                            @if($p->Description)
                            <div class="mt-0.5 text-[10px] text-slate-400 truncate max-w-[420px]" title="{{ $p->Description }}">
                                {{ $p->Description }}
                            </div>
                            @endif
                        </td>

                        <td class="px-3 py-2">
                            <span class="inline-flex px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ $p->category->Name ?? 'General' }}
                            </span>
                        </td>

                        <td class="px-3 py-2 text-center">
                            @if($qty <= 0)
                            <span class="inline-flex px-2 py-1 rounded-lg text-[10px] font-black uppercase text-rose-500 bg-rose-500/10 border border-rose-500/25">
                                Out
                            </span>
                            @elseif($qty <= 5)
                            <span class="inline-flex px-2 py-1 rounded-lg text-[10px] font-black text-amber-500 bg-amber-500/10 border border-amber-500/25">
                                {{ number_format($qty, 0) }} left
                            </span>
                            @else
                            <span class="font-black text-xs text-emerald-600 dark:text-emerald-400">
                                {{ number_format($qty, 0) }}
                            </span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right font-display font-black text-sm text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                            ₱{{ number_format($price, 2) }}
                        </td>

                        <td class="px-3 py-2 text-center">
                            <button type="button"
                                onclick="openProductInfo(this.closest('.product-item'), event)"
                                class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-600 dark:text-slate-300 transition-all"
                                title="Product details">
                                <i class="fas fa-circle-info text-xs"></i>
                            </button>
                        </td>

                        <td class="px-3 py-2 text-center">
                            <button type="button"
                                {{ $qty <= 0 ? 'disabled' : '' }}
                                onclick="addProductRowToCart(this)"
                                class="inline-flex items-center justify-center gap-1.5 px-3 h-8 rounded-lg bg-red-600 hover:bg-red-500 disabled:bg-slate-400 disabled:cursor-not-allowed text-white text-[10px] font-black shadow-sm transition-all active:scale-95 whitespace-nowrap">
                                <i class="fas fa-plus"></i>
                                Add
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div id="noProductsMessage" class="hidden text-center py-14 text-slate-400 text-sm">
                <i class="fas fa-magnifying-glass text-2xl mb-2 opacity-50"></i>
                <p>No products match the current search/filter.</p>
            </div>
        </div>
    </section>
    <aside id="floatingCart" class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-dark-850 shadow-lg overflow-hidden min-w-0">
    <div id="floatingCartHeader"
        class="flex items-center justify-between gap-3 px-4 py-3 bg-slate-100 dark:bg-dark-900 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-2 min-w-0">
            <div class="min-w-0">
                <div class="font-display font-black text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-basket-shopping text-red-500"></i>
                    Customer Cart
                </div>
                <div id="floatingCartHeaderSummary" class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
                    0 items • ₱0.00
                </div>
            </div>
        </div>

    </div>

    <div id="floatingCartBody" class="overflow-y-auto">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <div class="text-[10px] uppercase tracking-wider font-black text-slate-400">Selected Items</div>
                <button type="button" onclick="promptClearCart()"
                    class="text-[10px] font-black text-rose-500 hover:text-rose-400">
                    <i class="fas fa-trash-can mr-1"></i>
                    Clear Cart
                </button>
            </div>

            <div id="cartContainer" class="space-y-2 max-h-[245px] overflow-y-auto pr-1">
                <div class="text-center py-8 text-slate-400 text-xs">
                    <i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i>
                    <p>No items in cart yet.</p>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400">Total Items</span>
                    <strong id="cartTotalItems" class="text-slate-800 dark:text-slate-200">0</strong>
                </div>

                <div class="flex items-center justify-between text-base">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Grand Total</span>
                    <strong id="cartGrandTotal"
                        class="font-display font-black text-xl text-emerald-600 dark:text-emerald-400">
                        ₱0.00
                    </strong>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Payment Method
                    </label>
                    <select id="paymentMethodSelect" onchange="handlePaymentMethodChange()"
                        class="w-full px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white">
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method->ID }}" data-name="{{ strtolower($method->Name) }}">{{ $method->Name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="gcashReferenceWrap" class="hidden">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        GCash Reference Number <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-hashtag absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="gcashReference"
                            inputmode="numeric" autocomplete="off" maxlength="30"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-black tracking-wide text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                            placeholder="Enter GCash reference no.">
                    </div>
                    <p class="mt-1 text-[9px] text-slate-400">Required only when GCash is selected.</p>
                </div>

                <div>
                    <label id="amountTenderedLabel" class="block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Amount Received (₱)
                    </label>
                    <input type="number" id="amountTendered"
                        min="0" step="0.01" inputmode="decimal"
                        oninput="calculateChange()"
                        class="w-full px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-base font-black text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                        placeholder="0.00">
                </div>

                <div class="p-3 rounded-xl bg-slate-100 dark:bg-dark-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-[10px] font-black text-slate-500 dark:text-slate-400">Change Due</span>
                    <strong id="changeDue" class="text-base font-black text-emerald-500">₱0.00</strong>
                </div>

                <button type="button" id="checkoutBtn"
                    onclick="processCheckout()" disabled
                    class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-black text-sm shadow-lg shadow-red-600/20 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    Charge &amp; Save Sale
                </button>

                <p class="text-[9px] text-center text-slate-400">
                    FIFO inventory deduction remains active.
                </p>
            </div>
        </div>
    </div>
    </aside>
    </div>
</div>

<div id="productInfoModal"
    class="fixed inset-0 z-[70] bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
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
            <div class="text-xs uppercase font-bold text-slate-400 mb-1.5">Description / Other Details</div>
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
    </div>
</div>

<div id="posGalleryModal"
    class="fixed inset-0 z-[70] bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-3xl w-full rounded-2xl border border-slate-700 p-5 shadow-2xl">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h3 id="posGalleryTitle" class="font-black text-lg text-slate-900 dark:text-white">Product Photos</h3>
                <div id="posGalleryCounter" class="text-xs text-slate-400 mt-0.5">1 / 1</div>
            </div>
            <button type="button" onclick="closePosGallery()" class="text-2xl text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="relative rounded-2xl overflow-hidden bg-black/30 min-h-[320px] flex items-center justify-center">
            <img id="posGalleryMainImg" src="" alt="Product" class="max-h-[520px] w-full object-contain">
            <button type="button" id="posPrevGalleryBtn" onclick="prevPosGalleryImage()"
                class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 text-white">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" id="posNextGalleryBtn" onclick="nextPosGalleryImage()"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 text-white">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div id="posGalleryThumbnailsStrip" class="flex items-center gap-2 overflow-x-auto pt-4"></div>
    </div>
</div>

<div id="receiptModal"
    class="fixed inset-0 z-[80] bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
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
    class="fixed inset-0 z-[80] bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-500 flex items-center justify-center text-xl flex-shrink-0">
                <i id="cartConfirmIcon" class="fas fa-trash-can"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="cartConfirmTitle" class="font-display font-black text-lg text-slate-900 dark:text-white">Remove Item?</h3>
                <p id="cartConfirmSubtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-1">Are you sure?</p>
            </div>
            <button type="button" onclick="closeCartConfirmModal()" class="text-slate-400 hover:text-white text-xl">&times;</button>
        </div>

        <div id="cartConfirmItemBox"
            class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
            <div class="flex items-center justify-between gap-2">
                <span id="cartConfirmItemName" class="font-bold text-slate-900 dark:text-white text-sm truncate">-</span>
                <span id="cartConfirmItemTotal" class="font-bold text-rose-500 text-sm">₱0.00</span>
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
@endsection

@push('scripts')
<script>
let cart = {};
let activeCategory = 'ALL';
let lastCompletedSaleId = null;
let pendingCartRemoval = null;
let currentPosImages = [];
let currentPosIndex = 0;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function money(value) {
    return '₱' + Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getCartGrandTotal() {
    return Object.values(cart).reduce((total, item) => total + (item.price * item.qty), 0);
}

function getCartTotalQty() {
    return Object.values(cart).reduce((total, item) => total + item.qty, 0);
}

function getSelectedPaymentName() {
    const select = document.getElementById('paymentMethodSelect');
    const option = select?.selectedOptions?.[0];
    return String(option?.dataset?.name || option?.textContent || '').trim().toLowerCase();
}

function isGcashSelected() {
    return getSelectedPaymentName() === 'gcash';
}

function showFloatingCart() {
    if (window.innerWidth < 1024) document.getElementById('floatingCart').scrollIntoView({behavior: 'smooth', block: 'start'});
}
function initializePosLayout() {
    const slider = document.getElementById('posLayoutRatio');
    const apply = ratio => {
        ratio = Math.min(80, Math.max(55, Number(ratio) || 70));
        slider.value = ratio;
        document.getElementById('posWorkspace').style.setProperty('--pos-columns', 'minmax(0, ' + ratio + 'fr) minmax(300px, ' + (100-ratio) + 'fr)');
        document.getElementById('posLayoutLabel').textContent = 'Products ' + ratio + '% / Cart ' + (100-ratio) + '%';
        try { localStorage.setItem('posProductRatio', ratio); } catch (_) {}
    };
    let saved = 70;
    try { saved = localStorage.getItem('posProductRatio') || 70; } catch (_) {}
    apply(saved);
    slider.addEventListener('input', () => apply(slider.value));
}

function addProductRowToCart(button) {
    const row = button.closest('.product-item');
    if (!row) return;

    addToCart(
        row.dataset.id,
        row.dataset.name || 'Product',
        row.dataset.price,
        row.dataset.stock
    );
}

function openPosGalleryFromButton(button, event) {
    if (event) event.stopPropagation();

    let images = [];
    try {
        images = JSON.parse(button.dataset.galleryImages || '[]');
    } catch (error) {
        images = [];
    }

    openPosGallery(
        images,
        button.dataset.galleryTitle || 'Product Photos',
        event
    );
}
function addToCart(id, name, price, maxStock) {
    maxStock = Number(maxStock);
    if (maxStock <= 0) return;

    if (cart[id]) {
        if (cart[id].qty + 1 > maxStock) {
            alert('Cannot exceed available inventory (' + maxStock + ' units).');
            return;
        }
        cart[id].qty += 1;
    } else {
        cart[id] = {
            id: Number(id),
            name: name,
            price: Number(price),
            maxStock: maxStock,
            qty: 1
        };
    }

    renderCart();
}

function updateCartQty(id, delta) {
    if (!cart[id]) return;

    const newQty = cart[id].qty + delta;
    if (newQty <= 0) {
        promptRemoveCartItem(id);
        return;
    }

    if (newQty > cart[id].maxStock) {
        alert('Cannot exceed available inventory (' + cart[id].maxStock + ' units).');
        return;
    }

    cart[id].qty = newQty;
    renderCart();
}

function setCartQuantity(id, value) {
    if (!cart[id]) return;

    let quantity = parseInt(value, 10);
    if (isNaN(quantity) || quantity < 1) quantity = 1;

    if (quantity > cart[id].maxStock) {
        alert('Cannot exceed available inventory (' + cart[id].maxStock + ' units).');
        quantity = cart[id].maxStock;
    }

    cart[id].qty = quantity;
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartContainer');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const keys = Object.keys(cart);
    const totalQty = getCartTotalQty();
    const grandTotal = getCartGrandTotal();

    document.getElementById('cartTotalItems').textContent = totalQty;
    document.getElementById('cartGrandTotal').textContent = money(grandTotal);
    document.getElementById('topCartCount').textContent = totalQty;
    document.getElementById('topCartTotal').textContent = money(grandTotal);
    document.getElementById('floatingCartHeaderSummary').textContent = `${totalQty} item${totalQty === 1 ? '' : 's'} • ${money(grandTotal)}`;

    if (keys.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-slate-400 text-xs">
                <i class="fas fa-cart-arrow-down text-3xl mb-2 opacity-50"></i>
                <p>No items in cart yet.</p>
            </div>
        `;
        checkoutBtn.disabled = true;
        document.getElementById('gcashReference').value = '';
        if (isGcashSelected()) document.getElementById('amountTendered').value = '';
        calculateChange();
        return;
    }

    let html = '';
    keys.forEach(k => {
        const item = cart[k];
        const lineTotal = item.price * item.qty;

        html += `
            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-dark-800/80 border border-slate-200 dark:border-slate-700/60 flex items-center justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-xs text-slate-900 dark:text-white truncate">${escapeHtml(item.name)}</div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400">
                        ${money(item.price)} × ${item.qty} =
                        <strong class="text-emerald-500">${money(lineTotal)}</strong>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" onclick="updateCartQty(${item.id}, -1)"
                        class="w-7 h-7 rounded-lg bg-slate-200 dark:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold">−</button>
                    <input type="number" min="1" max="${item.maxStock}" step="1" value="${item.qty}"
                        onchange="setCartQuantity(${item.id}, this.value)"
                        class="w-12 h-7 px-1 rounded-lg bg-white dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-center font-bold text-xs text-slate-900 dark:text-white">
                    <button type="button" onclick="updateCartQty(${item.id}, 1)"
                        class="w-7 h-7 rounded-lg bg-slate-200 dark:bg-dark-700 text-slate-700 dark:text-slate-200 text-xs font-bold">+</button>
                    <button type="button" onclick="promptRemoveCartItem(${item.id})"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 text-xs">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    checkoutBtn.disabled = false;

    if (isGcashSelected()) {
        document.getElementById('amountTendered').value = grandTotal.toFixed(2);
    }

    calculateChange();
}

function handlePaymentMethodChange() {
    const gcash = isGcashSelected();
    const refWrap = document.getElementById('gcashReferenceWrap');
    const refInput = document.getElementById('gcashReference');
    const tendered = document.getElementById('amountTendered');
    const label = document.getElementById('amountTenderedLabel');

    refWrap.classList.toggle('hidden', !gcash);
    refInput.required = gcash;

    if (gcash) {
        label.textContent = 'Amount Paid (₱)';
        tendered.value = getCartGrandTotal() > 0 ? getCartGrandTotal().toFixed(2) : '';
        tendered.readOnly = true;
        tendered.classList.add('opacity-80', 'cursor-not-allowed');
    } else {
        label.textContent = 'Amount Received (₱)';
        tendered.readOnly = false;
        tendered.classList.remove('opacity-80', 'cursor-not-allowed');
        tendered.value = '';
        refInput.value = '';
    }

    calculateChange();
}

function calculateChange() {
    const grandTotal = getCartGrandTotal();
    const tenderedInput = document.getElementById('amountTendered');
    const changeEl = document.getElementById('changeDue');

    if (isGcashSelected()) {
        if (grandTotal > 0) tenderedInput.value = grandTotal.toFixed(2);
        changeEl.textContent = money(0);
        return;
    }

    const tendered = parseFloat(tenderedInput.value) || 0;
    const change = tendered >= grandTotal ? tendered - grandTotal : 0;
    changeEl.textContent = money(change);
}

function promptRemoveCartItem(id) {
    const item = cart[id];
    if (!item) return;

    pendingCartRemoval = { type: 'item', id: id };
    document.getElementById('cartConfirmIcon').className = 'fas fa-trash-can';
    document.getElementById('cartConfirmTitle').innerText = 'Remove Item from Cart?';
    document.getElementById('cartConfirmSubtitle').innerText = 'Remove this item from the active cart?';
    document.getElementById('cartConfirmItemName').innerText = item.name;
    document.getElementById('cartConfirmItemQty').innerText = 'Quantity: ' + item.qty;
    document.getElementById('cartConfirmItemPrice').innerText = 'Unit Price: ' + money(item.price);
    document.getElementById('cartConfirmItemTotal').innerText = money(item.price * item.qty);
    document.getElementById('cartConfirmAcceptText').innerText = 'Yes, Remove';

    const modal = document.getElementById('cartConfirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function promptClearCart() {
    const keys = Object.keys(cart);
    if (keys.length === 0) return;

    pendingCartRemoval = { type: 'clear' };
    document.getElementById('cartConfirmIcon').className = 'fas fa-triangle-exclamation';
    document.getElementById('cartConfirmTitle').innerText = 'Clear Entire Cart?';
    document.getElementById('cartConfirmSubtitle').innerText = 'Remove all items from the current cart?';
    document.getElementById('cartConfirmItemName').innerText = `${keys.length} product(s)`;
    document.getElementById('cartConfirmItemQty').innerText = 'Units: ' + getCartTotalQty();
    document.getElementById('cartConfirmItemPrice').innerText = 'All items will be discarded';
    document.getElementById('cartConfirmItemTotal').innerText = money(getCartGrandTotal());
    document.getElementById('cartConfirmAcceptText').innerText = 'Yes, Clear All';

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
    if (!pendingCartRemoval) return;

    if (pendingCartRemoval.type === 'item') {
        delete cart[pendingCartRemoval.id];
    } else if (pendingCartRemoval.type === 'clear') {
        cart = {};
    }

    closeCartConfirmModal();
    renderCart();
}

function processCheckout() {
    const keys = Object.keys(cart);
    if (keys.length === 0) {
        alert('Your cart is empty.');
        return;
    }

    const grandTotal = Math.round(getCartGrandTotal() * 100) / 100;
    const paymentMethodId = document.getElementById('paymentMethodSelect').value;
    const gcash = isGcashSelected();
    const gcashReference = document.getElementById('gcashReference').value.trim();
    let tendered = parseFloat(document.getElementById('amountTendered').value);

    if (gcash) {
        if (!gcashReference) {
            alert('Please enter the GCash reference number.');
            document.getElementById('gcashReference').focus();
            return;
        }
        tendered = grandTotal;
    } else {
        if (isNaN(tendered)) {
            alert('Please enter the amount received.');
            return;
        }
        if (tendered < grandTotal) {
            alert(`Insufficient payment.\n\nTotal: ${money(grandTotal)}\nEntered: ${money(tendered)}`);
            return;
        }
    }

    const itemsPayload = keys.map(k => ({
        product_id: cart[k].id,
        quantity: cart[k].qty
    }));

    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

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
            gcash_reference: gcash ? gcashReference : null,
            items: itemsPayload
        })
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            if (data.errors) {
                throw new Error(Object.values(data.errors).flat().join('\n'));
            }
            throw new Error(data.message || 'Unable to complete sale.');
        }
        return data;
    })
    .then(data => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Charge &amp; Save Sale';

        if (!data.success) {
            alert(data.message || 'Error completing sale.');
            return;
        }

        lastCompletedSaleId = data.sale_id;
        document.getElementById('receiptSaleIdText').textContent = `Sale #${data.sale_id} recorded successfully`;

        let receiptHtml = `
            <div class="text-center font-bold text-sm border-b pb-2 mb-2">PAOLO PAOLO MATTING & ACCESSORIES</div>
            <div>Date: ${escapeHtml(data.date)}</div>
            <div>Cashier: <strong>${escapeHtml(data.cashier)}</strong> (${escapeHtml(data.cashier_role || 'Staff')})</div>
            <div>Payment Method: ${escapeHtml(data.payment_method)}</div>
        `;

        if (data.gcash_reference) {
            receiptHtml += `<div>GCash Ref: <strong>${escapeHtml(data.gcash_reference)}</strong></div>`;
        }

        receiptHtml += '<div class="border-t my-2"></div>';

        data.items.forEach(item => {
            receiptHtml += `
                <div class="flex justify-between gap-2">
                    <span>${escapeHtml(item.name)} (x${item.quantity})</span>
                    <span>${money(item.total)}</span>
                </div>
            `;
        });

        receiptHtml += `
            <div class="border-t my-2"></div>
            <div class="flex justify-between font-bold text-sm"><span>TOTAL:</span><span>${money(data.total)}</span></div>
        `;

        if (String(data.payment_method || '').toLowerCase() === 'cash') {
            receiptHtml += `
                <div class="flex justify-between"><span>Amount Tendered:</span><span>${money(data.tendered)}</span></div>
                <div class="flex justify-between font-bold text-emerald-500"><span>Change:</span><span>${money(data.change)}</span></div>
            `;
        }

        document.getElementById('receiptContent').innerHTML = receiptHtml;
        const receiptModal = document.getElementById('receiptModal');
        receiptModal.classList.remove('hidden');
        receiptModal.classList.add('flex');

        cart = {};
        renderCart();
        document.getElementById('amountTendered').value = '';
        document.getElementById('gcashReference').value = '';
        calculateChange();
    })
    .catch(error => {
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = '<i class="fas fa-check-circle"></i> Charge &amp; Save Sale';
        alert(error.message || 'Network or server error processing sale.');
    });
}

function printReceipt() {
    if (lastCompletedSaleId) {
        window.open("{{ url('pos/receipt') }}/" + lastCompletedSaleId, '_blank');
    }
}

function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    window.location.reload();
}

function applyProductFilters() {
    const q = document.getElementById('posSearch').value.trim().toLowerCase();
    let visibleCount = 0;

    document.querySelectorAll('.product-item').forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const description = (row.dataset.description || '').toLowerCase();
        const categoryName = (row.dataset.categoryName || '').toLowerCase();
        const categoryMatch = activeCategory === 'ALL' || row.dataset.category === activeCategory;
        const searchMatch = !q || name.includes(q) || description.includes(q) || categoryName.includes(q);
        const visible = categoryMatch && searchMatch;

        row.classList.toggle('hidden', !visible);
        if (visible) visibleCount++;
    });

    document.getElementById('noProductsMessage').classList.toggle('hidden', visibleCount !== 0);
}

function filterCategory(catId, button) {
    activeCategory = String(catId);

    document.querySelectorAll('.cat-filter-btn').forEach(btn => {
        btn.classList.remove('bg-red-600', 'text-white');
        btn.classList.add('bg-slate-200', 'dark:bg-dark-800', 'text-slate-700', 'dark:text-slate-300');
    });

    button.classList.remove('bg-slate-200', 'dark:bg-dark-800', 'text-slate-700', 'dark:text-slate-300');
    button.classList.add('bg-red-600', 'text-white');
    applyProductFilters();
}

function openProductInfo(row, event) {
    if (event) event.stopPropagation();

    document.getElementById('productInfoName').textContent = row.dataset.name || '-';
    document.getElementById('productInfoCategory').textContent = row.dataset.categoryName || 'General';
    document.getElementById('productInfoDescription').textContent = row.dataset.description || 'No description provided.';
    document.getElementById('productInfoPrice').textContent = money(row.dataset.price || 0);
    document.getElementById('productInfoStock').textContent = Number(row.dataset.stock || 0) + ' units';
    document.getElementById('productInfoExpiry').textContent = row.dataset.nextExpiration || 'No expiration';
    document.getElementById('productInfoBatchCount').textContent = row.dataset.batchCount || '0';
    document.getElementById('productInfoOldestBatch').textContent = row.dataset.oldestBatch || 'N/A';

    const modal = document.getElementById('productInfoModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeProductInfo() {
    const modal = document.getElementById('productInfoModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

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

    const hideArrows = currentPosImages.length <= 1;
    document.getElementById('posPrevGalleryBtn').classList.toggle('hidden', hideArrows);
    document.getElementById('posNextGalleryBtn').classList.toggle('hidden', hideArrows);

    const strip = document.getElementById('posGalleryThumbnailsStrip');
    strip.innerHTML = '';

    currentPosImages.forEach((url, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `w-12 h-12 rounded-xl border-2 overflow-hidden flex-shrink-0 ${i === currentPosIndex ? 'border-red-500' : 'border-slate-300 dark:border-slate-700 opacity-60'}`;

        const img = document.createElement('img');
        img.src = url;
        img.className = 'w-full h-full object-cover';
        img.alt = 'Product thumbnail';
        btn.appendChild(img);

        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            currentPosIndex = i;
            updatePosGalleryDisplay();
        });

        strip.appendChild(btn);
    });
}

function prevPosGalleryImage() {
    currentPosIndex = currentPosIndex > 0 ? currentPosIndex - 1 : currentPosImages.length - 1;
    updatePosGalleryDisplay();
}

function nextPosGalleryImage() {
    currentPosIndex = currentPosIndex < currentPosImages.length - 1 ? currentPosIndex + 1 : 0;
    updatePosGalleryDisplay();
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('posSearch').addEventListener('input', applyProductFilters);
    initializePosLayout();
    handlePaymentMethodChange();
    renderCart();
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closePosGallery();
        closeProductInfo();
        closeCartConfirmModal();
    }
});
</script>
@endpush

@push('styles')
<style>
#posWorkspace { display:grid; grid-template-columns:1fr; gap:16px; align-items:start; }
#floatingCart { scroll-margin-top:90px; }
#floatingCartBody { max-height:none; }
@media (min-width:1024px) {
    #posWorkspace { grid-template-columns:var(--pos-columns, minmax(0,7fr) minmax(300px,3fr)); }
    #floatingCart { position:sticky; top:90px; }
    #floatingCartBody { max-height:calc(100vh - 160px); }
}
</style>
@endpush
